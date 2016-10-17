<?php

class EWRporta2_Widget_Install_Resource
{
	public static function installCode($existingWidget, $widgetData)
	{
		if (!$existingWidget)
		{
			$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		
			if (!$addon = $addonModel->getAddOnById('XenResource'))
			{
				throw new XenForo_Exception('Unable to install Resource widget; missing prerequisite Addon.', true);
			}
		}
	}
}