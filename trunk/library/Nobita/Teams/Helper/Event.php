<?php

class Nobita_Teams_Helper_Event
{
	protected static $_datetime;
	protected static $_eventModel;

	private function __construct() {}

	public static function getEventsTodayWidget($limit, array $team = null)
	{
		$beginDay = self::_getDateTime()->setTime(0, 0, 0)->format('U');
		$endDay = self::_getDateTime()->setTime(23, 59, 59)->format('U');

		$eventModel = self::_getEventModel();

		$conditions = array('event_today' => true);
		if (!empty($team['team_id']))
		{
			$conditions += $eventModel->getPermissionBasedFetchConditions($team);
		}
		$fetchOptions = self::_getEventFetchOptions();
		$fetchOptions['limit'] = $limit * 3;

		$events = $eventModel->getEvents($conditions, $fetchOptions);
		return self::_filterUnviewableAndShortEvent($events, $limit);
	}

	public static function getEventsUpcomingWidget($limit, array $team = null)
	{
		$time = XenForo_Application::$time;
		$eventModel = self::_getEventModel();

		$conditions = array(
			'event_upcoming' => array($time, $time)
		);

		if (!empty($team['team_id']))
		{
			$conditions += $eventModel->getPermissionBasedFetchConditions($team);
		}
		$fetchOptions = self::_getEventFetchOptions();
		$fetchOptions['limit'] = $limit * 3;

		$events = $eventModel->getEvents($conditions, $fetchOptions);
		return self::_filterUnviewableAndShortEvent($events, $limit);
	}

	public static function getEventsPastWidget()
	{
	}

	protected static function _filterUnviewableAndShortEvent(array $events, $limit)
	{
		$eventModel = self::_getEventModel();

		foreach($events as $eventId => &$event)
		{
			if (!$eventModel->canViewEventAndContainer($event, $event, $event))
			{
				unset($events[$eventId]);
			}

			$event = $eventModel->prepareEvent($event, $event, $event);
		}

		if (count($events) > $limit)
		{
			$events = array_slice($events, 0, $limit, true);
		}

		return $events;
	}

	protected static function _getEventFetchOptions()
	{
		return array(
			'join' => Nobita_Teams_Model_Event::FETCH_TEAM
					| Nobita_Teams_Model_Event::FETCH_USER
		);
	}

	protected static function _getDateTime()
	{
		if (!self::$_datetime)
		{
			$time = XenForo_Application::$time;

			self::$_datetime = new DateTime('@' . $time);
			self::$_datetime->setTimeZone(XenForo_Locale::getDefaultTimeZone());
		}

		return self::$_datetime;
	}

	protected static function _getEventModel()
	{
		if (!self::$_eventModel)
		{
			self::$_eventModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
		}

		return self::$_eventModel;
	}

}
