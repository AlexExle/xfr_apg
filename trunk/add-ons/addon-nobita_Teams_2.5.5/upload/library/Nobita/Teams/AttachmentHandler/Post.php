<?php

class Nobita_Teams_AttachmentHandler_Post extends XenForo_AttachmentHandler_Post
{
	protected $_postModel = null;

	/**
	 * Key of primary content in content data array.
	 *
	 * @var string
	 */
	protected $_contentIdKey = 'post_id';

	/**
	 * Route to get to a post
	 *
	 * @var string
	 */
	protected $_contentRoute =  'groups/posts'; // xF will auto redirected to target route. Can't use ::routePrefix().

	/**
	 * Name of the phrase that describes the conversation_message content type
	 *
	 * @var string
	 */
	protected $_contentTypePhraseKey = 'Teams_handler_phrase_key_posts';

	/**
	 * Determines if attachments and be uploaded and managed in this context.
	 *
	 * @see XenForo_AttachmentHandler_Abstract::_canUploadAndManageAttachments()
	 */
	protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
	{
		$postModel = $this->_getPostModel();

		if (!empty($contentData['post_id']))
		{
			$post = $postModel->getPostById($contentData['post_id']);
			if ($post)
			{
				$contentData['team_id'] = $post['team_id'];
			}
		}

		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		if (!empty($contentData['team_id']))
		{
			$team = $teamModel->getTeamById($contentData['team_id'], array(
				'join' => Nobita_Teams_Model_Team::FETCH_PROFILE
					| Nobita_Teams_Model_Team::FETCH_PRIVACY
					| Nobita_Teams_Model_Team::FETCH_CATEGORY
			));

			if ($team)
			{
				if (!empty($contentData['post_id']))
				{
					if (!$postModel->canViewPost($post, $team, $team, $null, $viewingUser)
						|| !$postModel->canEditPost($post, $team, $team, $null, $viewingUser))
					{
						return false;
					}
				}

				return (
					$teamModel->canViewTeamAndContainer($team, $team, $null, $viewingUser)
					&& $teamModel->canUploadAndManageAttachment($team, $team, $null, $viewingUser)
				);
			}
		}

		return false; // invalid content data.
	}

	/**
	 * Determines if the specified attachment can be viewed.
	 *
	 * @see XenForo_AttachmentHandler_Abstract::_canViewAttachment()
	 */
	protected function _canViewAttachment(array $attachment, array $viewingUser)
	{
		$postModel = $this->_getPostModel();

		$post = $postModel->getPostById($attachment['content_id'], array(
			'join' => Nobita_Teams_Model_Post::FETCH_TEAM | Nobita_Teams_Model_Post::FETCH_POSTER
		));
		if (!$post)
		{
			return false;
		}

		/* @var $teamModel Nobita_Teams_Model_Team */
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		$team = $teamModel->getFullTeamById($post['team_id'], array('join' => Nobita_Teams_Model_Team::FETCH_CATEGORY));

		if (!$team)
		{
			return false;
		}

		if (!$teamModel->canViewTeamAndContainer($team, $team, $null, $viewingUser))
		{
			return false;
		}

		if (!$postModel->canViewPostAndContainer($post, $team, $team, $null, $viewingUser))
		{
			return false;
		}

		return $postModel->canViewAttachmentOnPost($post, $team, $team, $null, $viewingUser);
	}

	/**
	 * Code to run after deleting an associated attachment.
	 *
	 * @see XenForo_AttachmentHandler_Abstract::attachmentPostDelete()
	 */
	public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
	{
		$db->query('
			UPDATE xf_team_post
			SET attach_count = IF(attach_count > 0, attach_count - 1, 0)
			WHERE post_id = ?
		', $attachment['content_id']);
	}

	public function getContentLink(array $attachment, array $extraParams = array(), $skipPrepend = false)
	{
		$data = $this->getContentDataFromContentId($attachment['content_id']);
		return XenForo_Link::buildPublicLink(
			$this->_getContentRoute(), $data, $extraParams, $skipPrepend
		);
	}

	/**
	 * @return Nobita_Teams_Model_Post
	 */
	protected function _getPostModel()
	{
		if (!$this->_postModel)
		{
			$this->_postModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
		}

		return $this->_postModel;
	}

	/**
	 * @see XenForo_AttachmentHandler_Abstract::_getContentRoute()
	 */
	protected function _getContentRoute()
	{
		return Nobita_Teams_Option::get('routePrefix').'/posts';
	}
}
