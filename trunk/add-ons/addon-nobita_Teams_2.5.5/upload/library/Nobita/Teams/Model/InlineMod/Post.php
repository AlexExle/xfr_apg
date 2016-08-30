<?php

class Nobita_Teams_Model_InlineMod_Post extends XenForo_Model
{
	public $enableLogging = false;

	/**
	 * Determines if the selected profile post IDs can be deleted.
	 *
	 * @param array $postIds List of IDs check
	 * @param string $errorKey Modified by reference. If no permission, may include a key of a phrase that gives more info
	 * @param array|null $viewingUser Viewing user reference
	 *
	 * @return boolean
	 */
	public function canDeletePosts(array $postIds, &$errorKey = '', array $viewingUser = null)
	{
		list($posts, $teams, $categories) = $this->getPostsAndParentData($postIds);
		return $this->canDeletePostsData($posts, $teams, $categories, $errorKey, $viewingUser);
	}

	/**
	 * Determines if the selected profile post data can be deleted.
	 *
	 * @param array $posts List of data to be deleted
	 * @param array $users List of information about users whose profiles the posts are on
	 * @param string $errorKey Modified by reference. If no permission, may include a key of a phrase that gives more info
	 * @param array|null $viewingUser Viewing user reference
	 *
	 * @return boolean
	 */
	public function canDeletePostsData(array $posts, array $teams, array $categories, &$errorKey = '', array $viewingUser = null)
	{
		// note: this cannot use _checkPermissionOnPosts because of extra param
		if (!$posts)
		{
			return true;
		}

		$this->standardizeViewingUserReference($viewingUser);
		$postModel = $this->_getPostModel();

		foreach ($posts AS $post)
		{
			$team = $this->_getTeamFromPost($post, $teams);
			$category = $this->_getCategoryFromTeam($team, $categories);

			if (!$postModel->canDeletePost($post, $team, $category, $errorKey, $viewingUser))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Deletes the specified profile posts if permissions are sufficient.
	 *
	 * @param array $postIds List of IDs to delete
	 * @param array $options Options that control the delete. Supports deleteType (soft or hard).
	 * @param string $errorKey Modified by reference. If no permission, may include a key of a phrase that gives more info
	 * @param array|null $viewingUser Viewing user reference
	 *
	 * @return boolean True if permissions were ok
	 */
	public function deletePosts(array $postIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		$options = array_merge(
			array(
				'deleteType' => '',
				'reason' => ''
			), $options
		);

		list($posts, $teams, $categories) = $this->getPostsAndParentData($postIds);

		if (empty($options['skipPermissions']) && !$this->canDeletePostsData($posts, $teams, $categories, $errorKey, $viewingUser))
		{
			return false;
		}

		foreach ($posts AS $post)
		{
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Post', XenForo_DataWriter::ERROR_SILENT);
			$dw->setExistingData($post);
			if (!$dw->get('post_id'))
			{
				// this may happen if the post was already removed
				continue;
			}

			//$team = $this->_getTeamFromPost($post, $teams);
			$dw->delete();

			if ($this->enableLogging)
			{
				XenForo_Model_Log::logModeratorAction(
					'team_post', $post, 'delete_' . $options['deleteType'], array('reason' => $options['reason']), $user
				);
			}
		}

		return true;
	}

	/**
	 * Gets information about the user a where profile post has been posted.
	 *
	 * @param array $post Info about the profile post
	 * @param array $users List of users that the profile post could belong to
	 *
	 * @return array User info
	 */
	protected function _getTeamFromPost(array $post, array $teams)
	{
		return $teams[$post['team_id']];
	}

	protected function _getCategoryFromTeam(array $team, array $categories)
	{
		return $categories[$team['team_category_id']];
	}

	protected function _getTeamAndCategoryFromPost(array $post, array $teams, array $categories)
	{
		$team = $this->_getTeamFromPost($post, $teams);
		$category = $this->_getCategoryFromTeam($team, $categories);

		return array($team, $category);
	}

	public function canApproveUnapprovePosts(array $postIds, &$errorKey = '', array $viewingUser = null)
	{
		list($posts, $teams, $categories) = $this->getPostsAndParentData($postIds);
		return $this->canApproveUnapprovePostsData($posts, $teams, $categories, $errorKey, $viewingUser);
	}

	public function canApproveUnapprovePostsData(array $posts, array $teams, array $categories, &$errorKey = '', array $viewingUser = null)
	{
		if (!$posts)
		{
			return true;
		}

		$this->standardizeViewingUserReference($viewingUser);
		$postModel = $this->_getPostModel();

		foreach ($posts as $post)
		{
			list($team, $category) = $this->_getTeamAndCategoryFromPost($post, $teams, $categories);
			if (!$postModel->canApproveUnapprove($post, $team, $category, $errorKey, $viewingUser))
			{
				return false;
			}
		}

		return true;
	}

	public function approvePosts(array $postIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		list($posts, $teams, $categories) = $this->getPostsAndParentData($postIds);

		if (empty($options['skipPermissions']) && !$this->canApproveUnapprovePostsData($posts, $teams, $categories, $errorKey, $viewingUser))
		{
			return false;
		}

		$this->_updatePostsBulk($posts, $teams, array('message_state' => 'visible'));

		return true;
	}

	public function unapprovePosts(array $postIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		list($posts, $teams, $categories) = $this->getPostsAndParentData($postIds);

		if (empty($options['skipPermissions']) && !$this->canApproveUnapprovePostsData($posts, $teams, $categories, $errorKey, $viewingUser))
		{
			return false;
		}

		$this->_updatePostsBulk($posts, $teams, array('message_state' => 'moderated'));
		return true;
	}

	public function canStickUnstickPosts(array $postIds, &$errorKey = '', array $viewingUser = null)
	{
		list ($posts, $teams, $categories) = $this->getPostsAndParentData($postIds);
		return $this->canStickUnstickPostsData($posts, $teams, $categories, $errorKey, $viewingUser);
	}

	public function canStickUnstickPostsData(array $posts, array $teams, array $categories, &$errorKey = '',
		array $viewingUser = null)
	{
		if (!$posts)
		{
			return true;
		}

		$this->standardizeViewingUserReference($viewingUser);
		$postModel = $this->_getPostModel();

		foreach ($posts as $postId => $post)
		{
			list ($team, $category) = $this->_getTeamAndCategoryFromPost($post, $teams, $categories);
			if (!$postModel->canStickyOrUnstickyPost($post, $team, $category, $errorKey, $viewingUser))
			{
				return false;
			}
		}

		return true;
	}

	public function stickPosts(array $postIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		list($posts, $teams, $categories) = $this->getPostsAndParentData($postIds);

		if (empty($options['skipPermissions']) && !$this->canStickUnstickPostsData($posts, $teams, $categories, $errorKey, $viewingUser))
		{
			return false;
		}

		$this->_updatePostsBulk($posts, $teams, array('sticky' => 1));

		return true;
	}

	public function unstickPosts(array $postIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		list($posts, $teams, $categories) = $this->getPostsAndParentData($postIds);

		if (empty($options['skipPermissions']) && !$this->canStickUnstickPostsData($posts, $teams, $categories, $errorKey, $viewingUser))
		{
			return false;
		}

		$this->_updatePostsBulk($posts, $teams, array('sticky' => 0));

		return true;
	}

	protected function _updatePostsBulk(array $posts, array $teams, array $updates, $logAction = '')
	{
		foreach ($posts as $post)
		{
			$postDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Post', XenForo_DataWriter::ERROR_SILENT);
			if (!$postDw->setExistingData($post))
			{
				continue;
			}
			$postDw->bulkSet($updates);

			if (array_key_exists($postDw->get('team_id'), $teams))
			{
				$postDw->setExtraData(Nobita_Teams_DataWriter_Post::TEAM_DATA, $teams[$postDw->get('team_id')]);
			}

			$postDw->save();

			if ($postDw->hasChanges() && $this->enableLogging && $logAction)
			{
				XenForo_Model_Log::logModeratorAction('team_post', $post, $logAction);
			}
		}
	}

	/**
	 * From a List of IDs, gets info about the profile posts and the users whose posts
	 * they are on.
	 *
	 * @param array $postIds List of IDs
	 *
	 * @return array Format: [0] => list of posts, [1] => list of teams, [2] => list of categories
	 */
	public function getPostsAndParentData(array $postIds)
	{
		$posts = $this->_getPostModel()->getPostsByIds($postIds);

		$teamIds = array();
		foreach ($posts AS $post)
		{
			$teamIds[$post['team_id']] = true;
		}
		$teams = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getTeamsByIds(array_keys($teamIds));

		$categoryIds = array();
		foreach ($teams as $team)
		{
			$categoryIds[$team['team_category_id']] = true;
		}
		$categories = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category')->getCategoriesByIds(array_keys($categoryIds));

		return array($posts, $teams, $categories);
	}

	protected function _getPostModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
	}
}
