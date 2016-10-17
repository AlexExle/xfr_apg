<?php

class Nobita_Teams_ControllerPublic_Post extends Nobita_Teams_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$ftpHelper = $this->_getTeamHelper();
		list($post, $team, $category) = $ftpHelper->assertPostValidAndViewable();

		$postModel = $this->_getPostModel();
		$commentModel = $this->_getCommentModel();

		$commentId = $this->_input->filterSingle('comment_id', XenForo_Input::UINT);
		$comment = $commentModel->getCommentById($commentId);

		$conditions = array(
			'post_id' => $post['post_id']
		);

		if($comment)
		{
			if($comment['content_type'] == Nobita_Teams_Model_Comment::CONTENT_TYPE_POST
				&& $comment['content_id'] == $post['post_id']
			)
			{
				// Just show special comment on post?
				$conditions['comment_date'] = array('<=', $comment['comment_date']);
			}
		}

		$fetchOptions = array(
			'join' => Nobita_Teams_Model_Comment::FETCH_USER
					| Nobita_Teams_Model_Comment::FETCH_TEAM
					| Nobita_Teams_Model_Comment::FETCH_BBCODE_CACHE,
			'likeUserId' => XenForo_Visitor::getUserId(),
			'limit' => Nobita_Teams_Option::get('messagesPerPage'),
			'order' => 'recent_comment',
		);
		$comments = $commentModel->getComments($conditions, $fetchOptions);
		$comments = array_reverse($comments);

		foreach($comments as &$comment)
		{
			$comment = $commentModel->prepareComment($comment, $post, $team);
		}

		$post = $commentModel->attachCommentLoadingParams($post, $comments, 'post_id', 'post');
		$post['comments'] = $comments;

		$posts = array($post['post_id'] => $post);
		$posts = $postModel->getAndMergeAttachmentsIntoPosts($posts);

		$post = reset($posts);

		$images = array();
		if(!empty($post['attachments']))
		{
			foreach($post['attachments'] as $attachment)
			{
				if(!empty($attachment['thumbnail_width']) || !empty($attachment['thumbnail_height']))
				{
					$images[$attachment['attachment_id']] = $attachment;
				}
			}
		}

		$viewParams = array(
			'team' => $team,
			'category' => $category,

			// params need for template Team_newsfeed_card
			'post' => $post,
			'user' => $post,
			'contentViewLink' => Nobita_Teams_Link::buildTeamLink('posts', $post),
			'contentDate' => $post['post_date'],
			'cardId' => sprintf('post-%d', $post['post_id']),
			'shareVisibility' => $this->_getPostShareVisibility($post),

			'canViewAttachments' => $postModel->canViewAttachmentOnPost($post, $team, $category),
			'visibleCommentForm' => true,
			'useOwnMetaProperty' => true,
			'images' => $images,
		);

		$subView = $this->responseView('Nobita_Teams_ViewPublic_Post_Show', 'Team_newsfeed_card', $viewParams);
		return $this->_getTeamViewWrapper('newsfeed', $team, $category, $subView);
	}

	protected function _getPostShareVisibility(array $post)
	{
		if($post['share_privacy'] == 'public')
		{
			return new XenForo_Phrase('Teams_public');
		}

		return new XenForo_Phrase(sprintf('Teams_%s_only', $post['share_privacy']));
	}

	public function actionEdit()
	{
		$ftpHelper = $this->_getTeamHelper();
		list($post, $team, $category) = $ftpHelper->assertPostValidAndViewable();

		$this->_assertCanEditPost($post, $team, $category);

		$postModel = $this->_getPostModel();
		$attachmentModel = Nobita_Teams_Container::getModel('XenForo_Model_Attachment');

		$attachmentParams = $this->_getTeamModel()->getAttachmentParams($team, $category, array(
			'post_id' => $post['post_id'],
			'content_type' => 'team_post'
		));

		$attachments = $attachmentModel->getAttachmentsByContentId('team_post', $post['post_id']);
		$shareable = $this->_getPostModel()->getSharePrivacy($team);

		$viewParams = array(
			'post' => $post,
			'team' => $team,
			'category' => $category,

			'shareable' => $shareable,

			'attachmentParams' => $attachmentParams,
			'attachments' => $attachmentModel->prepareAttachments($attachments),
			'attachmentConstraints' => $attachmentModel->getAttachmentConstraints()
		);

		return $this->_getTeamViewWrapper('newsfeed', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Post_Edit', 'Team_post_edit', $viewParams)
		);
	}

	public function actionInsert()
	{
		$this->_assertPostOnly();
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$visitor = XenForo_Visitor::getInstance();

		$teamModel = $this->_getTeamModel();
		$memberModel = $this->_getMemberModel();

		$memberRecord = $memberModel->getTeamMemberRecord($team['team_id']);

		$sharePrivacy = $this->_input->filterSingle('share_privacy', XenForo_Input::STRING);
		if (!$teamModel->canPostOnTeam($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$shareable = $this->_getPostModel()->getSharePrivacy($team);
		if(!in_array($sharePrivacy, array_keys($shareable)))
		{
			// Throw no permission to user...
			return $this->responseNoPermission();
		}

		$attachmentHash = $this->_input->filterSingle('attachment_hash', XenForo_Input::STRING);

		$message = $this->getHelper('Editor')->getMessageText('message', $this->_input);
		$message = XenForo_Helper_String::autoLinkBbCode($message);

		$writer = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Post');

		$writer->set('team_id', $team['team_id']);
		$writer->set('user_id', $visitor['user_id']);
		$writer->set('username', $visitor['username']);
		$writer->set('message', $message);
		$writer->set('share_privacy', $sharePrivacy);

		$writer->setExtraData(Nobita_Teams_DataWriter_Post::DATA_ATTACHMENT_HASH, $attachmentHash);
		$writer->setExtraData(Nobita_Teams_DataWriter_Post::TEAM_DATA, $team);
		$writer->setOption(Nobita_Teams_DataWriter_Post::OPTION_MAX_TAGGED_USERS, $visitor->hasPermission('general', 'maxTaggedUsers'));

		if($team['always_moderate_posting'])
		{
			$messageState = 'moderated';
			if(($memberRecord && $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'approveUnapprovePost'))
				|| $visitor->hasPermission('Teams', 'approveUnapprovePost')
			)
			{
				$messageState = 'visible';
			}

			$writer->set('message_state', $messageState);
		}

		/** @var $spamModel XenForo_Model_SpamPrevention */
		$spamModel = Nobita_Teams_Container::getModel('XenForo_Model_SpamPrevention');
		if (!$writer->hasErrors()
			&& $writer->get('message_state') == 'visible'
			&& $spamModel->visitorRequiresSpamCheck()
		)
		{
			switch ($spamModel->checkMessageSpam($message, array(), $this->_request))
			{
				case XenForo_Model_SpamPrevention::RESULT_MODERATED:
					$writer->set('message_state', 'moderated');
					break;
				case XenForo_Model_SpamPrevention::RESULT_DENIED;
					$writer->error(new XenForo_Phrase('your_content_cannot_be_submitted_try_later'));
					break;
			}
		}

		$writer->preSave();
		if (!$writer->hasErrors())
		{
			$this->assertNotFlooding('post');
		}

		$writer->save();

		$postId = $writer->get('post_id');

		$postModel = $this->_getPostModel();
		$post = $postModel->getPostById($postId, array(
			'join' => Nobita_Teams_Model_Post::FETCH_POSTER
					| Nobita_Teams_Model_Post::FETCH_BBCODE_CACHE,
			'watchUserId' => $visitor['user_id']
		));
		$post = $postModel->getAndMergeAttachmentsIntoPost($post);

		$post = $postModel->preparePost($post, $team, $category);
		$post['comments'] = array();

		$viewParams = array(
			'team' => $team,
			'category' => $category,
			'post' => $post,
			'user' => $post,
			'contentViewLink' => Nobita_Teams_Link::buildTeamLink('posts', $post),
			'contentDate' => $post['post_date'],
			'shareVisibility' => $this->_getPostShareVisibility($post),

			'cardId' => sprintf('post-%d', $post['post_id']),
			'canViewAttachments' => $postModel->canViewAttachmentOnPost($post, $team, $category),
		);

		return $this->_getTeamViewWrapper('newsfeed', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Post_NewPost', 'Team_newsfeed_card', $viewParams)
		);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$ftpHelper = $this->_getTeamHelper();
		list($post, $team, $category) = $ftpHelper->assertPostValidAndViewable();

		$this->_assertCanEditPost($post, $team, $category);

		$input = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING
		));

		$input['message'] = $this->getHelper('Editor')->getMessageText('message', $this->_input);
		$input['message'] = XenForo_Helper_String::autoLinkBbCode($input['message']);

		$sharePrivacy = $this->_input->filterSingle('share_privacy', XenForo_Input::STRING);
		$shareable = $this->_getPostModel()->getSharePrivacy($team);
		if(!in_array($sharePrivacy, array_keys($shareable)))
		{
			// Throw no permission to user...
			return $this->responseNoPermission();
		}

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Post');
		$dw->setExistingData($post['post_id']);
		$dw->set('message', $input['message']);
		$dw->set('share_privacy', $sharePrivacy);
		$dw->setExtraData(Nobita_Teams_DataWriter_Post::TEAM_DATA, $team);
		$dw->setExtraData(Nobita_Teams_DataWriter_Post::DATA_ATTACHMENT_HASH, $input['attachment_hash']);

		$spamModel = $this->_getSpamPreventionModel();

		if (!$dw->hasErrors()
			&& $dw->get('message_state') == 'visible'
			&& $spamModel->visitorRequiresSpamCheck()
		)
		{
			$spamExtraParams = array(
				'permalink' => Nobita_Teams_Link::buildTeamLink('', $team)
			);
			switch ($spamModel->checkMessageSpam($input['message'], $spamExtraParams, $this->_request))
			{
				case XenForo_Model_SpamPrevention::RESULT_MODERATED:
				case XenForo_Model_SpamPrevention::RESULT_DENIED;
					$dw->error(new XenForo_Phrase('your_content_cannot_be_submitted_try_later'));
					break;
			}
		}

		$dw->save();
		return $this->getPostSpecificRedirect($dw->getMergedData(), $team);
	}

	public function actionPin()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));
		list($post, $team, $category) = $this->_getTeamHelper()->assertPostValidAndViewable();

		if(!$this->_getPostModel()->canStickUnstickPost($post, $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Post');
		$dw->setExistingData($post);
		$dw->set('sticky', !$post['sticky']);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildTeamLink(null, $team)
		);
	}

	protected function _assertCanEditPost(array $post, array $team, array $category)
	{
		if (!$this->_getPostModel()->canEditPost($post, $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}

	public function actionDelete()
	{
		list($post, $team, $category) = $this->_getTeamHelper()->assertPostValidAndViewable();
		if (!$this->_getPostModel()->canDeletePost($post, $team, $category, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}

		if ($this->isConfirmedPost())
		{
			$this->_getPostModel()->deletePost($post, $team);

			XenForo_Helper_Cookie::clearIdFromCookie($post['post_id'], 'inlinemod_teamPosts');

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink(null, $team)
			);
		}
		else
		{
			$viewParams = array(
				'post' => $post,
				'team' => $team,
				'category' => $category,
				//'redirect' => $this->getDynamicRedirect(Nobita_Teams_Link::buildTeamLink('', $team))
			);

			return $this->_getTeamViewWrapper('newsfeed', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Post_Delete', 'Team_post_delete', $viewParams)
			);
		}
	}

	public function actionReport()
	{
		list($post, $team, $category) = $this->_getTeamHelper()->assertPostValidAndViewable();

		if (!$post['canReport'])
		{
			return $this->responseNoPermission();
		}

		if ($this->isConfirmedPost())
		{
			$reportMessage = $this->_input->filterSingle('message', XenForo_Input::STRING);
			if (!$reportMessage)
			{
				return $this->responseError(new XenForo_Phrase('please_enter_reason_for_reporting_this_message'));
			}

			$this->assertNotFlooding('report');

			/* @var $reportModel XenForo_Model_Report */
			$reportModel = Nobita_Teams_Container::getModel('XenForo_Model_Report');
			$reportModel->reportContent('team_post', $post, $reportMessage);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('', $team),
				new XenForo_Phrase('thank_you_for_reporting_this_message')
			);
		}
		else
		{
			$viewParams = array(
				'post' => $post,
				'team' => $team,
				'category' => $category
			);

			return $this->_getTeamViewWrapper('newsfeed', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Post_Report', 'Team_post_report', $viewParams)
			);
		}
	}

	public function actionApprove()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));

		list($post, $team, $category) = $this->_getTeamHelper()->assertPostValidAndViewable();
		if (!$this->_getPostModel()->canApproveUnapprove($post, $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Post', XenForo_DataWriter::ERROR_SILENT);
		$dw->setExistingData($post['post_id']);
		$dw->setExtraData(Nobita_Teams_DataWriter_Post::TEAM_DATA, $team);

		$dw->set('message_state', 'visible');
		$dw->save();

		return $this->getPostSpecificRedirect($post, $team);
	}

	public function actionLike()
	{
		list($post, $team, $category) = $this->_getTeamHelper()->assertPostValidAndViewable();

		$postModel = $this->_getPostModel();
		if (!$postModel->canLikePost($post, $team, $category, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}

		$likeModel = Nobita_Teams_Container::getModel('XenForo_Model_Like');
		$existingLike = $likeModel->getContentLikeByLikeUser('team_post', $post['post_id'], XenForo_Visitor::getUserId());

		if ($this->_request->isPost())
		{
			if ($existingLike)
			{
				$latestUsers = $likeModel->unlikeContent($existingLike);
			}
			else
			{
				$postModel->watch($post['post_id'], XenForo_Visitor::getUserId());
				$latestUsers = $likeModel->likeContent('team_post', $post['post_id'], $post['user_id']);
			}

			$liked = ($existingLike ? false : true);
			if ($this->_noRedirect())
			{
				$post['likeUsers'] = $latestUsers;
				$post['likes'] += ($liked ? 1 : -1);
				$post['like_date'] = ($liked ? XenForo_Application::$time : 0);

				$viewParams = array(
					'post' => $post,
					'liked' => $liked,
				);

				return $this->responseView('Nobita_Teams_ViewPublic_Post_LikeConfirmed', '', $viewParams);
			}
			else
			{
				return $this->getPostSpecificRedirect($post, $team);
			}
		}
		else
		{
			$viewParams = array(
				'post' => $post,
				'like' => $existingLike,
				'category' => $category,
				'team' => $team
			);

			return $this->_getTeamViewWrapper('newsfeed', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Post_Like', 'Team_post_like', $viewParams)
			);
		}
	}

	public function actionWatch()
	{
		$this->_assertPostOnly();
		$this->_assertRegistrationRequired();

		list($post, $team, $category) = $this->_getTeamHelper()->assertPostValidAndViewable();

		$postModel = $this->_getPostModel();
		$userId = XenForo_Visitor::getUserId();

		$watched = $postModel->getWatchRecord($post['post_id'], $userId);

		if($watched)
		{
			$postModel->unwatch($post['post_id'], $userId);
			$phraseName = 'Teams_get_notifications';
		}
		else
		{
			$postModel->watch($post['post_id'], $userId);
			$phraseName = 'Teams_stop_notifications';
		}

		return $this->getPostSpecificRedirect($post, $team,
			XenForo_ControllerResponse_Redirect::SUCCESS,
			array(
				'linkPhrase' => new XenForo_Phrase($phraseName)
			)
		);
	}

	public function actionLikes()
	{
		list($post, $team, $category) = $this->_getTeamHelper()->assertPostValidAndViewable();

		$likes =  Nobita_Teams_Container::getModel('XenForo_Model_Like')->getContentLikes('team_post', $post['post_id']);
		if (!$likes)
		{
			return $this->responseError(new XenForo_Phrase('no_one_has_liked_this_post_yet'));
		}

		$viewParams = array(
			'post' => $post,
			'team' => $team,
			'category' => $category,
			'likes' => $likes
		);

		return $this->_getTeamViewWrapper('newsfeed', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Post_Likes', 'Team_post_likes', $viewParams)
		);
	}

	protected function _getSpamPreventionModel()
	{
		return Nobita_Teams_Container::getModel('XenForo_Model_SpamPrevention');
	}
}
