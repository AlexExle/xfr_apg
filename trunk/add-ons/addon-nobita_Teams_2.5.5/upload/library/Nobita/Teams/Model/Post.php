<?php

class Nobita_Teams_Model_Post extends Nobita_Teams_Model_Abstract
{
	const FETCH_POSTER = 0x02;
	const FETCH_TEAM = 0x04;
	const FETCH_MEMBER = 0x08;

	const FETCH_BBCODE_CACHE = 0x40;

	const SHARE_PUBLIC = 'public';
	const SHARE_MEMBER_ONLY = 'member';
	const SHARE_STAFF_ONLY = 'staff';

	public function getPostDataFromArray(array $data)
	{
		$collected = array();
		$prefix = 'post_';

		foreach(array_keys($data) as $dataKey) {
			if(strpos($dataKey, $prefix) === 0) {
				$postKey = substr($dataKey, strlen($prefix));

				$collected[$postKey] = $data[$dataKey];
			}
		}

		return $collected;
	}

	public function getSharePrivacyList()
	{
		return array(self::SHARE_PUBLIC, self::SHARE_MEMBER_ONLY, self::SHARE_STAFF_ONLY);
	}

	public function getSharePrivacy(array $team, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$privacy = array(
			self::SHARE_PUBLIC => new XenForo_Phrase('Teams_public')
		);

		if($this->isTeamMember($team['team_id'], $viewingUser))
		{
			$privacy[self::SHARE_MEMBER_ONLY] = new XenForo_Phrase('Teams_member_only');
		}

		if($this->isTeamAdmin($team['team_id'], $viewingUser))
		{
			$privacy[self::SHARE_STAFF_ONLY] = new XenForo_Phrase('Teams_staff_only');
		}

		return $privacy;
	}

	public function getPostShareVisibility(array $post)
	{
		if($post['share_privacy'] == 'public') 
		{
			return new XenForo_Phrase('Teams_public');
		}

		return new XenForo_Phrase(sprintf('Teams_%s_only', $post['share_privacy']));
	}

