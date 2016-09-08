<?php

/**
 * Cron entry for updating credits interest.
 */
class Brivium_Credits_CronEntry_Interest
{
	public static function runInterestUpdate()
	{
		$actionObj = XenForo_Application::get('brcActionHandler');
		$actionId = 'interest';
		$events = $actionObj->getActionEvents($actionId);
		if(!$events){
			return false;
		}
		XenForo_Application::defer('Brivium_Credits_Deferred_Interest', array('action_id' => $actionId), "triggerAction_$actionId", true);
	}
}