<?php

class Nobita_Teams_ControllerPublic_InlineMod_Post extends XenForo_ControllerPublic_InlineMod_Abstract
{
	/**
	 * Key for inline mod data.
	 *
	 * @var string
	 */
	public $inlineModKey = 'teamPosts';

	public function getInlineModTypeModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_InlineMod_Post');
	}

	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			$hardDelete = $this->_input->filterSingle('hard_delete', XenForo_Input::STRING);
			$options = array(
				'deleteType' => ($hardDelete ? 'hard' : 'soft'),
				'reason' => $this->_input->filterSingle('reason', XenForo_Input::STRING)
			);

			return $this->executeInlineModAction('deletePosts', $options, array('fromCookie' => false));
		}
		else // show confirmation dialog
		{
			$postIds = $this->getInlineModIds();

			$handler = $this->_getInlineModPostModel();
			if (!$handler->canDeletePosts($postIds))
			{
				return $this->responseNoPermission();
			}

			$redirect = $this->getDynamicRedirect();

			if (!$postIds)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					$redirect
				);
			}

			$posts = $this->_getPostModel()->getPostsByIds($postIds, array(
				'join' => XenForo_Model_Post::FETCH_THREAD
			));

			$viewParams = array(
				'postIds' => $postIds,
				'postCount' => count($postIds),
				'redirect' => $redirect
			);

			return $this->responseView('XenForo_ViewPublic_InlineMod_Post_Delete', 'Team_inline_mod_post_delete', $viewParams);
		}
	}

	public function actionApprove()
	{
		return $this->executeInlineModAction('approvePosts');
	}

	public function actionUnapprove()
	{
		return $this->executeInlineModAction('unapprovePosts');
	}

	public function actionStick()
	{
		return $this->executeInlineModAction('stickPosts');
	}

	public function actionUnstick()
	{
		return $this->executeInlineModAction('unstickPosts');
	}

	protected function _getPostModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
	}

	protected function _getInlineModPostModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_InlineMod_Post');
	}
}
