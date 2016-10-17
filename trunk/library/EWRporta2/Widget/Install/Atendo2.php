<?php

class EWRporta2_Widget_Install_Atendo2
{
	public static function installCode($existingWidget, $widgetData)
	{
		if (!$existingWidget)
		{
			$addonModel = XenForo_Model::create('XenForo_Model_AddOn');
		
			if (!$addon = $addonModel->getAddOnById('EWRatendo2'))
			{
				throw new XenForo_Exception('Unable to install Atendo2 widget; missing prerequisite Addon.', true);
			}
			
			if ($addon['version_id'] < 4)
			{
				throw new XenForo_Exception('Unable to install Atendo2 widget; prerequisite Addon is out of date.', true);
			}
		}
	}
}