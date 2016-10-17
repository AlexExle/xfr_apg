<?php

class Nobita_Teams_AlertHandler_Member extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		/* @var $teamModel Nobita_Teams_Model_Team */
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getTeamsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Team::FETCH_PROFILE
					| Nobita_Teams_Model_Team::FETCH_PRIVACY
					| Nobita_Teams_Model_Team::FETCH_CATEGORY
		));
	}

	public function canViewAlert(array $alert, $content, array $viewingUser)
	{
		// canViewTeamAndContainer
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canViewTeam($content, $content, $null, $viewingUser);
	}



}
