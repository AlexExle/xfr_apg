<?php

class EWRporta2_Widget_SiropuChat extends XenForo_Model
{
	public function getUncachedData($widget, &$options)
	{
		if ((!$addon = $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnById('siropu_chat')) || empty($addon['active']))
		{
			return 'killWidget';
		}
		
		return array();
	}
}