	public function getPostById($postId, array $fetchOptions = array())
	{
		$joinOptions = $this->preparePostFetchOptions($fetchOptions);
		return $this->_getDb()->fetchRow('
			SELECT post.*
				' . $joinOptions['selectFields'] . '
			FROM xf_team_post AS post
				' . $joinOptions['joinTables'] . '
			WHERE post.post_id = ?
		', $postId);
	}

	public function getPostsByIds(array $postIds, array $fetchOptions = array())
	{
		if (empty($postIds))
		{
			return array();
		}
		$joinOptions = $this->preparePostFetchOptions($fetchOptions);

		return $this->fetchAllKeyed(
			'
				SELECT post.*
					' . $joinOptions['selectFields'] . '
				FROM xf_team_post AS post
					' . $joinOptions['joinTables'] . '
				WHERE post.post_id IN (' . $this->_getDb()->quote($postIds) . ')
		', 'post_id');
	}

	public function getPostsForTeamId($teamId, array $conditions = array(), array $fetchOptions = array())
	{
		$conditions['team_id'] = $teamId;
		$fetchOptions = array_merge(array(
			'order' => 'last_comment_date',
			'direction' => 'desc'
		), $fetchOptions);

		return $this->getPosts($conditions, $fetchOptions);
	}

	public function getPosts(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause = $this->preparePostConditions($conditions);

		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		$joinOptions = $this->preparePostFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			"
				SELECT post.*
					$joinOptions[selectFields]
				FROM xf_team_post AS post
					$joinOptions[joinTables]
				WHERE $whereClause
				 $joinOptions[orderClause]
			", $limitOptions['limit'], $limitOptions['offset'])
		, 'post_id');
	}

	public function countPostsForTeamId($teamId, array $conditions)
	{
		$fetchOptions = array();
		$whereClause = $this->preparePostConditions($conditions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_team_post as post
			WHERE ' . $whereClause . '
				AND post.team_id = ?
		', $teamId);
	}

	public function getPostIdsByUser($userId, $teamId)
	{
		return $this->_getDb()->fetchCol('
			SELECT post_id
			FROM xf_team_post
			WHERE user_id = ?
				AND team_id = ?
		', array($userId, $teamId));
	}

	public function getPostIdsInRange($start, $limit)
	{
		$db = $this->_getDb();

		return $db->fetchCol($db->limit('
			SELECT post_id
			FROM xf_team_post
			WHERE post_id > ?
			ORDER BY post_id
		', $limit), $start);
	}

	public function preparePostFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$orderClause = '';

		$orderDirection = isset($fetchOptions['direction']) ? $fetchOptions['direction'] : 'asc';
		if(isset($fetchOptions['order']))
		{
			switch($fetchOptions['order'])
			{
				case 'post_date':
				case 'comment_count':
				case 'likes':
				case 'first_comment_date':
				case 'last_comment_date':
					$orderClause = "post.$fetchOptions[order] $orderDirection";
					break;
			}
		}

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_POSTER)
			{
				$selectFields .=',
					poster.*,IF(poster.username IS NULL, post.username, poster.username) AS username';
				$joinTables .='
					LEFT JOIN xf_user AS poster ON (poster.user_id = post.user_id)';
			}

			if (XenForo_Application::getOptions()->cacheBbCodeTree && $fetchOptions['join'] & self::FETCH_BBCODE_CACHE)
			{
				$selectFields .= ',
					bb_code_parse_cache.parse_tree AS message_parsed, bb_code_parse_cache.cache_version AS message_cache_version';
				$joinTables .= '
					LEFT JOIN xf_bb_code_parse_cache AS bb_code_parse_cache ON
						(bb_code_parse_cache.content_type = \'team_post\' AND bb_code_parse_cache.content_id = post.post_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_TEAM)
			{
				$selectFields .=',' . $this->_getTeamModel()->getSimpleTeamColumns() . ',team_privacy.*';
				$joinTables .='
					LEFT JOIN xf_team AS team ON (team.team_id = post.team_id)
					LEFT JOIN xf_team_privacy AS team_privacy ON (team_privacy.team_id = team.team_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_MEMBER)
			{
				$selectFields .=',
					member.*';
				$joinTables .='
					LEFT JOIN xf_team_member AS member ON (member.user_id = post.user_id AND member.team_id = post.team_id)';
			}
		}

		if (isset($fetchOptions['watchUserId']))
		{
			if (empty($fetchOptions['watchUserId']))
			{
				$selectFields .=',0 AS watch_user_id';
			}
			else
			{
				$selectFields .=',
					post_watch.user_id AS watch_user_id';
				$joinTables .='
					LEFT JOIN xf_team_post_watch AS post_watch ON (
						post_watch.post_id = post.post_id
						AND post_watch.user_id = ' . $this->_getDb()->quote($fetchOptions['watchUserId']) . ')';
			}
		}

		if (isset($fetchOptions['likeUserId']))
		{
			if (empty($fetchOptions['likeUserId']))
			{
				$selectFields .= ',
					0 AS like_date';
			}
			else
			{
				$selectFields .= ',
					liked_content.like_date';
				$joinTables .= '
					LEFT JOIN xf_liked_content AS liked_content
						ON (liked_content.content_type = \'team_post\'
							AND liked_content.content_id = post.post_id
							AND liked_content.like_user_id = ' .$this->_getDb()->quote($fetchOptions['likeUserId']) . ')';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables,
			'orderClause' => empty($orderClause) ? '' : "ORDER BY $orderClause",
		);
	}

	public function preparePostConditions(array $conditions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		foreach(array('post_id', 'team_id', 'user_id', 'message_state', 'share_privacy') as $field)
		{
			if(empty($conditions[$field])) continue;

			$value = $db->quote($conditions[$field]);
			if(is_array($conditions[$field]))
			{
				$sqlConditions[] = "post.{$field} IN ({$value})";
			}
			else
			{
				$sqlConditions[] = "post.{$field} = $value";
			}
		}

		if (!empty($conditions['last_comment_date']) && is_array($conditions['last_comment_date']))
		{
			$sqlConditions[] = $this->getCutOffCondition("post.last_comment_date", $conditions['last_comment_date']);
		}

		if (!empty($conditions['attach_count']) && is_array($conditions['attach_count']))
		{
			$sqlConditions[] = $this->getCutOffCondition("post.attach_count", $conditions['attach_count']);
		}

		if (isset($conditions['sticky']))
		{
			$sqlConditions[] = 'post.sticky = ' . $db->quote($conditions['sticky'] ? 1 : 0);
		}

		if (isset($conditions['moderated']))
		{
			$sqlConditions[] = $this->prepareStateLimitFromConditions($conditions, 'post', 'message_state');
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function getPermissionBasedPostConditions(array $team, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$viewModerated = false;
		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		$memberRoleModel = $this->_getMemberRoleModel();

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'viewModeratedPost'))
		{
			$viewModerated = true;
		}
		elseif ($memberRecord)
		{
			if($memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'editPostAny')
				|| $memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'deletePostAny')
			)
			{
				$viewModerated = true;
			}

		}

		return array(
			'moderated' => $viewModerated
		);
	}

	public function preparePost(array $post, array $team, array $category, $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!isset($post['canInlineMod']))
		{
			$this->addInlineModOptionToPost($post, $team, $category, $viewingUser);
		}

		$post['shareVisibility'] = $this->getPostShareVisibility($post);

		$post['canReport'] = Nobita_Teams_Container::getModel('XenForo_Model_User')->canReportContent();
		$post['canDelete'] = $this->canDeletePost($post, $team, $category, $null, $viewingUser);
		$post['canApprove'] = $this->canApproveUnapprove($post, $team, $category, $null, $viewingUser);
		$post['canEdit'] = $this->canEditPost($post, $team, $category, $null, $viewingUser);
		$post['canComment'] = $this->canCommentOnPost($post, $team, $category, $null, $viewingUser);
		$post['canLike'] = $this->canLikePost($post, $team, $category, $null, $viewingUser);
		$post['canStickUnstick'] = $this->canStickUnstickPost($post, $team, $category, $null, $viewingUser);

		$post['likeUsers'] = unserialize($post['like_users']);

		$banningModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Banning');
		$banningModel->prepareContent($post, $team, $category, $null, $viewingUser);
		$post['banningInfo'] = array(
			'banning_id' => $banningModel->generateBanningKey($post['team_id'], 'post', $post['user_id'])
		);

		if(isset($post['latest_comment_ids']))
		{
			$post['latestCommentIds'] = @json_decode($post['latest_comment_ids'], true);
		}

		$post['isModerated'] = $this->isModerated($post, $team);
		return $post;
	}

	public function preparePosts(array $posts, array $team, array $category, $viewingUser = null)
	{
		foreach ($posts as &$post)
		{
			$post = $this->preparePost($post, $team, $category, $viewingUser);
		}

		return $posts;
	}

	/**
	 * Gets the attachments that belong to the given posts, and merges them in with
	 * their parent post (in the attachments key). The attachments key will not be
	 * set if no attachments are found for the post.
	 *
	 * @param array $posts
	 *
	 * @return array Posts, with attachments added where necessary
	 */
	public function getAndMergeAttachmentsIntoPosts(array $posts)
	{
		$postIds = array();

		foreach ($posts AS $postId => $post)
		{
			if ($post['attach_count'])
			{
				$postIds[] = $postId;
			}
		}

		if ($postIds)
		{
			$attachmentModel = $this->_getAttachmentModel();

			$attachments = $attachmentModel->getAttachmentsByContentIds('team_post', $postIds);

			foreach ($attachments AS $attachment)
			{
				$posts[$attachment['content_id']]['attachments'][$attachment['attachment_id']] = $attachmentModel->prepareAttachment($attachment);
			}
		}

		return $posts;
	}

	public function getAndMergeAttachmentsIntoPost(array $post)
	{
		$attachmentModel = $this->_getAttachmentModel();

		$attachments = $attachmentModel->getAttachmentsByContentIds('team_post', array($post['post_id']));
		foreach ($attachments AS $attachment)
		{
			$post['attachments'][$attachment['attachment_id']] = $attachmentModel->prepareAttachment($attachment);
		}

		return $post;
	}

	public function canViewAttachmentOnPost(array $post, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		return $this->_getTeamModel()->canViewAttachments($team, $category, $errorPhraseKey, $viewingUser);
	}

	public function canLikePost(array $post, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($post['user_id'] == $viewingUser['user_id'])
		{
			$errorPhraseKey = 'liking_own_content_cheating';
			return false;
		}

		if ($post['message_state'] != 'visible')
		{
			return false;
		}

		return true;
	}

	public function canCommentOnPost(array $post, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		return $this->_getTeamModel()->canPostOnTeam($team, $team, $errorPhraseKey, $viewingUser);
	}

	public function canDeletePost(array $post, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
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

		if($viewingUser['user_id'] == $post['user_id']
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deletePostSelf')
		)
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'deletePostAny');
	}

	public function canEditPost(array $post, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
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

		if($viewingUser['user_id'] == $post['user_id']
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editPostSelf')
		)
		{
			return false;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'editPostAny');
	}

	public function canViewPost(array $post, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$teamModel = $this->_getTeamModel();
		if (!$teamModel->canViewTeamAndContainer($team, $category, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editPostAny'))
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		$memberRoleModel = $this->_getMemberRoleModel();

		if($memberRecord)
		{
			if($memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'deletePostAny')
				|| $memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'editPostAny')
			)
			{
				return true;
			}
		}

		if ($this->isModerated($post, $team))
		{
			if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'viewModeratedPost'))
			{
				if (!$viewingUser['user_id'] || !$viewingUser['user_id'] != $post['user_id'])
				{
					return false;
				}
			}
		}

		switch($post['share_privacy'])
		{
			case self::SHARE_PUBLIC:
				return true;
			case self::SHARE_MEMBER_ONLY:
				return $this->isTeamMember($team['team_id'], $viewingUser);
			case self::SHARE_STAFF_ONLY:
				return $this->isTeamAdmin($team['team_id'], $viewingUser);
			default:
				return false;
		}
	}

