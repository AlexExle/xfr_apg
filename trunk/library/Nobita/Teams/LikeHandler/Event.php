<?php

class Nobita_Teams_LikeHandler_Event extends XenForo_LikeHandler_Abstract
{
	public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Event');
		if ($dw->setExistingData($contentId))
		{
			$dw->set('likes', $dw->get('likes') + $adjustAmount);
			$dw->set('like_users', $latestLikes);
			$dw->save();
		}
	}

	public function getContentData(array $contentIds, array $viewingUser)
	{
		$eventModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');

		$events = $eventModel->getEventsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Event::FETCH_TEAM
		));

		foreach ($events as $eventId => &$event)
		{
			if (!$eventModel->canViewEventAndContainer($event, $event, $event, $null, $viewingUser))
			{
				unset($events[$eventId]);
			}
			else
			{
				$event = $eventModel->prepareEvent($event, $event, $event, $viewingUser);
			}
		}

		return $events;
	}

	public function getListTemplateName()
	{
		return 'news_feed_item_team_event_like';
	}

	public function batchUpdateContentUser($oldUserId, $newUserId, $oldUsername, $newUsername)
	{
		$eventModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
		$eventModel->batchUpdateLikeUser($oldUserId, $newUserId, $oldUsername, $newUsername);
	}
}
