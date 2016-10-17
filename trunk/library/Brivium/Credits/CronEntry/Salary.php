<?php

/**
 * Cron entry for updating credits interest.
 */
class Brivium_Credits_CronEntry_Salary
{
	public static function runSalary()
	{
		$actionObj = XenForo_Application::get('brcActionHandler');
		$actionId = 'salary';
		$events = $actionObj->getActionEvents($actionId);
		if(!$events){
			return false;
		}
		XenForo_Application::defer('Brivium_Credits_Deferred_ActionTrigger', array('action_id' => $actionId), "triggerAction_$actionId", true);
	}
}