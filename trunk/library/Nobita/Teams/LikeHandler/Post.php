<?php

class Nobita_Teams_LikeHandler_Post extends XenForo_LikeHandler_Abstract
{
	public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Post');
		$dw->setExistingData($contentId);
		$dw->set('likes', $dw->get('likes') + $adjustAmount);
		$dw->set('like_users', $latestLikes);
		$dw->save();
	}

	public function getContentData(array $contentIds, array $viewingUser)
	{
		$postModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
		
		$posts = $postModel->getPostsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Post::FETCH_TEAM
		));
		foreach ($posts as $postID => $post)
		{
			if (!$postModel->canViewPostAndContainer($post, $post, $post, $null, $viewingUser))
			{
				unset($posts[$postID]);
			}
		}

		return $posts;
	}

	public function getListTemplateName()
	{
		return 'news_feed_item_team_post_like';
	}

	/**
	 * Attempts to update any instances of an old username in like_users with a new username
	 *
	 * @param integer $oldUserId
	 * @param integer $newUserId
	 * @param string $oldUsername
	 * @param string $newUsername
	 */
	public function batchUpdateContentUser($oldUserId, $newUserId, $oldUsername, $newUsername)
	{
		$postModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
		$postModel->batchUpdateLikeUser($oldUserId, $newUserId, $oldUsername, $newUsername);
	}
}