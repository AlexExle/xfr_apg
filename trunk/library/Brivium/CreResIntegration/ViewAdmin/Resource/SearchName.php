<?php

class Brivium_CreResIntegration_ViewAdmin_Resource_SearchName extends XenForo_ViewAdmin_Base
{
	public function renderJson()
	{
		$results = array();
		foreach ($this->_params['resources'] AS $resource)
		{
			$results[$resource['title']] = array(
				'avatar' => XenResource_ViewPublic_Helper_Resource::getResourceIconUrl($resource),
				'resource_title' => htmlspecialchars($resource['title'])
			);
		}
		return array(
			'results' => $results
		);
	}
	public function renderHtml()
	{
		$results = array();
		foreach ($this->_params['resources'] AS $resource)
		{pr(XenResource_ViewPublic_Helper_Resource::getResourceIconUrl($resource));
			$results[$resource['title']] = array(
				//'avatar' => XenForo_Template_Helper_Core::callHelper('avatar', array($resource, 's')),
				'resource_title' => htmlspecialchars($resource['title'])
			);
		}die;
		return array(
			'results' => $results
		);
	}
}