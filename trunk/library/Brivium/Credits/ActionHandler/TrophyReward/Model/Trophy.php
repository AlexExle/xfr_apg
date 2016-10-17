<?php

class Brivium_Credits_ActionHandler_TrophyReward_Model_Trophy extends XFCP_Brivium_Credits_ActionHandler_TrophyReward_Model_Trophy
{
	public function awardUserTrophy(array $user, $username, array $trophy, $awardDate = null)
	{
		if ($awardDate === null)
		{
			$awardDate = XenForo_Application::$time;
		}
		$result = parent::awardUserTrophy($user, $username, $trophy, $awardDate);

		$db = $this->_getDb();
		$count = $db->fetchOne('
			SELECT COUNT(*)
			FROM xf_user_trophy
			WHERE user_id = ? AND trophy_id = ? AND award_date = ?
		',array($user['user_id'], $trophy['trophy_id'], $awardDate));

		if ($count)
		{
			$trophy = $this->prepareTrophy($trophy);
			$dataCredit = array(
				'amount'		=>	$trophy['trophy_points'],
				'content_id' 	=>	$trophy['trophy_id'],
				'content_type'	=>	'trophy',
				'user'			=>	$user,
				'message'		=>	$trophy['title']
			);
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('trophyReward', $user['user_id'], $dataCredit);
		}
	}
}