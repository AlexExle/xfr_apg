<?php

class EWRporta2_Widget_MedioRecent extends XenForo_Model
{
	public function getCachedData($widget, &$options)
	{
		if ((!$addon = $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnById('EWRmedio')) || empty($addon['active']))
		{
			return 'killWidget';
		}
		
		if ($options['mediorecent_category'])
		{
			$params = array(
				'type' => 'category',
				'where' => $options['mediorecent_category'],
			);
		}
		else
		{
			$params = array();
		}

		return $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaList(1, $options['mediorecent_limit'], $params);
	}
}