<?php

class EWRporta2_Widget_Install_SiropuChat
{
	public static function installCode($existingWidget, $widgetData)
	{
		if (!$existingWidget)
		{
			$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		
			if (!$addon = $addonModel->getAddOnById('siropu_chat'))
			{
				throw new XenForo_Exception('Unable to install SiropuChat widget; missing prerequisite Addon.', true);
			}
			
			if ($addon['version_id'] < 30)
			{
				throw new XenForo_Exception('Unable to install SiropuChat widget; prerequisite Addon is out of date.', true);
			}
		}
	}
}