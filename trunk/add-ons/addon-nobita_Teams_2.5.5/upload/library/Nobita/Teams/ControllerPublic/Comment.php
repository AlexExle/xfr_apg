<?php

class Nobita_Teams_ControllerPublic_Comment extends Nobita_Teams_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		list($comment, $team, $post) = $this->_getTeamHelper()->assertCommentValidAndViewable();
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
			$this->_getContentViewLink($comment, $team)
		);
	}

	public function actionInsert()
	{
		$this->_assertPostOnly();

		$inputData = $this->_input->filter(array(
			'content_id' => XenForo_Input::UINT,
			'content_type' => XenForo_Input::STRING
		));

		$message = $this->getHelper('Editor')->getMessageText('message', $this->_input);
		if(empty($message))
		{
			$message = $this->_input->filterSingle('message', XenForo_Input::STRING);
		}

		$message = XenForo_Helper_String::autoLinkBbCode($message);
		$inputData['message'] = $message;

		list($content, $team, $category) = $this->_getContentDataViewable($inputData['content_id'], $inputData['content_type']);
		$this->_assertCanCommentOnContent($content, $team, $category);

		$visitor = XenForo_Visitor::getInstance();

		$inputData = array_merge(array(
			'team_id' => $team['team_id'],
			'user_id' => $visitor['user_id'],
			'username' => $visitor['username']
		), $inputData);

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Comment');
		$dw->bulkSet($inputData);

		$dw->setExtraData(Nobita_Teams_DataWriter_Comment::TEAM_DATA, $team);
		$dw->setOption(Nobita_Teams_DataWriter_Comment::OPTION_MAX_TAGGED_USERS, $visitor->hasPermission('general', 'maxTaggedUsers'));

		$dw->preSave();
		if(!$dw->hasErrors())
		{
			$this->assertNotFlooding('post');
		}

		$dw->save();

		$commentId = $dw->get('comment_id');
		$commentModel = $this->_getCommentModel();

		$comment = $commentModel->getCommentById($commentId, array(
			'join' => Nobita_Teams_Model_Comment::FETCH_USER
		));
		$comment = $commentModel->prepareComment($comment, $team, $category);

		$viewParams = array(
			'team' => $team,
			'category' => $category,
			'comment' => $comment,
		);

		return $this->_getTeamViewWrapper('newsfeed', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Comment_Insert', 'Team_comment', $viewParams)
		);
	}

	public function actionEdit()
	{
		list($comment, $team, $category) = $this->_getTeamHelper()->assertCommentValidAndViewable();
		if(!$this->_getCommentModel()->canEditComment($comment, $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		if ($this->_request->isPost())
		{
			$message = $this->getHelper('Editor')->getMessageText('message', $this->_input);
			if(empty($message))
			{
				$message = $this->_input->filterSingle('message', XenForo_Input::STRING);
			}

			$message = XenForo_Helper_String::autoLinkBbCode($message);

			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Comment');
			$dw->setExistingData($comment);

			$dw->set('message', $message);
			$dw->setExtraData(Nobita_Teams_DataWriter_Comment::TEAM_DATA, $team);
			$dw->save();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
				$this->_getContentViewLink($comment, $team)
			);
		}
		else
		{
			$viewParams = array(
				'team' => $team,
				'category' => $category,
				'comment' => $comment,
			);

			return $this->_getTeamViewWrapper('newsfeed', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Comment_Edit', 'Team_comment_edit', $viewParams)
			);
		}
	}

	public function actionDelete()
	{
		list($comment, $team, $category) = $this->_getTeamHelper()->assertCommentValidAndViewable();

		if (!$this->_getCommentModel()->canDeleteComment($comment, $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		if ($this->_request->isPost())
		{
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Comment');
			$dw->setExistingData($comment);
			$dw->delete();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$this->_getContentViewLink($comment, $team)
			);
		}
		else
		{
			$viewParams = array(
				'team' => $team,
				'category' => $category,
				'comment' => $comment,
			);

			return $this->_getTeamViewWrapper('newsfeed', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Comment_Delete', 'Team_comment_delete_confirm', $viewParams)
			);
		}
	}

	protected function _getContentViewLink(array $comment, array $team)
	{
		switch($comment['content_type'])
		{
			case Nobita_Teams_Model_Comment::CONTENT_TYPE_POST:
				return Nobita_Teams_Link::buildTeamLink('posts', array('post_id' => $comment['content_id']), array(
					'comment_id' => $comment['comment_id']
				))."#comment-$comment[comment_id]";
			case Nobita_Teams_Model_Comment::CONTENT_TYPE_EVENT:
				$commentModel = $this->_getCommentModel();
				$beforeComment = $commentModel->countComments(array(
					'event_id' => $comment['content_id'],
					'comment_date' => array('>', $comment['comment_date'])
				));

				$event = $this->_getEventModel()->getEventById($comment['content_id']);
				$page = floor($beforeComment/Nobita_Teams_Option::get('messagesPerPage')) + 1;

				return Nobita_Teams_Link::buildTeamLink('events', $event, array(
					'page' => $page,
				)) . "#comment-$comment[comment_id]";
				break;
			default:
				return Nobita_Teams_Link::buildTeamLink();
		}
	}

	protected function _getContentDataViewable($contentId, $contentType)
	{
		$content = array();
		$team = array();
		$category = array();

		if($contentType == Nobita_Teams_Model_Comment::CONTENT_TYPE_POST)
		{
			list($content, $team, $category) = $this->_getTeamHelper()->assertPostValidAndViewable($contentId);
		}
		elseif($contentType == Nobita_Teams_Model_Comment::CONTENT_TYPE_EVENT)
		{
			list($content, $team, $category) = $this->_getTeamHelper()->assertEventValidAndViewable($contentId);
		}

		if(!$team || !$category)
		{
			throw $this->getNoPermissionResponseException();
		}

		return array($content, $team, $category);
	}

	public function actionHistory()
	{
		$this->_assertPostOnly();

		$inputData = $this->_input->filter(array(
			'content_id' => XenForo_Input::UINT,
			'content_type' => XenForo_Input::STRING,
			'is_previous' => XenForo_Input::BOOLEAN,
			'content_key' => XenForo_Input::STRING,
			'comment_date' => XenForo_Input::UINT,
		));

		list($content, $team, $category) = $this->_getContentDataViewable($inputData['content_id'], $inputData['content_type']);

		$messagesPerPage = Nobita_Teams_Option::get('messagesPerPage');

		$comments = array();
		$commentModel = $this->_getCommentModel();
		$commentsUnshown = 0;

		if($inputData['comment_date'])
		{
			$compareDate = $inputData['is_previous'] ? '<' : '>';

			$conditions = array(
				'content_id' => $inputData['content_id'],
				'content_type' => $inputData['content_type'],
				'comment_date' => array($compareDate, $inputData['comment_date'])
			);
			$fetchOptions = array(
				'join' => Nobita_Teams_Model_Comment::FETCH_USER
						| Nobita_Teams_Model_Comment::FETCH_TEAM
						| Nobita_Teams_Model_Comment::FETCH_BBCODE_CACHE,
				'likeUserId' => XenForo_Visitor::getUserId(),
				'order' => 'recent_comment',
				'limit' => $messagesPerPage
			);

			$comments = $commentModel->getComments($conditions, $fetchOptions);
			$comments = array_reverse($comments);

			foreach($comments as $commentId => &$comment)
			{
				$comment["$inputData[content_type]Data"] = $content;
				if(!$commentModel->canViewComment($comment, $team, $category))
				{
					unset($comments[$commentId]);
					continue;
				}

				$comment = $commentModel->prepareComment($comment, $team, $category);
			}

			$commentsUnshown = $commentModel->countComments($conditions);
		}

		$viewParams = array(
			'comments' => $comments,
			'commentsUnshown' => $commentsUnshown,
		);

		return $this->_getTeamViewWrapper('newsfeed', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Comment_History', 'Team_comment_list', $viewParams)
		);
	}

	protected function _assertCanCommentOnContent(array $content, array $team, array $category)
	{
		$contentType = $this->_getCommentModel()->getContentTypeFromContentData($content);
		if($contentType == Nobita_Teams_Model_Comment::CONTENT_TYPE_POST)
		{
			// Content is post
			if(!$this->_getPostModel()->canCommentOnPost($content, $team, $category, $error))
			{
				throw $this->getErrorOrNoPermissionResponseException($error);
			}
		}
		elseif($contentType == Nobita_Teams_Model_Comment::CONTENT_TYPE_EVENT)
		{
			// Content is event
			if(!$this->_getEventModel()->canCommentOnEvent($content, $team, $category, $error))
			{
				throw $this->getErrorOrNoPermissionResponseException($error);
			}
		}
		else
		{
			// Unknow content?
			throw $this->getNoPermissionResponseException();
		}
	}

	public function actionLike()
	{
		$this->_assertPostOnly();
		list($comment, $team, $category) = $this->_getTeamHelper()->assertCommentValidAndViewable();

		if (!$this->_getCommentModel()->canLikeComment($comment, $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$likeModel = Nobita_Teams_Container::getModel('XenForo_Model_Like');
		$existingLike = $likeModel->getContentLikeByLikeUser(
			'team_comment', $comment['comment_id'], XenForo_Visitor::getUserId()
		);

		if ($existingLike)
		{
			$latestUsers = $likeModel->unlikeContent($existingLike);
		}
		else
		{
			$latestUsers = $likeModel->likeContent('team_comment', $comment['comment_id'], $comment['user_id']);
		}

		$liked = ($existingLike ? false : true);
		if ($this->_noRedirect())
		{
			$comment['likeUsers'] = $latestUsers;
			$comment['likes'] += ($liked ? 1 : -1);
			$comment['like_date'] = ($liked ? XenForo_Application::$time : 0);

			$viewParams = array(
				'comment' => $comment,
				'liked' => $liked,
			);

			return $this->responseView('Nobita_Teams_ViewPublic_Comment_LikeConfirmed', '', $viewParams);
		}
		else
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$this->_getContentViewLink($comment, $team)
			);
		}
	}

	public function actionLikes()
	{
		list($comment, $team, $category) = $this->_getTeamHelper()->assertCommentValidAndViewable();

		$likes =  Nobita_Teams_Container::getModel('XenForo_Model_Like')->getContentLikes(
			'team_comment', $comment['comment_id']
		);
		if (! $likes)
		{
			return $this->responseError(new XenForo_Phrase('no_one_has_liked_this_post_yet'));
		}

		$viewParams = array(
			'comment' => $comment,
			'team' => $team,
			'category' => $category,
			'likes' => $likes
		);

		return $this->_getTeamViewWrapper('wall', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Post_Likes', 'Team_comment_likes', $viewParams)
		);
	}
}
