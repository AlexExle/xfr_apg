<?php

class Nobita_Teams_NewsFeedHandler_Event extends XenForo_NewsFeedHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		/* @var $eventModel Nobita_Teams_Model_Event */
		$eventModel = $model->getModelFromCache('Nobita_Teams_Model_Event');
		$events = $eventModel->getEventsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Event::FETCH_TEAM
		));

		$output = array();
		foreach ($events as $eventId => $event)
		{
			$output[$eventId] = $eventModel->prepareEvent($event, $event, $event, $viewingUser);
		}

		return $output;
	}

	public function canViewNewsFeedItem(array $item, $content, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event')->canViewEventAndContainer(
			$content, $content, $content, $null, $viewingUser
		);
	}



}