<?php

class Nobita_Teams_ViewPublic_Event_Calendar extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$events = $this->_params['events'];
		return Zend_Json::encode($events, Zend_Json::TYPE_OBJECT);
	}
}