	public function canViewPostAndContainer(array $post, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		if (!$this->_getTeamModel()->canViewTeamAndContainer($team, $category, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		return $this->canViewPost($post, $team, $category, $errorPhraseKey, $viewingUser);
	}

	public function canApproveUnapprove(array $post, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'approveUnapprovePost'))
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if($memberRecord)
		{
			if($this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'approveUnapprovePost'))
			{
				return true;
			}
		}

		return $this->isTeamOwner($team, $viewingUser); // owner can approve post!
	}

	public function canStickUnstickPost(array $post, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'stickUnstickPost'))
		{
			return true;
		}

		if ($this->isTeamOwner($team, $viewingUser))
		{
			return true; //owner team.
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'stickUnstickPost');
	}

	public function deletePost(array $post)
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Post');

		$dw->setExistingData($post);
		$dw->delete();
	}

	public function addInlineModOptionToPost(array &$post, array $team, array $category, $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$options = array();
		if (!$viewingUser['user_id'])
		{
			return $options;
		}

		$memberModel = $this->_getMemberModel();
		$memberRoleModel = $this->_getMemberRoleModel();

		$canInlineMod = false;

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deletePostAny')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'approveUnapprove')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'stickUnstickPost'))
		{
			$canInlineMod = true;
		}
		elseif($memberRecord)
		{
			if($memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'editPostAny')
				|| $memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'deletePostAny')
				|| $memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'approveUnapprovePost')
				|| $memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'stickUnstickPost')
			)
			{
				$canInlineMod = true;
			}
		}

		if ($canInlineMod)
		{
			if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deletePostAny')
				|| ($memberRecord && $memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'deletePostAny'))
			)
			{
				$options['delete'] = true;
			}

			if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'approveUnapprove')
				|| ($memberRecord && $memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'approveUnapprovePost'))
			)
			{
				$options['approve'] = true;
				$options['unapprove'] = true;
			}

			if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'stickUnstickPost')
				|| ($memberRecord && $memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'stickUnstickPost'))
				)
			{
				$options['stick'] = true;
				$options['unstick'] = true;
			}
		}

		$post['canInlineMod'] = (count($options) > 0);
		return $options;
	}

	public function addInlineModOptionToPosts(array &$posts, array $team, array $category, $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$options = array();
		foreach ($posts AS &$post)
		{
			$options += $this->addInlineModOptionToPost($post, $team, $viewingUser);
		}

		return $options;
	}

	public function isModerated(array $post, array $team)
	{
		if (!isset($post['message_state']))
		{
			return false;
			//throw new XenForo_Exception('Message state not available in post.');
		}

		return ($post['message_state'] == 'moderated');
	}

	public function isVisible(array $post)
	{
		if (!isset($post['message_state']))
		{
			return false;
			//throw new XenForo_Exception('Message state not available in post.');
		}

		return ($post['message_state'] == 'visible');
	}

	public function alertTaggedMembers(array $post, array $team, array $tagged, array $alreadyAlerted)
	{
		$userIds = XenForo_Application::arrayColumn($tagged, 'user_id');
		$userIds = array_diff($userIds, $alreadyAlerted);
		$alertedUserIds = array();

		if ($userIds)
		{
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

			foreach ($users as $user)
			{
				$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
				if ($userModel->isUserIgnored($user, $post['user_id'])
					|| !XenForo_Model_Alert::userReceivesAlert($user, "team_post", "tag")
				)
				{
					continue;
				}

				if (empty($user['send_alert']))
				{
					continue;
				}

				if (!isset($alertedUserIds[$user['user_id']]) && $post['user_id'] != $user['user_id'])
				{
					if ($this->canViewPostAndContainer($post, $team, $team, $null, $user))
					{
						$alertedUserIds[$user['user_id']] = true;

						XenForo_Model_Alert::alert($user['user_id'],
							$post['user_id'], $post['username'],
							'team_post', $post['post_id'],
							 'tag'
						);
					}
				}
			}
		}

		return array_keys($alertedUserIds);
	}


	protected static $_preventDoubleNotify = array();
	public function sendNotificationsToUser(array $post, array $team = null, array $noAlerts = array())
	{
		if ($post['message_state'] != 'visible')
		{
			return array();
		}

		if (!$team)
		{
			$team = $this->_getTeamModel()->getFullTeamById($post['team_id'], array(
				'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
			));
		}

		if (!$team || $team['team_state'] != 'visible')
		{
			return array();
		}

		if (XenForo_Application::get('options')->emailWatchedThreadIncludeMessage)
		{
			$parseBbCode = true;
			$emailTemplate = 'Team_user_watched_team_messagetext';
		}
		else
		{
			$parseBbCode = false;
			$emailTemplate = 'Team_user_watched_team';
		}

		$userModel = Nobita_Teams_Container::getModel('XenForo_Model_User');
		$memberModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');

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
			'alert' => 1,
			'member_state' => 'accept'
		);

		if ($activeLimit)
		{
			$conditions['last_view_date'] = array('>=', $activeLimit);
		}

		$usersWatch = $memberModel->getAllMembersInTeam($team['team_id'], $conditions, array(
			'join' => Nobita_Teams_Model_Member::FETCH_USER
					 | Nobita_Teams_Model_Member::FETCH_USER_PERMISSIONS
		));

		// fetch a full user record if we don't have one already
		if (!isset($post['avatar_width']) || !isset($post['custom_title']))
		{
			$replyUser = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserById($post['user_id']);
			if ($replyUser)
			{
				$post = array_merge($replyUser, $post);
			}
			else
			{
				$post['avatar_width'] = 0;
				$post['custom_title'] = '';
			}
		}

		$alerted = array();
		$emailed = array();

		foreach ($usersWatch as $user)
		{
			$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
			if ($user['user_id'] == $post['user_id'])
			{
				continue;
			}

			if ($userModel->isUserIgnored($user, $post['user_id']))
			{
				continue;
			}

			if (!$this->_getTeamModel()->canViewTeamAndContainer($team, $team, $null, $user))
			{
				continue;
			}

			if (isset(self::$_preventDoubleNotify[$team['team_id']][$user['user_id']]))
			{
				continue;
			}
			self::$_preventDoubleNotify[$team['team_id']][$user['user_id']] = true;

			if ($user['email'] && $user['user_state'] == 'valid'
				&& $user['send_email']
			)
			{
				if (!isset($post['messageText']) && $parseBbCode)
				{
					$bbCodeParserText = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Text'));
					$post['messageText'] = new XenForo_BbCode_TextWrapper($post['message'], $bbCodeParserText);

					$bbCodeParserHtml = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('HtmlEmail'));
					$post['messageHtml'] = new XenForo_BbCode_TextWrapper($post['message'], $bbCodeParserHtml);
				}

				if (!isset($team['titleCensored']))
				{
					$team['titleCensored'] = XenForo_Helper_String::censorString($team['title']);
				}

				$user['email_confirm_key'] = $userModel->getUserEmailConfirmKey($user);

				$mail = XenForo_Mail::create($emailTemplate, array(
					'post' => $post,
					'team' => $team,
					'category' => $team,
					'receiver' => $user
				), $user['language_id']);
				$mail->enableAllLanguagePreCache();
				$mail->queue($user['email'], $user['username']);

				$emailed[] = $user['user_id'];
			}

			if (!in_array($user['user_id'], $noAlerts)
				&& $user['send_alert']
			)
			{
				if (XenForo_Model_Alert::userReceivesAlert($user, 'team_post', 'insert'))
				{
					XenForo_Model_Alert::alert($user['user_id'],
						$post['user_id'], $post['username'],
						'team_post', $post['post_id'],
						'insert'
					);

					$alerted[] = $user['user_id'];
				}
			}
		}

		return array(
			'emailed' => $emailed,
			'alerted' => $alerted
		);
	}

	public function sendNotificationsToModerators(array $post, array $team)
	{
		$memberRoleIds = array();

		$memberRoleModel = $this->_getMemberRoleModel();
		$memberRoles = $memberRoleModel->getMemberRolesFromCache();

		foreach($memberRoles as $memberRoleId => $memberRole)
		{
			if($memberRoleModel->hasModeratorPermission($memberRoleId, 'editPostAny')
				|| $memberRoleModel->hasModeratorPermission($memberRoleId, 'deletePostAny')
				|| $memberRoleModel->hasModeratorPermission($memberRoleId, 'approveUnapprovePost')
			)
			{
				$memberRoleIds[] = $memberRoleId;
			}
		}

		if(empty($memberRoleIds))
		{
			return array();
		}

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
			'member_role_id' => $memberRoleIds,
			'alert' => 1,
			'member_state' => 'accept'
		);

		if ($activeLimit)
		{
			$conditions['last_view_date'] = array('>=', $activeLimit);
		}
		$members = $this->_getMemberModel()->getMembersByTeamId($team['team_id'], $conditions);

		if (empty($members))
		{
			return array();
		}

		$userModel = Nobita_Teams_Container::getModel('XenForo_Model_User');

		foreach ($members as $userId => $user) {
			if($user['user_id'] == $post['user_id'])
			{
				continue;
			}

			if($userModel->isUserIgnored($user, $post['user_id']))
			{
				continue;
			}

			if (XenForo_Model_Alert::userReceivesAlert($user, 'team_post', 'insert'))
			{
				// send alert
				XenForo_Model_Alert::alert($user['user_id'],
					$post['user_id'], $post['username'],
					'team_post', $post['post_id'],
					'insert'
				);
			}
		}
	}

	/**
	 * Attempts to update any instances of an old username in like_users with a new username
	 *
	 * @param integer $oldUserId
	 * @param integer $newUserId
	 * @param string $oldUsername
	 * @param string $newUsername
	 */
	public function batchUpdateLikeUser($oldUserId, $newUserId, $oldUsername, $newUsername)
	{
		$db = $this->_getDb();

		$oldUserId = $db->quote($oldUserId);
		$newUserId = $db->quote($newUserId);

		// note that xf_liked_content should have already been updated with $newUserId

		$db->query('
			UPDATE xf_team_post
			SET like_users = REPLACE(like_users, ' .
				$db->quote('i:' . $oldUserId . ';s:8:"username";s:' . strlen($oldUsername) . ':"' . $oldUsername . '";') . ', ' .
				$db->quote('i:' . $newUserId . ';s:8:"username";s:' . strlen($newUsername) . ':"' . $newUsername . '";') . ')
			WHERE post_id IN (
				SELECT content_id FROM xf_liked_content
				WHERE content_type = \'team_post\'
				AND like_user_id = ' . $newUserId . '
			)
		');
	}

	public function getWatchRecord($postId, $userId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_team_post_watch
			WHERE post_id = ? AND user_id = ?
		', array($postId, $userId));
	}

	public function watch($postId, $userId)
	{
		try
		{
			$this->_getDb()->query('
				INSERT IGNORE INTO xf_team_post_watch (post_id, user_id)
				VALUES
					(?, ?)
			', array($postId, $userId));
		}
		catch (Zend_Db_Exception $e) {}
	}

	public function unwatch($postId, $userId)
	{
		$db = $this->_getDb();

		try
		{
			$this->_getDb()->delete('xf_team_post_watch', 'post_id = ' . $db->quote($postId) . ' AND user_id = ' . $db->quote($userId));
		}
		catch (Zend_Db_Exception $e) {}
	}

	public function getWatchers($postId)
	{
		return $this->fetchAllKeyed('
			SELECT user.*, member.*,
				permission_combination.cache_value AS global_permission_cache
			FROM xf_team_post_watch as post_watch
				INNER JOIN xf_user AS user ON (user.user_id = post_watch.user_id)
				LEFT JOIN xf_team_member AS member ON (member.user_id = post_watch.user_id)
				LEFT JOIN xf_permission_combination AS permission_combination ON
						(permission_combination.permission_combination_id = user.permission_combination_id)
			WHERE post_watch.post_id = ?
		', 'user_id', $postId);
	}

	protected function _getAttachmentModel()
	{
		return Nobita_Teams_Container::getModel('XenForo_Model_Attachment');
	}
}
