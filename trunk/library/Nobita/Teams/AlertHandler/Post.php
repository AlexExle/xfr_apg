<?php

class Nobita_Teams_AlertHandler_Post extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		/* @var $postModel Nobita_Teams_Model_Post */
		$postModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');

		$posts = $postModel->getPostsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Post::FETCH_TEAM
		));

		return $posts;
	}

	public function canViewAlert(array $alert, $content, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post')->canViewPostAndContainer($content, $content, $content, $null, $viewingUser);
	}



}
