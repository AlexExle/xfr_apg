<?php

/**
 * @extends XenForo_ControllerPublic_FindNew
 */
class XenResource_Listener_Proxy_ControllerFindNew extends XFCP_XenResource_Listener_Proxy_ControllerFindNew
{
	public function actionResources()
	{
		$this->_routeMatch->setSections('resources');
		$resourceModel = $this->_getResourceModel();

		$searchId = $this->_input->filterSingle('search_id', XenForo_Input::UINT);
		if (!$searchId)
		{
			return $this->findNewResources();
		}

		$searchModel = $this->_getSearchModel();

		$search = $searchModel->getSearchById($searchId);
		if (!$search
			|| $search['user_id'] != XenForo_Visitor::getUserId()
			|| $search['search_type'] != 'new-resources'
		)
		{
			return $this->findNewResources();
		}

		$visitor = XenForo_Visitor::getInstance();

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage = XenForo_Application::get('options')->resourcesPerPage;

		$pageResultIds = $searchModel->sliceSearchResultsToPage($search, $page, $perPage);
		$resourceIds = XenForo_Application::arrayColumn($pageResultIds, 1);

		$resources = $resourceModel->getResourcesByIds($resourceIds,
			array(
				'join' => XenResource_Model_Resource::FETCH_VERSION
					| XenResource_Model_Resource::FETCH_USER
					| XenResource_Model_Resource::FETCH_CATEGORY,
				'permissionCombinationId' => $visitor->permission_combination_id,
				'limit' => XenForo_Application::get('options')->maximumSearchResults
			)
		);

		/** @var XenResource_Model_Category $categoryModel */
		$categoryModel = $this->getModelFromCache('XenResource_Model_Category');
		$categoryModel->bulkSetCategoryPermCache(null, $resources, 'category_permission_cache');

		$resources = $resourceModel->filterUnviewableResources($resources);
		$resources = $resourceModel->prepareResources($resources);
		$inlineModOptions = $resourceModel->getInlineModOptionsForResources($resources);

		$output = array();
		foreach ($resourceIds AS $resourceId)
		{
			if (isset($resources[$resourceId]))
			{
				$output[$resourceId] = $resources[$resourceId];
			}
		}
		$resources = $output;

		$resultStartOffset = ($page - 1) * $perPage + 1;
		$resultEndOffset = ($page - 1) * $perPage + count($resourceIds);

		$viewParams = array(
			'search' => $search,
			'resources' => $resources,
			'inlineModOptions' => $inlineModOptions,

			'startOffset' => $resultStartOffset,
			'endOffset' => $resultEndOffset,

			'ignoredNames' => $this->_getIgnoredContentUserNames($resources),

			'page' => $page,
			'perPage' => $perPage,
			'total' => $search['result_count'],
			'nextPage' => ($resultEndOffset < $search['result_count'] ? ($page + 1) : 0)
		);

		return $this->getFindNewWrapper(
			$this->responseView('XenResource_ViewPublic_FindNew_Resources', 'find_new_resources', $viewParams),
			'resources'
		);
	}

	public function findNewResources()
	{
		$resourceModel = $this->_getResourceModel();
		$searchModel = $this->_getSearchModel();

		$visitor = XenForo_Visitor::getInstance();

		$cutOff = XenForo_Application::$time - XenForo_Application::get('options')->readMarkingDataLifetime * 86400;

		$resources = $resourceModel->getResources(
			array(
				'last_update' => array('>', $cutOff),
				'moderated' => false,
				'deleted' => false
			),
			array(
				'join' => XenResource_Model_Resource::FETCH_VERSION
					| XenResource_Model_Resource::FETCH_USER
					| XenResource_Model_Resource::FETCH_CATEGORY,
				'permissionCombinationId' => $visitor->permission_combination_id,
				'limit' => XenForo_Application::get('options')->maximumSearchResults
			)
		);

		/** @var XenResource_Model_Category $categoryModel */
		$categoryModel = $this->getModelFromCache('XenResource_Model_Category');
		$categoryModel->bulkSetCategoryPermCache(null, $resources, 'category_permission_cache');

		$resources = $resourceModel->filterUnviewableResources($resources);

		$searchType = 'new-resources';

		$results = array();
		foreach ($resources AS $resource)
		{
			$results[] = array(
				XenForo_Model_Search::CONTENT_TYPE => 'resource',
				XenForo_Model_Search::CONTENT_ID => $resource['resource_id']
			);
		}

		if (!$results)
		{
			return $this->getNoResourcesResponse();
		}

		$search = $searchModel->insertSearch($results, $searchType, '', array(), 'date', false);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('find-new/resources', $search)
		);
	}

	public function getNoResourcesResponse()
	{
		$this->_routeMatch->setSections('resources');

		return $this->getFindNewWrapper($this->responseView(
			'XenResource_ViewPublic_FindNew_ResourcesNone',
			'find_new_resources_none',
			array()
		), 'resources');
	}

	protected function _getWrapperTabs()
	{
		$tabs = parent::_getWrapperTabs();

		if (XenForo_Visitor::getInstance()->hasPermission('resource', 'view'))
		{
			$tabs['resources'] = array(
				'href' => XenForo_Link::buildPublicLink('find-new/resources'),
				'title' => new XenForo_Phrase('new_resources')
			);
		}

		return $tabs;
	}

	/**
	 * @return XenResource_Model_Resource
	 */
	protected function _getResourceModel()
	{
		return $this->getModelFromCache('XenResource_Model_Resource');
	}
}