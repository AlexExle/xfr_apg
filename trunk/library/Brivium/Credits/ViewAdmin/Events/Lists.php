<?php

class Brivium_Credits_ViewAdmin_Events_Lists extends XenForo_ViewAdmin_Base
{
	/**
	 * Renders all options, and splits them into groups according to
	 * their 100s display order
	 */
	public function renderHtml()
	{
		$events = array();
		$listEvents = array();
		foreach ($this->_params['events'] AS $eventId => $event)
		{
			$x = floor($event['display_order'] / 100);
			$listEvents[$x][$eventId] = $event;
		}
		$this->_params['listEvents'] = $listEvents;
	}
}