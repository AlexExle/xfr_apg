<?php

class Nobita_Teams_Model_Comment extends Nobita_Teams_Model_Abstract
{
	const FETCH_USER = 0x01;
	const FETCH_TEAM = 0x02;

	const FETCH_CONTENT_POST = 0x04;
	const FETCH_CONTENT_EVENT = 0x08;

	const FETCH_BBCODE_CACHE = 0x40;

	const CONTENT_TYPE_POST = 'post';
	const CONTENT_TYPE_EVENT = 'event';

	public function getCommentById($commentId, array $fetchOptions = array())
	{
		$comments = $this->getComments(array('comment_id' => $commentId), $fetchOptions);
		return isset($comments[$commentId]) ? $comments[$commentId] : array();
	}

	public function getCommentsByIds(array $commentIds, array $fetchOptions = array())
	{
		if(empty($commentIds))
		{
			return array();
		}

		return $this->getComments(array('comment_id' => $commentIds), $fetchOptions);
	}

	public function getComments(array $conditions, array $fetchOptions)
	{
		$whereClause = $this->prepareCommentConditions($conditions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		$joinOptions = $this->prepareCommentFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			"
				SELECT comment.*
					$joinOptions[selectFields]
				FROM xf_team_comment AS comment
					$joinOptions[joinTables]
				WHERE $whereClause
				 $joinOptions[orderClause]
			", $limitOptions['limit'], $limitOptions['offset'])
		, 'comment_id');
	}

