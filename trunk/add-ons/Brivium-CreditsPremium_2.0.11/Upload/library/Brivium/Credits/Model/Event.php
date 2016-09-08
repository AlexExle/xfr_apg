<?php

class Brivium_Credits_Model_Event extends XenForo_Model
{
	/**
	 * Get all events , in their relative display order.
	 *
	 * @return array Format: [] => event info
	 */
	public function getAllEvents()
	{
		if (($events = $this->_getLocalCacheData('allBrcEvents')) === false)
		{
			$events = $this->fetchAllKeyed('
				SELECT *
				FROM xf_brivium_credits_event
			', 'event_id');

			$this->setLocalCacheData('allBrcEvents', $events);
		}

		return $events;
	}

	/**
	 * Fetches events based on their eventIds.
	 * Note that if a version of the requested event does not exist
	 * in the specified style, nothing will be returned for it.
	 *
	 * @param array $eventIds List of eventIds
	 *
	 * @return array Format: [event_id] => info
	 */
	public function getEventsByIds(array $eventIds)
	{
		if (!$eventIds)
		{
			return array();
		}

		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_brivium_credits_event
			WHERE event_id IN (' . $this->_getDb()->quote($eventIds) . ')
		', 'event_id');
	}

	/**
	 * Returns event records based on event_id.
	 *
	 * @param string $eventId
	 *
	 * @return array|false
	 */
	public function getEventById($eventId = 0)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_brivium_credits_event
			WHERE  event_id = ?
		', array( $eventId));
	}

	/**
	 * Returns all the events that belong to the specified currency.
	 *
	 * @param string $currencyId
	 *
	 * @return array Format: [title] => info
	 */
	public function getEventsInCurrency($currencyId, array $fetchOptions = array())
	{
		$sqlClauses = $this->prepareEventFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		return $this->fetchAllKeyed('
				SELECT event.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_brivium_credits_event AS event
				' . $sqlClauses['joinTables'] . '
				WHERE currency_id = ?
			', 'event_id', $currencyId);
	}

	public function getEventsInCurrencyOfAddon($currencyId, $addOnId)
	{
		$actions = $this->_getActionModel()->getActionsInAddOn($addOnId);
		if($actions){
			$db = $this->_getDb();
			return $this->fetchAllKeyed('
				SELECT *
				FROM xf_brivium_credits_event
				WHERE currency_id = ? AND action_id IN (' . $db->quote(array_keys($actions)) . ')
			', 'action_id', $currencyId);
		}
		return array();
	}

	public function getEventInCurrencyByActionId($actionId, $currencyId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_brivium_credits_event
			WHERE  action_id = ? AND currency_id = ?
		', array($actionId, $currencyId));
	}

	public function prepareEventFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables,
		);
	}

