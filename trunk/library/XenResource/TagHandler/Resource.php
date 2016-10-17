<?php

class XenResource_TagHandler_Resource extends XenForo_TagHandler_Abstract
{
	/**
	 * @var XenResource_Model_Resource
	 */
	protected $_resourceModel = null;

	public function getPermissionsFromContext(array $context, array $parentContext = null)
	{
		if (isset($context['resource_id']))
		{
			$resource = $context;
			$category = $parentContext;
		}
		else
		{
			$resource = null;
			$category = $context;
		}

		if (!$category || !isset($category['resource_category_id']))
		{
			throw new Exception("Context must be a resource and a category or just a category");
		}

		$visitor = XenForo_Visitor::getInstance();

		/** @var XenResource_Model_Category $categoryModel */
		$categoryModel = XenForo_Model::create('XenResource_Model_Category');
		$categoryPermissions = $categoryModel->getCategoryPermCache(
			$visitor['permission_combination_id'], $category['resource_category_id']
		);

		if ($resource)
		{
			if ($resource['user_id'] == $visitor['user_id']
				&& XenForo_Permission::hasContentPermission($categoryPermissions, 'manageOthersTagsOwnRes')
			)
			{
				$removeOthers = true;
			}
			else
			{
				$removeOthers = XenForo_Permission::hasContentPermission($categoryPermissions, 'manageAnyTag');
			}
		}
		else
		{
			$removeOthers = false;
		}

		return array(
			'edit' => $this->_getResourceModel()->canEditTags($resource, $category),
			'removeOthers' => $removeOthers,
			'minTotal' => $category['min_tags']
		);
	}

	public function getBasicContent($id)
	{
		return $this->_getResourceModel()->getResourceById($id);
	}

	public function getContentDate(array $content)
	{
		return $content['resource_date'];
	}

	public function getContentVisibility(array $content)
	{
		return $content['resource_state'] == 'visible';
	}

	public function updateContentTagCache(array $content, array $cache)
	{
		$dw = XenForo_DataWriter::create('XenResource_DataWriter_Resource');
		$dw->setExistingData($content['resource_id']);
		$dw->set('tags', $cache);
		$dw->save();
	}

	public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
	{
		$resourceModel = $this->_getResourceModel();

		$threads = $resourceModel->getResourcesByIds($ids, array(
			'join' => XenResource_Model_Resource::FETCH_CATEGORY
				| XenResource_Model_Resource::FETCH_DESCRIPTION
				| XenResource_Model_Resource::FETCH_VERSION
				| XenResource_Model_Resource::FETCH_USER,
			'permissionCombinationId' => $viewingUser['permission_combination_id']
		));

		return $resourceModel->unserializePermissionsInList($threads, 'category_permission_cache');
	}

	public function canViewResult(array $result, array $viewingUser)
	{
		return $this->_getResourceModel()->canViewResourceAndContainer(
			$result, $result, $null, $viewingUser, $result['permissions']
		);
	}

	public function prepareResult(array $result, array $viewingUser)
	{
		return $this->_getResourceModel()->prepareResource($result, $result, $viewingUser);
	}

	public function renderResult(XenForo_View $view, array $result)
	{
		return $view->createTemplateObject('search_result_resource', array(
			'resource' => $result,
		));
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