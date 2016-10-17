<?php

class Nobita_Teams_SitemapHandler_Event extends XenForo_SitemapHandler_Abstract
{
	/**
	 * @var Nobita_Teams_Model_Event
	 */
	protected $_eventModel;

	/**
	 * @var Nobita_Teams_Model_Team
	 */
	protected $_teamModel;

	protected function _getEventModel()
	{
		if (is_null($this->_eventModel))
		{
			$this->_eventModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
		}

		return $this->_eventModel;
	}

	protected function _getTeamModel()
	{
		if (is_null($this->_teamModel))
		{
			$this->_teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		}

		return $this->_teamModel;
	}

	public function getRecords($previousLast, $limit, array $viewingUser)
	{
		$teamModel = $this->_getTeamModel();
		if (!$teamModel->canViewTeams($null, $viewingUser))
		{
			return array();
		}

		$eventModel = $this->_getEventModel();
		$ids = $eventModel->getEventIdsInRange($previousLast, $limit);

		$events = $eventModel->getEventsByIds($ids, array(
			'join' => Nobita_Teams_Model_Event::FETCH_TEAM | Nobita_Teams_Model_Event::FETCH_USER
		));
		ksort($events);

		return $events;
	}

	public function isIncluded(array $entry, array $viewingUser)
	{
		if (!$this->_getTeamModel()->canViewTeamAndContainer($entry, $entry, $null, $viewingUser)
			|| !$this->_getTeamModel()->canViewTabAndContainer('events', $entry, $entry, $null, $viewingUser)
		)
		{
			return false;
		}

		return $this->_getEventModel()->canViewEventAndContainer($entry, $entry, $entry, $null, $viewingUser);
	}

	public function getData(array $entry)
	{
		$entry['title'] = XenForo_Helper_String::censorString($entry['event_title']);

		return array(
			'loc' => XenForo_Link::buildPublicLink('canonical:' . TEAM_ROUTE_PREFIX . '/events', $entry),
			'lastmod' => $entry['publish_date']
		);
	}

	public function getPhraseKey($key)
	{
		return 'Teams_handler_phrase_key_events';
	}

	public function isInterruptable()
	{
		return true;
	}

}