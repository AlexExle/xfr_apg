<?php

class EWRporta2_Widget_ResourceTop extends XenForo_Model
{
	public function getCachedData($widget, &$options)
	{
		if ((!$addon = $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnById('XenResource')) || empty($addon['active']))
		{
			return 'killWidget';
		}
		
		$conditions = array();
		
		if ($options['resourcetop_category'])
		{
			$conditions['resource_category_id'] = $options['resourcetop_category'];
		}
		
		return $this->getModelFromCache('XenResource_Model_Resource')->getResources($conditions,
			array(
				'limit' => $options['resourcetop_limit'],
				'order' => $options['resourcetop_order'],
				'direction' => 'desc'
			)
		);
	}
	
	public function getUncachedData($widget, &$options)
	{
		return $this->getModelFromCache('XenResource_Model_Resource')->filterUnviewableResources($widget['wCached']);
	}
}