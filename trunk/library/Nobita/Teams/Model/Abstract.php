<?php

abstract class Nobita_Teams_Model_Abstract extends XenForo_Model
{
	public function getTeamMemberRecord($teamId, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return array();
		}

		$cacheKey = $this->getTeamMemberCacheKey($teamId, $viewingUser['user_id']);

		if(!XenForo_Application::isRegistered($cacheKey))
		{
			$memberModel = $this->_getMemberModel();
			$member = $memberModel->getRecordByKeys($viewingUser['user_id'], $teamId, array(
				'join' => Nobita_Teams_Model_Member::FETCH_USER
						 | Nobita_Teams_Model_Member::FETCH_MEMBER_ROLE
						 | Nobita_Teams_Model_Member::FETCH_MEMBER_BAN
						 | Nobita_Teams_Model_Member::FETCH_TEAM_FULL
			));

			XenForo_Application::set($cacheKey, $member);
		}

		return XenForo_Application::get($cacheKey);
	}

	public function getTeamMemberCacheKey($teamId, $userId)
	{
		return sprintf('nobita_Teams_Member_%d_%d', $teamId, $userId);
	}

	public function isTeamMember($teamId, array $viewingUser = null)
	{
		$member = $this->getTeamMemberRecord($teamId, $viewingUser);
		if(empty($member))
		{
			return false;
		}

		return $member['member_state'] == 'accept';
	}

	public function isTeamMemberAwaitingConfirm($teamId, array $viewingUser = null)
	{
		$member = $this->getTeamMemberRecord($teamId, $viewingUser);
		if(empty($member))
		{
			return false;
		}

		return $member['member_state'] == 'request';
	}

	public function isTeamMemberInvite($teamId, array $viewingUser = null)
	{
		$member = $this->getTeamMemberRecord($teamId, $viewingUser);
		if(empty($member))
		{
			return false;
		}

		return $member['member_state'] == 'request' && $member['action'] == 'invite';
	}

	/**
	 * Determine if the user is admin of team
	 *
	 * @param integer $teamId
	 * @param array $viewingUser
	 * @return boolean
	 */
	public function isTeamAdmin($teamId, array $viewingUser = null)
	{
		$member = $this->getTeamMemberRecord($teamId, $viewingUser);
		if(empty($member))
		{
			return false;
		}

		$memberRoles = $this->_getMemberRoleModel()->getMemberRolesFromCache();
		if(!isset($memberRoles[$member['member_role_id']]))
		{
			return false;
		}

		$memberRole = $memberRoles[$member['member_role_id']];
		if($memberRole['member_role_id'] == 'admin' || $memberRole['is_staff'])
		{
			return true;
		}

		return false;
	}

	/**
	 * Determine if the user is owner of team
	 *
	 * @param array $team which contain creator id
	 * @param array $viewingUser the current user viewing
	 * @return boolean
	 */
	public function isTeamOwner(array $team, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if(empty($team['team_id']) OR empty($viewingUser['user_id']))
		{
			return false;
		}

		return $viewingUser['user_id'] === $team['user_id'];
	}

	public function preGetConditionsForClause($table, array $shortConditions, array $conditions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		foreach($shortConditions as $conditionKey => $clause)
		{
			if (empty($conditions[$conditionKey]))
			{
				continue;
			}
			$value = $conditions[$conditionKey];

			if (isset($clause['notEqual']))
			{
				$arrCondition = 'NOT IN';
				$strCondition = '!=';
			}
			else
			{
				$arrCondition = 'IN';
				$strCondition = '=';
			}

			if (is_array($value))
			{
				$sqlConditions[] = $table . '.' . $clause['field'] . ' ' . $arrCondition . ' ('. $db->quote($value) . ')';
			}
			else
			{
				$sqlConditions[] = $table . '.' . $clause['field'] . ' ' . $strCondition . ' ' . $db->quote($value);
			}
		}

		return $sqlConditions;
	}

	public function standardizeViewingUserReference(array &$viewingUser = null)
	{
		// Bug report: http://nobita.me/threads/still-problems-replying-with-the-latest-version.902/
		if(isset($viewingUser['permissions']) && is_array($viewingUser['permissions'])) {
			return parent::standardizeViewingUserReference($viewingUser);
		}

		if(empty($viewingUser['user_id'])) {
			return parent::standardizeViewingUserReference($viewingUser);
		}

		$viewingUser = $this->getModelFromCache('XenForo_Model_User')->getFullUserById($viewingUser['user_id'], array(
			'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
		));
		$viewingUser['permissions'] = XenForo_Permission::unserializePermissions($viewingUser['global_permission_cache']);

		return $viewingUser;
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}

	protected function _getMemberModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');
	}

	protected function _getCategoryModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category');
	}

	public function _getMemberRoleModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
	}

	public function getBanningModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Banning');
	}
}
