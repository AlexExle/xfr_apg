<?php

class XenResource_NewsFeedHandler_Rating extends XenForo_NewsFeedHandler_Abstract
{
	protected $_ratingModel;

	/**
	 * Just returns a value for each requested ID
	 * but does no actual DB work
	 *
	 * @param array $contentIds
	 * @param XenForo_Model_NewsFeed $model
	 * @param array $viewingUser Information about the viewing user (keys: user_id, permission_combination_id, permissions)
	 *
	 * @return array
	 */
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		$ratingModel = $this->_getRatingModel();

		$ratings = $ratingModel->getRatingsByIds($contentIds, array(
			'join' => XenResource_Model_Rating::FETCH_USER
		));
		$resourceIds = array();
		foreach ($ratings AS $rating)
		{
			$resourceIds[$rating['resource_id']] = $rating['resource_id'];
		}
		$resources = XenForo_Model::create('XenResource_Model_Resource')->getResourcesByIds($resourceIds, array(
			'join' => XenResource_Model_Resource::FETCH_CATEGORY,
			'permissionCombinationId' => $viewingUser['permission_combination_id']
		));

		foreach ($ratings AS $key => &$rating)
		{
			if (!isset($resources[$rating['resource_id']]))
			{
				unset($ratings[$key]);
			}
			else
			{
				$rating['resource'] = $resources[$rating['resource_id']];
				$rating['resource']['title'] = XenForo_Helper_String::censorString($rating['resource']['title']);
			}
		}

		return $ratings;
	}

	/**
	 * Determines if the given news feed item is viewable.
	 *
	 * @param array $item
	 * @param mixed $content
	 * @param array $viewingUser
	 *
	 * @return boolean
	 */
	public function canViewNewsFeedItem(array $item, $content, array $viewingUser)
	{
		$ratingModel = $this->_getRatingModel();

		$categoryPermissions = XenForo_Permission::unserializePermissions($content['resource']['category_permission_cache']);

		return $ratingModel->canViewRatingAndContainer(
			$content, $content['resource'], $content['resource'], $null, $viewingUser, $categoryPermissions
		);
	}

	protected function _prepareNewsFeedItemBeforeAction(array $item, $content, array $viewingUser)
	{
		$item['content'] = $this->_getRatingModel()->prepareRating(
			$item['content'], $item['content']['resource'], $item['content']['resource'], $viewingUser
		);

		return $item;
	}

	/**
	 * @return XenResource_Model_Rating
	 */
	protected function _getRatingModel()
	{
		if (!$this->_ratingModel)
		{
			$this->_ratingModel = XenForo_Model::create('XenResource_Model_Rating');
		}

		return $this->_ratingModel;
	}
}