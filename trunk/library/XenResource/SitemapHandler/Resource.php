<?php

class XenResource_SitemapHandler_Resource extends XenForo_SitemapHandler_Abstract
{
	protected $_resourceModel;

	public function getRecords($previousLast, $limit, array $viewingUser)
	{
		$resourceModel = $this->_getResourceModel();

		if (!$resourceModel->canViewResources($null, $viewingUser))
		{
			return array();
		}

		$ids = $resourceModel->getResourceIdsInRange($previousLast, $limit);

		$threads = $resourceModel->getResourcesByIds($ids, array(
			'join' => XenResource_Model_Resource::FETCH_CATEGORY,
			'permissionCombinationId' => $viewingUser['permission_combination_id']
		));
		ksort($threads);

		return $resourceModel->unserializePermissionsInList($threads, 'category_permission_cache');
	}

	public function isIncluded(array $entry, array $viewingUser)
	{
		return $this->_getResourceModel()->canViewResourceAndContainer(
			$entry, $entry, $null, $viewingUser, $entry['permissions']
		);
	}

	public function getData(array $entry)
	{
		$entry['title'] = XenForo_Helper_String::censorString($entry['title']);

		return array(
			'loc' => XenForo_Link::buildPublicLink('canonical:resources', $entry),
			'lastmod' => $entry['last_update']
		);
	}

	public function isInterruptable()
	{
		return true;
	}

	/**
	 * @return XenResource_Model_Resource
	 */
	protected function _getResourceModel()
	{
		if (!$this->_resourceModel)
		{
			$this->_resourceModel = XenForo_Model::create('XenResource_Model_Resource');
		}

		return $this->_resourceModel;
	}
}