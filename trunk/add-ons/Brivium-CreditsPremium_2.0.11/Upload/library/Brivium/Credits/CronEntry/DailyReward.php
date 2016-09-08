<?php

/**
 * Cron entry for updating credits interest.
 */
class Brivium_Credits_CronEntry_DailyReward
{
	public static function runDailyReward()
	{
		$actionObj = XenForo_Application::get('brcActionHandler');
		$actionId = 'dailyReward';
		$events = $actionObj->getActionEvents($actionId);
		if(!$events){
			return false;
		}
		XenForo_Application::defer('Brivium_Credits_Deferred_ActionTrigger', array('action_id' => $actionId), "triggerAction_$actionId", true);
	}
}