	/**
	 * Gets events that match the given conditions.
	 *
	 * @param array $conditions Conditions to apply to the fetching
	 * @param array $fetchOptions Collection of options that relate to fetching
	 *
	 * @return array Format: [event id] => info
	 */
	public function getEvents(array $fetchOptions = array())
	{
		$sqlClauses = $this->prepareEventFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		return $this->fetchAllKeyed('
				SELECT event.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_brivium_credits_event AS event
				' . $sqlClauses['joinTables'] . '
			', 'event_id');
	}

	/**
	 * Prepares an ungrouped list of events for display.
	 *
	 * @param array $events Format: [] => event info
	 *
	 * @return array
	 */
	public function prepareEvents($events = array())
	{
		if(empty($events))return array();
		$newEvents = array();

		$actionObj = XenForo_Application::get('brcActionHandler');

		foreach ($events AS $eventId=>&$event)
		{
			if(!empty($event['action_id'])){
				$handler = $actionObj->getActionHandler($event['action_id']);
				if($handler){
					$event = $handler->prepareEvent($event);
					$newEvents[$eventId] = $event;
				}
			}
		}
		return $newEvents;
	}

	/**
	 * Gets all events in the format expected by the event cache.
	 *
	 * @return array Format: [event id] => info, with phrase cache as array
	 */
	public function getAllEventsForCache()
	{
		$this->resetLocalCacheData('allBrcEvents');

		$events = $this->getAllEvents();
		$events = $this->prepareEvents($events);
		$listedEvents = array();
		foreach($events AS $event){
			if(empty($event['active'])){
				continue;
			}
			if(!empty($listedEvents[$event['action_id']][$event['currency_id']])){
				if(!empty($listedEvents[$event['action_id']][$event['currency_id']]['event_id'])){
					$newEvents = array(
						$listedEvents[$event['action_id']][$event['currency_id']]['event_id']	=>	$listedEvents[$event['action_id']][$event['currency_id']],
						$event['event_id']	=>	$event
					);
					$listedEvents[$event['action_id']][$event['currency_id']] = $newEvents;
				}else{
					$listedEvents[$event['action_id']][$event['currency_id']][$event['event_id']] = $event;
				}
			}else{
				$listedEvents[$event['action_id']][$event['currency_id']] = $event;
			}
		}
		return $listedEvents;
	}

	/**
	 * Rebuilds the full Event cache.
	 *
	 * @return array Format: [event id] => info, with phrase cache as array
	 */
	public function rebuildEventCache()
	{
		$this->resetLocalCacheData('allBrcEvents');

		$events = $this->getAllEventsForCache();

		$this->_getDataRegistryModel()->set('brcEvents', $events);

		return $events;
	}

	/**
	 * Imports the add-on credits events XML.
	 *
	 * @param SimpleXMLElement $xml XML element pointing to the root of the data
	 * @param string $addOnId Add-on to import for
	 * @param integer $offset Number of elements to skip
	 *
	 */
	public function importEventsCurrencyXml(SimpleXMLElement $xml, $currencyId)
	{
		if(!$currencyId){
			return;
		}
		$db = $this->_getDb();

		XenForo_Db::beginTransaction($db);

		$events = XenForo_Helper_DevelopmentXml::fixPhpBug50670($xml->event);

		$actionObj = XenForo_Application::get('brcActionHandler');
		foreach ($events AS $event)
		{
			$eventId = (int)$event['event_id'];
			$actionId = (string)$event['action_id'];
			$handler = $actionObj->getActionHandler($actionId);
			if(!$handler){
				continue;
			}
			$dw = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Event');
			$existingEvent = $this->getEventInCurrencyByActionId($event['action_id'], $currencyId);
			if (!empty($existingEvent))
			{
				$dw->setExistingData($existingEvent['event_id']);
			}
			$dw->bulkSet(array(
				'currency_id' 	=> $currencyId,
				'user_groups' 	=>	json_decode((string) $event->user_groups, true),
				'forums' 		=> 	json_decode((string) $event->forums, true),

				'action_id' 	=> $actionId,

				'amount' 		=> (double)$event['amount'],
				'sub_amount' 	=> (double)$event['sub_amount'],
				'multiplier' 	=> (double)$event['multiplier'],
				'sub_multiplier' => (double)$event['sub_multiplier'],

				'active' 		=> (int)$event['active'],
				'moderate' 		=> (int)$event['moderate'],
				'alert' 		=> (int)$event['alert'],
				'times' 		=> (int)$event['times'],
				'max_time' 		=> (int)$event['max_time'],
				'apply_max' 	=> (int)$event['apply_max'],

				'extra_min' 	=> (double)$event['extra_min'],
				'extra_max' 	=> (double)$event['extra_max'],
				'extra_min_handle' 	=> (int)$event['extra_min_handle'],
				'target' 		=> $event['target']? (string)$event['target']:'user',
				'extra_data' 		=> 	json_decode((string) $event->extra_data, true),
			));
			$dw->save();
		}

		XenForo_Db::commit($db);
	}

	/**
	 * Gets the DOM document that represents the event development file.
	 * @param string|null $limitAddOnId If specified, only exports events from the specified add-on
	 * This must be turned into XML (or HTML) by the caller.
	 *
	 * @return DOMDocument
	 */
	public function getEventXml($currencyId, $addOnId = '')
	{
		$document = new DOMDocument('1.0', 'utf-8');
		$document->formatOutput = true;
		$rootNode = $document->createElement('brc_events');
		$document->appendChild($rootNode);
		if($addOnId){
			$events = $this->getEventsInCurrencyOfAddon($currencyId,$addOnId);
		}else{
			$events = $this->getEventsInCurrency($currencyId);
		}
		$this->appendEventsXml($rootNode, $events);
		return $document;
	}

	/**
	 * Appends the add-on credits events XML to a given DOM element.
	 *
	 * @param DOMElement $rootNode Node to append all elements to
	 * @param array $events to be exported
	 */
	public function appendEventsXml(DOMElement $rootNode,array $events){

		$document = $rootNode->ownerDocument;
		$events = $this->prepareEvents($events);

		foreach ($events AS $event)
		{
			$eventNode = $document->createElement('event');
			$eventNode->setAttribute('action_id', $event['action_id']);
			$eventNode->setAttribute('amount', $event['amount']);
			$eventNode->setAttribute('sub_amount', $event['sub_amount']);
			$eventNode->setAttribute('multiplier', $event['multiplier']);
			$eventNode->setAttribute('sub_multiplier', $event['sub_multiplier']);
			$eventNode->setAttribute('active', $event['active']);
			$eventNode->setAttribute('moderate', $event['moderate']);
			$eventNode->setAttribute('alert', $event['alert']);
			$eventNode->setAttribute('times', $event['times']);
			$eventNode->setAttribute('max_time', $event['max_time']);
			$eventNode->setAttribute('apply_max', $event['apply_max']);
			$eventNode->setAttribute('extra_min', $event['extra_min']);
			$eventNode->setAttribute('extra_max', $event['extra_max']);
			$eventNode->setAttribute('extra_min_handle', $event['extra_min_handle']);
			$eventNode->setAttribute('target', $event['target']);

			$forumsNode = $document->createElement('forums');
			$forumsNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, json_encode($event['forums'])));
			$eventNode->appendChild($forumsNode);

			$userGroupsNode = $document->createElement('user_groups');
			$userGroupsNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, json_encode($event['user_groups'])));
			$eventNode->appendChild($userGroupsNode);

			$extraDataNode = $document->createElement('extra_data');
			$extraDataNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, json_encode($event['extra_data'])));
			$eventNode->appendChild($extraDataNode);

			$rootNode->appendChild($eventNode);
		}
	}

	/**
	 * Load action model from cache.
	 *
	 * @return Brivium_Credits_Model_Action
	 */
	protected function _getActionModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Action');
	}
}