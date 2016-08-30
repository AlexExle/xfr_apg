<?php

class Nobita_Teams_NewsFeedHandler_Team extends XenForo_NewsFeedHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		/* @var $teamModel Nobita_Teams_Model_Team */
		$teamModel = $model->getModelFromCache('Nobita_Teams_Model_Team');
		$teams = $teamModel->getTeamsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Team::FETCH_PRIVACY 
				| Nobita_Teams_Model_Team::FETCH_PROFILE 
				| Nobita_Teams_Model_Team::FETCH_CATEGORY
		));
		return $teams;
	}

	public function canViewNewsFeedItem(array $item, $content, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canViewTeamAndContainer(
			$content, $content, $null, $viewingUser
		);
	}

	protected function _getDefaultTemplateTitle($contentType, $action)
	{
		return 'Team_news_feed_item_' . $action;
	}

}