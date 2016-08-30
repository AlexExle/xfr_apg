<?php

class Nobita_Teams_Model_Member extends Nobita_Teams_Model_Abstract
{
	const FETCH_USER 				= 0x01;
	const FETCH_USER_PERMISSIONS 	= 0x02;
	const FETCH_TEAM 				= 0x04;
	const FETCH_MEMBER_ROLE 		= 0x08;
	const FETCH_TEAM_FULL			= 0x20;
	const FETCH_MEMBER_BAN			= 0x40;

	public static $memberDataColumns = array(
		'user_id' 		=> 'member_user_id',
		'team_id' 		=> 'member_team_id',
		'member_state' 	=> 'member_member_state',
		'member_role_id' 		=> 'member_member_role_id',
		'alert' 		=> 'member_alert',
		'send_alert' 	=> 'member_send_alert',
		'send_email' 	=> 'member_send_email',
		'action'		=> 'member_action',
		'action_username' => 'member_action_username',
		'action_user_id' => 'member_action_userid',
		'last_view_date' => 'member_last_view_date'
	);

	public function getRecordByKeys($userId, $teamId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareMemberFetchOptions($fetchOptions);

		$record = $this->_getDb()->fetchRow("
			SELECT team_member.*
				$joinOptions[selectFields]
			FROM xf_team_member as team_member
				$joinOptions[joinTables]
			WHERE team_member.user_id = ?
				AND team_member.team_id = ?
		", array($userId, $teamId));

		return (!empty($record)) ? $record : array();
	}

	public function countAllTeamsForUser($userId)
	{
		return $this->countMembers(array('user_id' => $userId));
	}

	public function getMembersByTeamId($teamId, array $conditions = array(), array $fetchOptions = array())
	{
		$conditions['team_id'] = $teamId;
		$this->addFetchOptionJoin($fetchOptions, self::FETCH_MEMBER_ROLE);

		return $this->getMembers($conditions, $fetchOptions);
	}

	public function countMembersInTeam($teamId, array $conditions = array())
	{
		$conditions['team_id'] = $teamId;
		return $this->countMembers($conditions);
	}

	public function getMembers(array $conditions, array $fetchOptions)
	{
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		$orderClause = $this->prepareMemberOrderFetchOptions($fetchOptions);

		$whereClause = $this->prepareMemberConditions($conditions);
		$joinOptions = $this->prepareMemberFetchOptions($fetchOptions);

		return $this->_getDb()->fetchAll($this->limitQueryResults("
			SELECT team_member.*
				$joinOptions[selectFields]
			FROM xf_team_member AS team_member
				$joinOptions[joinTables]
			WHERE $whereClause
				$orderClause
		", $limitOptions['limit'], $limitOptions['offset']));
	}

	public function countMembers(array $conditions)
	{
		$whereClause = $this->prepareMemberConditions($conditions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM xf_team_member AS team_member
			WHERE $whereClause
		");
	}

	public function getAllMembersInTeam($teamId, array $conditions = array(), array $fetchOptions = array())
	{
		$conditions['team_id'] = $teamId;
		$this->addFetchOptionJoin($fetchOptions, self::FETCH_MEMBER_ROLE);
		$fetchOptions['order'] = 'join_date';

		return $this->getMembers($conditions, $fetchOptions);
	}

	public function getTeamIdsByUserId($userId, array $conditions = array(), array $fetchOptions = array())
	{
		$conditions['user_id'] = $userId;

		$whereClause = $this->prepareMemberConditions($conditions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$orderClause = $this->prepareMemberOrderFetchOptions($fetchOptions, 'team_member.team_id');
		$joinOptions = $this->prepareMemberFetchOptions($fetchOptions);

		return $this->_getDb()->fetchCol($this->limitQueryResults("
			SELECT team_member.team_id
			FROM xf_team_member as team_member
				$joinOptions[joinTables]
			WHERE $whereClause
				$orderClause
		", $limitOptions['limit'], $limitOptions['offset']));
	}

	public function getAllTeamsUserJoined($userId)
	{
		$conditions = array('user_id' => $userId);
		$fetchOptions = array('join' => self::FETCH_TEAM | self::FETCH_MEMBER_ROLE);

		return $this->getMembers($conditions, $fetchOptions);
	}

	public function getTeamIdsByUserIdsAndNotIn(array $excludeTeamIds, array $userIds)
	{
		$whereClause = array();
		$db = $this->_getDb();

		if (!empty($excludeTeamIds))
		{
			$whereClause[] = 'team_id NOT IN (' . $db->quote($excludeTeamIds) . ')';
		}

		if (!empty($userIds))
		{
			$whereClause[] = 'user_id IN (' . $db->quote($userIds) . ')';
		}

		$whereClause = $this->getConditionsForClause($whereClause);

		return $this->_getDb()->fetchCol('
			SELECT team_id
			FROM xf_team_member
			WHERE ' . $whereClause . '
			ORDER BY RAND()
			LIMIT 50
		');
	}

	public function getTeamIdsByPendingForUser($userId)
	{
		return $this->_getDb()->fetchCol('
			SELECT team_id
			FROM xf_team_member
			WHERE user_id = ? AND action <> ? AND member_state = ?
		', array($userId, 'invite', 'request'));
	}

	public function getTeamIdsByInvitedForUser($userId)
	{
		return $this->_getDb()->fetchCol('
			SELECT team_id
			FROM xf_team_member
			WHERE user_id = ? AND action = ? AND member_state = ?
		', array($userId, 'invite', 'request'));
	}

	public function prepareMemberConditions(array $conditions = array())
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		$fields = array(
			'user_id', 'member_state', 'action', 'member_role_id', 'team_id'
		);

		foreach($fields as $fieldKey)
		{
			if(empty($conditions[$fieldKey]))
			{
				continue;
			}

			$value = $conditions[$fieldKey];
			$value = is_array($value) ? $value : array($value);
			$quoted = $db->quote($value);

			$sqlConditions[] = "team_member.{$fieldKey} IN ({$quoted})";
		}

		$fields = array(
			'not_in_position' => 'member_role_id',
			'not_in_action' => 'action'
		);

		foreach($fields as $fieldKey => $fieldName)
		{
			if(empty($conditions[$fieldKey]))
			{
				continue;
			}

			$value = $conditions[$fieldKey];
			$value = is_array($value) ? $value : array($value);
			$quoted = $db->quote($value);

			$sqlConditions[] = "team_member.{$fieldName} NOT IN ({$quoted})";
		}
		unset($fields);

		// damn! should be check isset or not.
		// cause that alert = 0 still valid
		if (isset($conditions['alert']))
		{
			$sqlConditions[] = 'team_member.alert = ' . $db->quote($conditions['alert']);
		}

		// for find member in team.
		if (!empty($conditions['username']))
		{
			if (is_array($conditions['username']))
			{
				$sqlConditions[] = 'team_member.username LIKE ' . XenForo_Db::quoteLike($conditions['username'][0], $conditions['username'][1], $db);
			}
			else
			{
				$sqlConditions[] = 'team_member.username LIKE ' . XenForo_Db::quoteLike($conditions['username'], 'lr', $db);
			}
		}

		if (!empty($conditions['last_seen_date_bw']) && is_array($conditions['last_seen_date_bw']))
		{
			$sqlConditions[] = sprintf('team_member.last_view_date BETWEEN %d AND %d',
				$db->quote($conditions['last_seen_date_bw'][0]), $db->quote($conditions['last_seen_date_bw'][1])
			);
		}

		if (!empty($conditions['last_seen_date_lt']))
		{
			$sqlConditions[] = 'team_member.last_view_date < ' . $db->quote($conditions['last_seen_date_lt']);
		}

		if (!empty($conditions['last_view_date']) AND is_array($conditions['last_view_date']))
		{
			$sqlConditions[] = $this->getCutOffCondition('team_member.last_view_date', $conditions['last_view_date']);
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareMemberFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_USER)
			{
				$selectFields .=',user.*, IF(user.username IS NULL, team_member.username, user.username) AS username, user_profile.custom_fields AS user_custom_fields';
				$joinTables .='
						LEFT JOIN xf_user AS user ON (user.user_id = team_member.user_id)
						LEFT JOIN xf_user_profile AS user_profile ON (user_profile.user_id = user.user_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_USER && $fetchOptions['join'] & self::FETCH_USER_PERMISSIONS)
			{
				$selectFields .= ',
					permission_combination.cache_value AS global_permission_cache';
				$joinTables .= '
					LEFT JOIN xf_permission_combination AS permission_combination ON
						(permission_combination.permission_combination_id = user.permission_combination_id)';
			}

			if($fetchOptions['join'] & self::FETCH_TEAM)
			{
				$selectFields .= ',team.team_id, team.title, team.team_avatar_date, team.team_state,
									team.privacy_state, team.user_id as team_user_id, team.username as team_username';
				$joinTables .= '
					LEFT JOIN xf_team AS team ON
						(team.team_id = team_member.team_id)';
			}

			if($fetchOptions['join'] & self::FETCH_TEAM_FULL)
			{
				$selectFields .= ',team.team_id, team.title, team.team_avatar_date, team.team_state,
									team.privacy_state, team.user_id as team_user_id, team.username as team_username,
									team.warning_id, team.custom_url, team.last_updated, team.tag_line,
									privacy.*,profile.*,category.category_title,category.team_category_id,
									feature.feature_date';
				$joinTables .= '
					LEFT JOIN xf_team AS team ON (team.team_id = team_member.team_id)
					LEFT JOIN xf_team_privacy AS privacy ON (privacy.team_id = team.team_id)
					LEFT JOIN xf_team_profile AS profile ON (profile.team_id = team.team_id)
					LEFT JOIN xf_team_category AS category ON (category.team_category_id = team.team_category_id)
					LEFT JOIN xf_team_feature AS feature ON (feature.team_id = team.team_id)
				';
			}

			if($fetchOptions['join'] & self::FETCH_MEMBER_ROLE)
			{
				$selectFields .= ',member_role.*';
				$joinTables .= '
					LEFT JOIN xf_team_member_role AS member_role ON
						(member_role.member_role_id = team_member.member_role_id)';
			}

			if($fetchOptions['join'] & self::FETCH_MEMBER_BAN)
			{
				$selectFields .= ',banning.ban_date,banning.end_date,banning.user_reason';
				$joinTables .= '
					LEFT JOIN xf_team_ban AS banning ON (
						banning.user_id = team_member.user_id AND banning.team_id = team_member.team_id
					)';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables
		);
	}

	public function prepareMemberOrderFetchOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'alphabetical' 		=> 'team_member.username',
			'date'		 		=> 'team_member.join_date',
			'last_view_date' 	=> 'team_member.last_view_date',
			'join_date' 		=> 'team_member.join_date',

			// You need join to table xf_team to order this fields
			'team_last_updated' => 'team.last_updated'
		);

		if(isset($fetchOptions['order']) && $fetchOptions['order'] == 'team_last_updated')
		{
			$this->addFetchOptionJoin($fetchOptions, self::FETCH_TEAM);
		}

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

	public function prepareMember(array $member, array $team, array $viewingUser = null, array $groupsCache = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if ($groupsCache === null)
		{
			$groupsCache = $this->_getMemberRoleModel()->getMemberRolesFromCache();
		}

		if ($team)
		{
			$member['canPromote'] = $this->canPromoteMember($member, $team, $null, $viewingUser);
			$member['canRemove'] = $this->canRemoveMember($member, $team, $null, $viewingUser);

			$member['canBanUser'] = $this->getBanningModel()->canBanUser($member, $team);
			$member['banningInfo'] = array(
				'banning_id' => $this->getBanningModel()->generateBanningKey($member['team_id'], 'list', $member['user_id'])
			);
		}
		else
		{
			$member['canPromote'] = false;
			$member['canRemove'] = false;
		}

		if (isset($member['member_role_id']) AND isset($groupsCache[$member['member_role_id']]))
		{
			$member['memberRolePhrase'] = $groupsCache[$member['member_role_id']]['memberRoleTitle'];
		}

		if ($member['action'] == "add")
		{
			$member['actionPhrase'] = new XenForo_Phrase('Teams_added_by_x', array(
				'name' => $member['action_username']
			));
		}
		else if ($member['action'] == "promote")
		{
			$member['actionPhrase'] = new XenForo_Phrase('Teams_promoted_by_x', array(
				'name' => $member['action_username']
			));
		}
		else if ($member['action'] == "approval")
		{
			$member['actionPhrase'] = new XenForo_Phrase('Teams_approval_by_x', array(
				'name' => $member['action_username']
			));
		}

		if (isset($member['action_username']) && isset($member['action_user_id']))
		{
			$member['actionUser'] = array(
				'user_id' => $member['action_user_id'],
				'username' => $member['action_username']
			);
		}

		$member['member_id'] = sprintf('%d_%d', $member['team_id'], $member['user_id']);
		$member['requestAction'] = $this->canLeaveOrCancelRequest($member, $team, $viewingUser);

		if (array_key_exists('user_custom_fields', $member))
		{
			$member['userCustomFields'] = unserialize($member['user_custom_fields']);
		}

		return $member;
	}


	public function insertMember($userId, $teamId, $memberRoleId, $state, array $actionUser, array $options = array(), array $viewingUser = null, $reqMessage = '')
	{
		$this->standardizeViewingUserReference($viewingUser);

		$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserById($userId);
		if (! $user)
		{
			throw new XenForo_Exception("Invalid user ID provided.", false);
			return false;
		}

		if (isset($options['insert']) && $options['insert'])
		{
			// good
		}
		else
		{
			$team = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getTeamById($teamId);
			if (!$team)
			{
				throw new XenForo_Exception("Invalid team ID provided.", false);
				return false;
			}
		}

		$action = array();
		if (!empty($actionUser['action']) && !empty($actionUser['action_user_id']) && !empty($actionUser['action_username']))
		{
			if ($actionUser['action_user_id'] != $userId)
			{
				$action = $actionUser;
			}
		}

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Member');

		$dw->bulkSet(array(
			'user_id' => $user['user_id'],
			'team_id' => $teamId,
			'username' => $user['username'],
			'member_state' => $state,
			'member_role_id' => $memberRoleId,
			'req_message' => $reqMessage
		));

		if ($action)
		{
			$dw->bulkSet($action);
		}

		$dw->save();
		return $dw->getMergedData();
	}

	public function remove(array $record, array $viewingUser = null)
	{
		$deleteDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Member');
		$deleteDw->setExistingData($record);
		$deleteDw->delete();
	}

	public function promote(array $record, $memberRoleId = '', $action = '', array $options = array(), array $viewingUser = null)
	{
		if (empty($memberRoleId))
		{
			return;
		}

		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		$updateDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Member');
		$updateDw->setExistingData($record);

		$updateDw->set('member_role_id', $memberRoleId);

		if (!empty($action))
		{
			$updateDw->bulkSet(array(
				'action' => $action,
				'action_user_id' => $viewingUser['user_id'],
				'action_username' => $viewingUser['username'],
				'join_date' => XenForo_Application::$time
			));
		}

		if (!empty($options))
		{
			$updateDw->bulkSet($options);
		}

		$updateDw->save();
	}

	public static function insert($userId, $teamId, $memberRoleId, $state, array $actionUser, array $options = array(), array $viewingUser = null)
	{
		return Nobita_Teams_Container::getModel(__CLASS__)->insertMember(
			$userId, $teamId,
			$memberRoleId, $state, $actionUser,
			$options, $viewingUser
		);
	}

	public function canPromoteMember(array $member, array $team, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if(empty($viewingUser['user_id']) || $member['user_id'] == $viewingUser['user_id'])
		{
			return false;
		}

		if($this->isTeamOwner($team, $member))
		{
			return false;
		}

		if($this->isTeamOwner($team, $viewingUser))
		{
			return true;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($member['member_role_id'], 'promoteMember');
	}

	public function canAsktoJoin(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($viewingUser['user_id'] == $team['user_id'])
		{
			return false;
		}

		$teamId = $team['team_id'];
		if ($this->isTeamMember($teamId, $viewingUser) || $this->isTeamMemberAwaitingConfirm($teamId, $viewingUser))
		{
			$errorPhraseKey = 'Teams_you_already_join_this_team';
			return false;
		}

		// added in version 2.3.6
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'join');
	}

	public function canAddPeople(array $team, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (empty($team['privacy_state']))
		{
			return false;
		}

		return $this->_getTeamModel()->isSecret($team);
	}

	public function canInvitePeople(array $team, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (empty($team['privacy_state']))
		{
			return false;
		}

		if ($this->_getTeamModel()->isSecret($team))
		{
			// secret never get this feature.
			// because the secret should be hidden to anyone
			return false;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(!$memberRecord)
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasGeneralPermission($memberRecord['member_role_id'], 'invitePeople');
	}

	public function canAcceptInvite(array $member, array $team, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'] || empty($member))
		{
			return false;
		}

		return $member['user_id'] == $viewingUser['user_id'] && $member['action'] == 'invite';
	}

	public function assertValidMemberOnTeam(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		return $this->isTeamMember($team['team_id'], $viewingUser);
	}

	public function canLeaveOrCancelRequest(array $record, array $team, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($viewingUser['user_id'] == $team['user_id'])
		{
			return array(); // visitor is owner of team. can't leave or request anything.
		}

		if (empty($record['member_id']))
		{
			if (!$this->canAsktoJoin($team, $team, $null, $viewingUser))
			{
				return false;
			}

			return array(
				'viewLink' => XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/members/join', '', array(
					'team_id' => $team['team_id'],
					'user_id' => $viewingUser['user_id']
				)),
				'viewTitle' => new XenForo_Phrase('Teams_join_team'),
				'extraLinkClasses' => 'OverlayTrigger'
			);
		}

		if ($record['member_state'] == 'accept')
		{
			return array(
				'viewLink' => XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/members/leave', $record),
				'viewTitle' => new XenForo_Phrase('Teams_leave_team'),
				'extraLinkClasses' => 'OverlayTrigger'
			);
		}
		elseif ($record['member_state'] == 'request')
		{
			if ($record['action'] == 'invite')
			{
				return array(
					'viewTitle' => new XenForo_Phrase('Teams_decline_invite')
				);
			}
			else
			{
				return array(
					'viewLink' => XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/members/leave', $record),
					'viewTitle' => new XenForo_Phrase('Teams_cancel_requesting'),
				);
			}
		}
	}

	public function canRemoveMember(array $member, array $team, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if(empty($viewingUser['user_id']) || ($viewingUser['user_id'] == $member['user_id']))
		{
			return false;
		}

		// Fixed the bug: http://nobita.me/threads/721/
		if($team['user_id'] == $member['user_id'])
		{
			$errorPhraseKey = 'Teams_you_cannot_remove_the_owner_of_team';
			return false;
		}

		if($this->isTeamOwner($team, $viewingUser))
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'removeMember');
	}

	public function sendAlertsToTeamManagersOnAction(array $record, $action = "", array $extraParams = array(), array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (empty($action))
		{
			return; // nothing to do.
		}

		$team = $this->_getTeamModel()->getFullTeamById($record['team_id']);
		if (!$team)
		{
			return false;
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

		if ($action == "request")
		{
			$conditions = array(
				'member_role_id' => $this->_getMemberRoleModel()->getGroupIdsReceiveAlertsOnRequest(), // get admin
				'member_state' => 'accept', // only get users who has accepted.
				 'alert' => 1 // only get users allow receive alerts.
			);
			if ($activeLimit)
			{
				$conditions['last_view_date'] = array('>=', $activeLimit);
			}

			$usersAlerted = $this->getAllMembersInTeam($team['team_id'], $conditions, array(
				'join' => self::FETCH_USER | self::FETCH_USER_PERMISSIONS
			));

			$memberRoleModel = $this->_getMemberRoleModel();

			foreach ($usersAlerted as $userId => $user)
			{
				$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
				if ($viewingUser['user_id'] == $user['user_id'])
				{
					continue;
				}

				if (!$memberRoleModel->hasModeratorPermission($user['member_role_id'], 'acceptDenyMember'))
				{
					// dont sent alert to users
					// who don't have permissions to approve requesting.
					// this perform alerts
					continue;
				}

				if (!$user['send_alert'])
				{
					// user don\t receive alert.
					continue;
				}

				XenForo_Model_Alert::alert($user['user_id'],
					$record['user_id'], $record['username'],
					"team_member", $team['team_id'],
					 "request"
				);
			}
		}
		else if ($action == 'accept')
		{
			XenForo_Model_Alert::alert($record['user_id'],
				$viewingUser['user_id'], $viewingUser['username'],
				'team_member', $team['team_id'],
				 'accept'
			);

			Nobita_Teams_Container::getModel('XenForo_Model_Alert')->deleteAlerts('team_member', $team['team_id'], $record['user_id']);
		}
		else if ($action == 'invite')
		{
			$userModel = Nobita_Teams_Container::getModel('XenForo_Model_User');
			$user = $userModel->getUserById($record['user_id']);

			if (!$user)
			{
				return;
			}

			if ($user['user_id'] == $record['action_user_id'])
			{
				return;
			}

			if (!isset($record['avatar_date']))
			{
				$recordUser = $userModel->getUserById($record['action_user_id']);
				if (!$recordUser)
				{
					return false;
				}

				$record = $recordUser;
			}

			XenForo_Model_Alert::alert($user['user_id'],
				$record['user_id'], $record['username'],
				'team_member', $team['team_id'],
				 'invite'
			);

			$user['email_confirm_key'] = $userModel->getUserEmailConfirmKey($user);
			$bbCodeParserHtml = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('HtmlEmail'));
			$team['aboutHtml'] = new XenForo_BbCode_TextWrapper($team['about'], $bbCodeParserHtml);

			$mail = XenForo_Mail::create('Team_member_invite', array(
				'user' => $user,
				'team' => $team,
				'inviter' => $record
			), $user['language_id']);

			$mail->enableAllLanguagePreCache();
			$mail->queue($user['email'], $user['username']);
		}
	}

	public function updateLastSeenGroupDateForMember($teamId, $userId)
	{
		$db = $this->_getDb();
		$db->query('
			UPDATE xf_team_member
			SET last_view_date = ?
			WHERE team_id = ? AND user_id = ?
		', array(XenForo_Application::$time, $teamId, $userId));
	}
}