	public function getCommentIds(array $conditions, array $fetchOptions = array())
	{
		$whereClause = $this->prepareCommentConditions($conditions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		$joinOptions = $this->prepareCommentFetchOptions($fetchOptions);

		return $this->_getDb()->fetchCol($this->limitQueryResults("
			SELECT comment.comment_id
			FROM xf_team_comment AS comment
			WHERE $whereClause
			 $joinOptions[orderClause]
		", $limitOptions['limit'], $limitOptions['offset']));
	}

	public function getLatestCommentIdsForContent($contentId, $contentType, array $fetchOptions = array())
	{
		$conditions = array(
			'content_id' => $contentId,
			'content_type' => $contentType
		);

		$fetchOptions = array_merge(array(
			'order' => 'recent_comment'
		), $fetchOptions);

		return $this->getCommentIds($conditions, $fetchOptions);
	}

	public function countComments(array $conditions)
	{
		$whereClause = $this->prepareCommentConditions($conditions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM xf_team_comment AS comment
			WHERE $whereClause
		");
	}

	public function getCommentIdsInRange($start, $limit)
	{
		$db = $this->_getDb();

		return $db->fetchCol($db->limit('
			SELECT comment_id
			FROM xf_team_comment
			WHERE comment_id > ?
			ORDER BY comment_id
		', $limit), $start);
	}

	public function prepareCommentConditions(array $conditions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		foreach(array('comment_id', 'user_id', 'team_id', 'content_id', 'content_type') as $field)
		{
			if(empty($conditions[$field])) continue;

			$value = $conditions[$field];
			$valueQuoted = $db->quote($value);

			if(is_array($value))
			{
				$sqlConditions[] = "comment.{$field} IN ({$valueQuoted})";
			}
			else
			{
				$sqlConditions[] = "comment.{$field} = {$valueQuoted}";
			}
		}

		$contentTypes = array(
			self::CONTENT_TYPE_EVENT => 'event_id',
			self::CONTENT_TYPE_POST => 'post_id',
		);

		foreach($contentTypes as $contentType => $contentKey)
		{
			if(empty($conditions[$contentKey])) continue;

			$value = $db->quote($conditions[$contentKey]);
			$contentTypeQuoted = $db->quote($contentType);

			if(is_array($conditions[$contentKey]))
			{
				$sqlConditions[] = "comment.content_id IN ($value) AND comment.content_type = $contentTypeQuoted";
			}
			else
			{
				$sqlConditions[] = "comment.content_id = $value AND comment.content_type = $contentTypeQuoted";
			}
		}

		if(!empty($conditions['comment_date']) && is_array($conditions['comment_date']))
		{
			$sqlConditions[] = $this->getCutOffCondition('comment.comment_date', $conditions['comment_date']);
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareCommentFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$orderClause = '';

		$db = $this->_getDb();

		$direction = 'asc';
		$sqlValidDirection = array('asc', 'desc');

		if(isset($fetchOptions['direction']) && in_array(strtolower($fetchOptions['direction']), $sqlValidDirection))
		{
			$direction = $fetchOptions['direction'];
		}

		if(isset($fetchOptions['order']))
		{
			switch($fetchOptions['order'])
			{
				case 'comment_date';
					$orderClause = "comment.comment_date $direction";
					break;
				case 'recent_comment';
					$orderClause = "comment.comment_date DESC";
					break;
				case 'likes':
					$orderClause = "comment.likes $direction";
					break;
			}
		}

		if(!empty($fetchOptions['join']))
		{
			if($fetchOptions['join'] & self::FETCH_USER)
			{
				$selectFields .= ',user.user_id, IF(user.username IS NULL, comment.username, user.username) AS username,
					user.gender, user.gravatar, user.avatar_date';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON (user.user_id = comment.user_id)';
			}

			if (XenForo_Application::getOptions()->cacheBbCodeTree && $fetchOptions['join'] & self::FETCH_BBCODE_CACHE)
			{
				$selectFields .= ',
					bb_code_parse_cache.parse_tree AS message_parsed, bb_code_parse_cache.cache_version AS message_cache_version';
				$joinTables .= '
					LEFT JOIN xf_bb_code_parse_cache AS bb_code_parse_cache ON
						(bb_code_parse_cache.content_type = \'team_comment\' AND bb_code_parse_cache.content_id = comment.comment_id)';
			}

			if($fetchOptions['join'] & self::FETCH_TEAM)
			{
				$fields = array(
					'team' => array(
						'title',
						'team_id',
						'user_id',
						'team_state',
						'privacy_state',
						'team_avatar_date'
					),
					'privacy' => array(
						'disable_tabs',
						'allow_guest_posting',
						'allow_member_posting',
					)
				);

				foreach($fields as $group => $columns)
				{
					foreach($columns as $columnName)
					{
						$selectFields .= ",$group.$columnName AS {$group}_{$columnName}";
					}
				}

				$joinTables .= '
					LEFT JOIN xf_team AS team ON (team.team_id = comment.team_id)
					LEFT JOIN xf_team_privacy AS privacy ON (privacy.team_id = team.team_id)';
			}

			if($fetchOptions['join'] & self::FETCH_CONTENT_POST)
			{
				$fields = array(
					'post_id',
					'user_id',
					'team_id',
					'username',
					'message_state',
					'share_privacy',
					'post_date'
				);

				foreach($fields as $field)
				{
					$selectFields .= ",post.{$field} AS post_{$field}";
				}

				$joinTables .= '
					LEFT JOIN xf_team_post AS post ON (
						post.post_id = comment.content_id AND comment.content_type = '. $db->quote(self::CONTENT_TYPE_POST) .'
					)';
			}

			if($fetchOptions['join'] & self::FETCH_CONTENT_EVENT)
			{
				$fields = array(
					'event_id',
					'event_title',
					'team_id',
					'user_id',
					'username',
					'event_type',
					'publish_date',
					'begin_date',
					'end_date',
					'allow_member_comment'
				);

				foreach($fields as $field)
				{
					$selectFields .= ",event.{$field} AS event_{$field}";
				}

				$joinTables .= '
					LEFT JOIN xf_team_event AS event ON (
						event.event_id = comment.content_id AND comment.content_type = '. $db->quote(self::CONTENT_TYPE_EVENT) .'
					)';
			}
		}

		if(isset($fetchOptions['likeUserId']))
		{
			if(empty($fetchOptions['likeUserId']))
			{
				$selectFields .= ',0 as like_date';
			}
			else
			{
				$selectFields .= ',liked_content.like_date';
				$joinTables .= '
					LEFT JOIN xf_liked_content AS liked_content ON (
						liked_content.content_id = comment.comment_id AND liked_content.content_type = \'team_comment\'
						AND liked_content.like_user_id = '. $db->quote($fetchOptions['likeUserId']) .'
					)';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables,
			'orderClause' => empty($orderClause) ? '' : "ORDER BY $orderClause"
		);
	}

	public function prepareComment(array $comment, array $team, array $category, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$comment['teamData'] = $this->_getTeamModel()->getTeamDataFromArray($comment);
		$comment['postData'] = $this->_getPostModel()->getPostDataFromArray($comment);
		$comment['eventData'] = $this->_getEventModel()->getEventDataFromArray($comment);

		$comment['likeUsers'] = unserialize($comment['like_users']);

		$comment['canEdit'] = $this->canEditComment($comment, $team, $category, $null, $viewingUser);
		$comment['canDelete'] = $this->canDeleteComment($comment, $team, $category, $null, $viewingUser);
		$comment['canLike'] = $this->canLikeComment($comment, $team, $category, $null, $viewingUser);

		return $comment;
	}

	public function canDeleteComment(array $comment, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deletePostAny'))
		{
			return true;
		}

		if($viewingUser['user_id'] == $comment['user_id']
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deletePostSelf')
		)
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($comment['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'deleteCommentAny');
	}

	public function canEditComment(array $comment, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editPostAny'))
		{
			return true;
		}

		if($comment['user_id'] == $viewingUser['user_id']
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editPostSelf'))
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($comment['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'editCommentAny');
	}

	public function canLikeComment(array $comment, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($comment['user_id'] == $viewingUser['user_id'])
		{
			return false;
		}

		return true;
	}

	public function canViewComment(array $comment, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if(!$this->_getTeamModel()->canViewTeamAndContainer($team, $category, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		if(XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editPostAny')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteCommentAny')
		)
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($comment['team_id'], $viewingUser);
		if($memberRecord)
		{
			if($this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'editCommentAny')
				|| $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'deleteCommentAny')
			)
			{
				return true;
			}
		}

		if($this->isTeamOwner($team, $viewingUser))
		{
			return true;
		}

		if($comment['content_type'] == self::CONTENT_TYPE_POST)
		{
			if(!isset($comment['postData']))
			{
				$comment['postData'] = $this->_getPostModel()->getPostDataFromArray($comment);
			}

			if($this->_getPostModel()->canViewPostAndContainer($comment['postData'], $team, $category, $errorPhraseKey, $viewingUser))
			{
				return true;
			}
		}
		elseif($comment['content_type'] == self::CONTENT_TYPE_EVENT)
		{
			if(!isset($comment['eventData']))
			{
				$comment['eventData'] = $this->_getEventModel()->getEventDataFromArray($comment);
			}

			if(empty($comment['eventData']))
			{
				return false;
			}

			if($this->_getEventModel()->canViewEventAndContainer($comment['eventData'], $team, $category, $errorPhraseKey, $viewingUser))
			{
				return true;
			}
		}

		return false;
	}

	public function attachCommentLoadingParams(array $content, array $comments, $contentKey, $contentType)
	{
		if(empty($comments) || !isset($content[$contentKey]))
		{
			return $content;
		}

		$firstComment = reset($comments);
		$lastComment = end($comments);

		$params = array(
			'content_id' => $content[$contentKey],
			'content_type' => $contentType,
			'content_key' => $contentKey,
		);

		$previousDate = false;
		$oldDate = false;

		if($firstComment['comment_date'] == $lastComment['comment_date'])
		{
			// It is mean there only 1 comments.
			if($content['last_comment_date'] > $firstComment['comment_date'])
			{
				// There still have more comments
				$previousDate = $firstComment['comment_date'];
			}

			if($content['first_comment_date'] < $lastComment['comment_date'])
			{
				$oldDate = $lastComment['comment_date'];
			}
		}
		else
		{
			if($firstComment['comment_id'] > $lastComment['comment_id'])
			{
				// Query order by recent comments. Or reserver array
				if($content['first_comment_date'] < $lastComment['comment_date'])
				{
					$previousDate = $lastComment['comment_date'];
				}

				if($content['last_comment_date'] > $firstComment['comment_date'])
				{
					$oldDate = $firstComment['comment_date'];
				}
			}
			else
			{
				// It is mean there only 1 comments.
				if($content['first_comment_date'] < $firstComment['comment_date'])
				{
					// There still have more comments
					$previousDate = $firstComment['comment_date'];
				}

				if($content['last_comment_date'] > $lastComment['comment_date'])
				{
					$oldDate = $lastComment['comment_date'];
				}
			}
		}

		if($previousDate)
		{
			$content['loadPreviousParams'] = $params;
		}

		if($oldDate)
		{
			$content['loadOldParams'] = $params;
		}

		return $content;
	}

	public function addCommentsToContentList(array $contentList, $lastCommentField = 'latest_comment_ids')
	{
		$commentIdMap = array();

		foreach($contentList as $contentId => &$content)
		{
			$latestCommentIds = json_decode($content[$lastCommentField], true);
			if(!is_array($latestCommentIds))
			{
				$latestCommentIds = array();
			}

			arsort($latestCommentIds);

			foreach($latestCommentIds as $commentId)
			{
				$commentIdMap[$commentId] = $contentId;
			}

			$content['comments'] = array();
		}

		if($commentIdMap)
		{
			$comments = $this->getCommentsByIds(array_keys($commentIdMap), array(
				'join' => self::FETCH_USER | self::FETCH_TEAM | self::FETCH_BBCODE_CACHE,
				'likeUserId' => XenForo_Visitor::getUserId(),
				'order' => 'comment_date',
				'direction' => 'desc'
			));

			foreach($commentIdMap as $commentId => $contentId)
			{
				$comment = $comments[$commentId];
				$contentList[$contentId]['comments'][$commentId] = $this->prepareComment($comment, $comment, $comment);
			}
		}

		foreach($contentList as $contentId => &$content)
		{
			$content['comments'] = array_splice($content['comments'], 0, 3, true);

			$contentType = $this->getContentTypeFromContentData($content);
			if($contentType)
			{
				$content = $this->attachCommentLoadingParams($content, $content['comments'], "{$contentType}_id", $contentType);
			}
			$content['comments'] = array_reverse($content['comments']);
		}

		return $contentList;
	}

	public function getContentTypeFromContentData(array $content)
	{
		if(isset($content['post_id']))
		{
			return self::CONTENT_TYPE_POST;
		}
		elseif(isset($content['event_id']))
		{
			return self::CONTENT_TYPE_EVENT;
		}

		return false;
	}

	public function sendNotificationsForPost(array $comment, array $post, array $team, array $noAlerts = array(), array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$postModel = $this->_getPostModel();
		$users = $postModel->getWatchers($post['post_id']);

		if(empty($users) || $comment['content_type'] != self::CONTENT_TYPE_POST)
		{
			return $noAlerts;
		}

		$comment['postData'] = $post;
		$comment['teamData'] = $team;

		$userModel = Nobita_Teams_Container::getModel('XenForo_Model_User');

		foreach($users as $user)
		{
			if($user['user_id'] == $comment['user_id']
				|| in_array($user['user_id'], $noAlerts)
			)
			{
				continue;
			}

			if(empty($user['send_alert']))
			{
				continue;
			}

			if($userModel->isUserIgnored($user, $comment['user_id']))
			{
				continue;
			}

			$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
			if(!$this->canViewComment($comment, $team, $team, $null, $user))
			{
				continue;
			}

			$noAlerts[] = $user['user_id'];
			XenForo_Model_Alert::alert($user['user_id'],
				$comment['user_id'], $comment['username'],
				'team_comment', $comment['comment_id'], 'insert'
			);
		}

		return $noAlerts;
	}

	public function alertTaggedMembers(array $comment, array $team, array $taggedUsers, array &$noAlerts = array())
	{
		$userIds = XenForo_Application::arrayColumn($taggedUsers, 'user_id');
		$userIds = array_diff($userIds, $noAlerts);

		if(!$userIds)
		{
			return;
		}

		$userModel = Nobita_Teams_Container::getModel('XenForo_Model_User');
		$memberModel = $this->_getMemberModel();

		$activeLimitOption = XenForo_Application::getOptions()->watchAlertActiveOnly;
		if (!empty($activeLimitOption['enabled']))
		{
			$activeLimit = XenForo_Application::$time - 86400 * $activeLimitOption['days'];
		}
		else
		{
			$activeLimit = 0;
		}

		$conditions = array(
			'user_id' => $userIds,
			'alert' => 1
		);

		if ($activeLimit)
		{
			$conditions['last_view_date'] = array('>=', $activeLimit);
		}

		$users = $memberModel->getAllMembersInTeam($team['team_id'], $conditions, array(
			'join' => Nobita_Teams_Model_Member::FETCH_USER
					| Nobita_Teams_Model_Member::FETCH_USER_PERMISSIONS
		));

		foreach($users as $user)
		{
			$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
			if ($userModel->isUserIgnored($user, $comment['user_id'])
				|| !XenForo_Model_Alert::userReceivesAlert($user, "team_comment", "tag")
			)
			{
				continue;
			}

			if (empty($user['send_alert']))
			{
				continue;
			}

			if($comment['user_id'] != $user['user_id'])
			{
				$noAlerts[] = $user['user_id'];

				XenForo_Model_Alert::alert($user['user_id'],
					$comment['user_id'], $comment['username'],
					'team_comment', $comment['comment_id'],
					 'tag'
				);
			}
		}
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}

	protected function _getPostModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
	}

	protected function _getEventModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
	}

	public function batchUpdateLikeUser($oldUserId, $newUserId, $oldUsername, $newUsername)
	{
		$db = $this->_getDb();

		$oldUserId = $db->quote($oldUserId);
		$newUserId = $db->quote($newUserId);

		// note that xf_liked_content should have already been updated with $newUserId

		$db->query('
			UPDATE xf_team_comment
			SET like_users = REPLACE(like_users, ' .
				$db->quote('i:' . $oldUserId . ';s:8:"username";s:' . strlen($oldUsername) . ':"' . $oldUsername . '";') . ', ' .
				$db->quote('i:' . $newUserId . ';s:8:"username";s:' . strlen($newUsername) . ':"' . $newUsername . '";') . ')
			WHERE comment_id IN (
				SELECT content_id FROM xf_liked_content
				WHERE content_type = \'team_comment\'
				AND like_user_id = ' . $newUserId . '
			)
		');
	}

}
