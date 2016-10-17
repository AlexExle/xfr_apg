<?php

class Nobita_Teams_Model_MemberRole extends Nobita_Teams_Model_Abstract
{
	protected $_groupStaffIds 	= array();
	protected $_groupIdsAlertOnRequest = array();

	public function getMemberRoleById($memberRoleId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_team_member_role
			WHERE member_role_id = ?
		', $memberRoleId);
	}

	public function getAllMemberRoles()
	{
		$memberRoles = $this->fetchAllKeyed('
			SELECT *
			FROM xf_team_member_role
			ORDER BY display_order
		','member_role_id');

		return $this->prepareMemberRoles($memberRoles);
	}

	public function saveMemberRolesToCache()
	{
		$memberRoles = $this->getAllMemberRoles();
		$memberRoles = $this->prepareMemberRoles($memberRoles);

		XenForo_Application::setSimpleCacheData(TEAM_DATAREGISTRY_KEY, $memberRoles);
		return $memberRoles;
	}

	public function prepareMemberRoles(array $memberRoles)
	{
		return array_map(array($this, 'prepareMemberRole'), $memberRoles);
	}

	public function prepareMemberRole(array $memberRole)
	{
		$memberRole['member_role_title'] = new XenForo_Phrase($this->getMemberRolePhrase($memberRole['member_role_id']));
		$memberRole['memberRoleTitle'] = $memberRole['member_role_title'];

		if(!is_array($memberRole['roles']))
		{
			$memberRole['roles'] = @unserialize($memberRole['roles']);
		}

		return $memberRole;
	}

	public function getMemberRolesFromCache()
	{
		$cacheKey = 'nobita_Teams_memberRoles';
		if(!XenForo_Application::isRegistered($cacheKey))
		{
			$memberRoles = XenForo_Application::getSimpleCacheData(TEAM_DATAREGISTRY_KEY);
			if(!$memberRoles)
			{
				$memberRoles = $this->saveMemberRolesToCache();
			}

			XenForo_Application::set($cacheKey, $memberRoles);
		}

		$memberRoles = XenForo_Application::get($cacheKey);
		return $this->prepareMemberRoles($memberRoles);
	}

	// get all group ids which have permission can approve or unapprove
	// requesting join to team.
	public function getGroupIdsReceiveAlertsOnRequest()
	{
		if (!$this->_groupIdsAlertOnRequest)
		{
			$cache = $this->getMemberRolesFromCache();

			foreach($cache as $memberRole)
			{
				if($this->hasModeratorPermission($memberRole['member_role_id'], 'acceptDenyMember'))
				{
					$this->_groupIdsAlertOnRequest[] = $memberRole['member_role_id'];
				}
			}
		}

		return $this->_groupIdsAlertOnRequest;
	}

	public function getStaffIds()
	{
		if (!$this->_groupStaffIds)
		{
			$userRoles = $this->getMemberRolesFromCache();

			foreach ($userRoles as $userRoleId => $userRole)
			{
				if (isset($userRole['is_staff']) && 1 == intval($userRole['is_staff']))
				{
					$this->_groupStaffIds[] = $userRoleId;
				}
			}
		}

		return $this->_groupStaffIds;
	}

	public function hasPermission($memberRoleId, $group, $permissionId)
	{
		$memberRoles = $this->getMemberRolesFromCache();
		if(!isset($memberRoles[$memberRoleId]))
		{
			return false;
		}

		$memberRole = $memberRoles[$memberRoleId];
		$permissions = $memberRole['roles'];

		if(!isset($permissions[$group]))
		{
			return false;
		}

		if(empty($permissions[$group][$permissionId]))
		{
			return false;
		}

		return true;
	}

	public function hasGeneralPermission($memberRoleId, $permissionId)
	{
		return $this->hasPermission($memberRoleId, 'general', $permissionId);
	}

	public function hasModeratorPermission($memberRoleId, $permissionId)
	{
		return $this->hasPermission($memberRoleId, 'moderator', $permissionId);
	}

	public function hasForumPermission($memberRoleId, $permissionId)
	{
		return $this->hasPermission($memberRoleId, 'forum', $permissionId);
	}

	protected function _getBasicMemberRolesForGeneral()
	{
		$memberRoleIds = array(
			'bypassPosting',
			'invitePeople',
		);

		return $this->_buildMemberRolesForEdit($memberRoleIds, 'general');
	}

	protected function _getBasicMemberRolesForModerator()
	{
		$memberRoleIds = array(
			'removeMember',
			'promoteMember',
			'acceptDenyMember',
			'banMember',
			'approveUnapprovePost',
			'stickUnstickPost',
			'editPostAny',
			'deletePostAny',
			'editCommentAny',
			'deleteCommentAny',
			'editEventAny',
			'deleteEventAny',
			'manageLogo',
			'manageCover',
			'massAlert',
			'updateRules'
		);

		return $this->_buildMemberRolesForEdit($memberRoleIds, 'moderator');
	}

	protected function _getBasicMemberRolesForForum()
	{
		$memberRoleIds = array(
			'editThreadAny',
			'deleteThreadAny',
			'stickUnstickThread',
			'lockUnlockThread',
			'approveUnapproveThread',
			'undeleteThread',
			'editPostAny',
			'deletePostAny',
			'viewDeletedThread',
			'viewModeratedThread',
		);

		return $this->_buildMemberRolesForEdit($memberRoleIds, 'forum');
	}

	protected function _buildMemberRolesForEdit(array $memberRoleIds, $group)
	{
		$memberRoles = array();
		foreach($memberRoleIds as $memberRoleId)
		{
			$memberRoles[$memberRoleId] = array(
				'label' => $this->getMemberRoleLabelPhrase($memberRoleId, $group),
				//'hint' => $this->getMemberRoleExplainPhrase($memberRoleId, $group),
			);
		}

		return $memberRoles;
	}

	public function getBasicMemberRoles()
	{
		return array(
			'general' => $this->_getBasicMemberRolesForGeneral(),
			'moderator' => $this->_getBasicMemberRolesForModerator(),
			'forum' => $this->_getBasicMemberRolesForForum()
		);
	}

	public function getMemberRoleGrouped()
	{
		$grouped = array_keys($this->getBasicMemberRoles());
		$result = array();
		foreach($grouped as $groupId)
		{
			$result[$groupId] = new XenForo_Phrase('Teams_member_role_group_'.$groupId.'_permissions');
		}

		return $result;
	}

	public function insertOrUpdateMasterPhrase($memberRoleId, $title)
	{
		$this->getModelFromCache('XenForo_Model_Phrase')->insertOrUpdateMasterPhrase(
			$this->getMemberRolePhrase($memberRoleId), $title, '', array(
				'global_cache' => true
			)
		);
	}

	public function getMemberRolePhrase($memberRoleId)
	{
		return "Teams_member_role_id_{$memberRoleId}";
	}

	public function getMemberRoleLabelPhrase($memberRoleId, $group)
	{
		return new XenForo_Phrase("Teams_member_role_id_{$group}_{$memberRoleId}");
	}

	public function getMemberRoleExplainPhrase($memberRoleId, $group)
	{
		return new XenForo_Phrase("Teams_member_role_id_{$group}_{$memberRoleId}_explain");
	}
}
