<?php

class Nobita_Teams_XenForo_Model_User extends XFCP_Nobita_Teams_XenForo_Model_User
{
	public function prepareUserFetchOptions(array $fetchOptions)
	{
		$response = parent::prepareUserFetchOptions($fetchOptions);
		extract($response);

		if (isset($GLOBALS['Team_fetchTeamRibbon']))
		{
			$selectFields .= ',team_profile.ribbon_display_class, team_profile.ribbon_text, team_profile.team_id';
			$joinTables .= '
					LEFT JOIN xf_team_profile AS team_profile ON (team_profile.team_id = user.team_ribbon_id)';

			unset($GLOBALS['Team_fetchTeamRibbon']);
		}

		return compact('selectFields', 'joinTables');
	}

	public function getFollowingIdsForUser($userId, $limit = 50, $random = true)
	{
		$orderClause = $random ? 'RAND()' : 'user_id';
		$db = $this->_getDb();

		return $db->fetchCol($db->limit('
			SELECT follow_user_id
			FROM xf_user_follow
			WHERE user_id = ?
			ORDER BY ' . $orderClause . '
		', $limit), array($userId));
	}

}
