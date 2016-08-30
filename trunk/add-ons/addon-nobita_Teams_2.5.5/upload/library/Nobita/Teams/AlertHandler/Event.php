<?php

class Nobita_Teams_AlertHandler_Event extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$eventModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
		$events = $eventModel->getEventsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Event::FETCH_TEAM
					 | Nobita_Teams_Model_Event::FETCH_USER
		));

		foreach ($events as &$event)
		{
			$event = $eventModel->prepareEvent($event, $event, $event, $viewingUser);
		}
		return $events;
	}

	public function canViewAlert(array $alert, $content, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event')->canViewEventAndContainer(
			$content, $content, $content, $null, $viewingUser
		);
	}


}
