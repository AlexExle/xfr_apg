<?php

class EWRporta2_Widget_ResourceFeatured extends XenForo_Model
{
	public function getCachedData($widget, &$options)
	{
		if ((!$addon = $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnById('XenResource')) || empty($addon['active']))
		{
			return 'killWidget';
		}
		
		$categories = array_keys($this->getModelFromCache('XenResource_Model_Category')->getAllCategories());
		
		return $this->getModelFromCache('XenResource_Model_Resource')->getFeaturedResourcesInCategories($categories,
			array(
				'join' => XenResource_Model_Resource::FETCH_VERSION
					| XenResource_Model_Resource::FETCH_USER
					| XenResource_Model_Resource::FETCH_CATEGORY
					| XenResource_Model_Resource::FETCH_FEATURED,
				'limit' => $options['resourcefeatured_limit'],
				'order' => $options['resourcefeatured_order']
			)
		);
	}
	
	public function getUncachedData($widget, &$options)
	{
		return $this->getModelFromCache('XenResource_Model_Resource')->prepareResources(
			$this->getModelFromCache('XenResource_Model_Resource')->filterUnviewableResources($widget['wCached'])
		);
	}
}