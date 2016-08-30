<?php

class Nobita_Teams_AlertHandler_Team extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		return $teamModel->getTeamsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
				| Nobita_Teams_Model_Team::FETCH_PROFILE
				| Nobita_Teams_Model_Team::FETCH_PRIVACY,
			'banUserId' => $viewingUser['user_id']
		));
	}

	public function canViewAlert(array $alert, $content, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canViewTeamAndContainer(
			$content, $content, $null, $viewingUser
		);
	}

	public function _prepareMass_alert(array $data, array $viewingUser)
	{
		return $data;
	}

}
