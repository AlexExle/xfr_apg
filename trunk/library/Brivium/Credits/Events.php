<?php

/**
 * Keep for avoid error from older credit integration add-ons
 *
 */
class Brivium_Credits_Events
{
	/**
	 * Collection of events.
	 *
	 * @var array
	 */
	protected $_events = array();

	/**
	 * Gets an event. If the event exists and is an array, then...
	 * 	* if no sub-event is specified but an $eventName key exists in the event, return the value for that key
	 *  * if no sub-event is specified and no $eventName key exists, return the whole event array
	 *  * if the sub-event === false, the entire event is returned, regardless of what keys exist
	 *  * if a sub-event is specified and the key exists, return the value for that key
	 *  * if a sub-event is specified and the key does not exist, return null
	 * If the event is not an array, then the value of the event is returned (provided no sub-event is specified).
	 * Otherwise, null is returned.
	 *
	 * @param string $eventName Name of the event
	 * @param null|false|string $subEvent Sub-event. See above for usage.
	 *
	 * @return null|mixed Null if the event doesn't exist (see above) or the event's value.
	 */
	public function get($eventName)
	{
		return array();
	}

	public function getByCurrency($eventName,$currencyId)
	{
		return array();
	}

	/**
	 * Gets all events in their raw form.
	 *
	 * @return array
	 */
	public function getEvents()
	{
		return array();
	}

	/**
	 * Sets the collection of events manually.
	 *
	 * @param array $events
	 */
	public function setEvents(array $events)
	{
		$this->_events = $events;
	}

	/**
	 * Magic getter for first-order events. This method cannot be used
	 * for getting a sub-event! You must use {@link get()} for that.
	 *
	 * This is equivalent to calling get() with no sub-event, which means
	 * the "main" sub-event will be returned (if applicable).
	 *
	 * @param string $event
	 *
	 * @return null|mixed
	 */
	public function __get($event)
	{
		return $this->get($event);
	}

	/**
	 * Returns true if the named event exists. Do not use this approach
	 * for sub-events!
	 *
	 * This is equivalent to calling get() with no sub-event, which means
	 * the "main" sub-event will be returned (if applicable).
	 *
	 * @param string $event
	 *
	 * @return boolean
	 */
	public function __isset($event)
	{
		return ($this->get($event) !== null);
	}

	/**
	 * Magic set method. Only sets whole events.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}
}