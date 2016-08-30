<?php

class Nobita_Teams_Model_Banning extends Nobita_Teams_Model_Abstract
{
	public function getBanningByKeys($teamId, $userId)
	{
		return $this->_getDb()->fetchRow('
			SELECT banning.*
			FROM xf_team_ban as banning
			WHERE banning.team_id = ? AND banning.user_id = ?
		', array($teamId, $userId));
	}

	public function getAllBanningActiveForTeam($teamId, array $fetchOptions = array())
	{
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT banning.*, user.*, ban_user.username as ban_username
				FROM xf_team_ban AS banning
					LEFT JOIN xf_user AS user ON (user.user_id = banning.user_id)
					LEFT JOIN xf_user ban_user ON (ban_user.user_id = banning.ban_user_id)
				WHERE banning.team_id = ?
					AND banning.end_date > ?
				ORDER BY banning.ban_date
			', $limitOptions['limit'], $limitOptions['offset']
		), 'user_id', array(
			$teamId, XenForo_Application::$time
		));
	}

	public function deleteBanningExpired()
	{
		$db = $this->_getDb();
		$db->delete('xf_team_ban', 'end_date < ' . $db->quote(XenForo_Application::$time));
	}

	public function canBanUser(array $user = null, array $team, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if(empty($viewingUser['user_id']))
		{
			return false;
		}

		if(isset($user['user_id']) && $user['user_id'] == $team['user_id'])
		{
			return false;
		}

		if(isset($user['user_id']) && $user['user_id'] == $viewingUser['user_id'])
		{
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

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'banMember');
	}

	public function canViewBannedUsers(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
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

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'banMember');
	}

	public function prepareContent(array &$content, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if ($content['user_id'] == $viewingUser['user_id']
			|| $content['user_id'] == $team['user_id']
		)
		{
			return;
		}
		$content['canBanUser'] = $this->canBanUser($content, $team, $errorPhraseKey, $viewingUser);
	}

	public function generateBanningKey($teamId, $banType, $userId)
	{
		return sprintf('%d_%s_%d', $teamId, $banType, $userId);
	}

	public function extractBanDataFromString($string)
	{
		$parts = explode('_', $string);
		if(count($parts) !== 3)
		{
			return false;
		}

		return array(
			'team_id' => $parts[0],
			'type' => $parts[1],
			'user_id' => $parts[2]
		);
	}
}
