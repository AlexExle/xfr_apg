<?php

class Nobita_Teams_NewsFeedHandler_Post extends XenForo_NewsFeedHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		/* @var $postModel Nobita_Teams_Model_Post */
		$postModel = $model->getModelFromCache('Nobita_Teams_Model_Post');
		$posts = $postModel->getPostsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Post::FETCH_TEAM
		));
		return $posts;
	}

	public function canViewNewsFeedItem(array $item, $content, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post')->canViewPostAndContainer(
			$content, $content, $content, $null, $viewingUser
		);
	}

}