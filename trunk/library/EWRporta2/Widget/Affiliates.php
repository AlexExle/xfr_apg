<?php

class EWRporta2_Widget_Affiliates extends XenForo_Model
{
	public function getUncachedData($widget, $options)
	{
		$affiliates = array();
		
		switch ($options['affiliates_display'])
		{
			case 'random':
				$affiliates[] = $options['affiliates_links'][array_rand($options['affiliates_links'])];
				break;
			case 'shuffle':
				shuffle($options['affiliates_links']);
			default:
				$affiliates = $options['affiliates_links'];
				break;
		}

		return $affiliates;
	}
}