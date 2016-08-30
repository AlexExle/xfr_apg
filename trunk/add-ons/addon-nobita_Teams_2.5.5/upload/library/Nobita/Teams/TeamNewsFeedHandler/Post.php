<?php

class Nobita_Teams_TeamNewsFeedHandler_Post extends Nobita_Teams_TeamNewsFeedHandler_Abstract
{
	private $_postModel;
	private $_commentModel;

	public function getContentsByIds(array $contentIds, XenForo_Model $model, array $viewingUser)
	{
		$posts = $this->_getPostModel()->getPosts(array(
			'post_id' => $contentIds,
			'sticky' => 0
		), array(
			'join' => Nobita_Teams_Model_Post::FETCH_TEAM
					| Nobita_Teams_Model_Post::FETCH_BBCODE_CACHE
					| Nobita_Teams_Model_Post::FETCH_POSTER,
			'likeUserId' => $viewingUser['user_id'],
			'watchUserId' => $viewingUser['user_id'],
		));

		$posts = $this->_getCommentModel()->addCommentsToContentList($posts);
		$posts = $this->_getPostModel()->getAndMergeAttachmentsIntoPosts($posts);

		return $posts;
	}

	public function canViewItem(array $item, array $content, array $viewingUser)
	{
		return $this->_getPostModel()->canViewPostAndContainer(
			$content, $content, $content, $null, $viewingUser
		);
	}

	public function prepareContent(array $item, array $content, array $viewingUser)
	{
		return $this->_getPostModel()->preparePost($content, $content, $content, $viewingUser);
	}

	public function getContentViewLink(array $content)
	{
		return Nobita_Teams_Link::buildTeamLink('posts', $content);
	}

	public function getContentShareVisibility(array $content)
	{
		return $this->_getPostModel()->getPostShareVisibility($content);
	}

	public function getContentStatePhrase(array $content)
	{
		if($content['message_state'] == 'moderated')
		{
			return new XenForo_Phrase('Teams_moderated');
		}
	}

	public function getContentDate(array $content)
	{
		return $content['post_date'];
	}

	public function renderContentHtml(XenForo_View $view, array $feedItem, array $content)
	{
		$templateName = $this->_getContentTemplate($feedItem['content_type']);
		$postModel = $this->_getPostModel();

		$params = $view->getParams();
		$params['post'] = $content;

		$bbCodeBase = XenForo_BbCode_Formatter_Base::create('Base', array(
			'view' => $view
		));
		$commentBbCode = XenForo_BbCode_Formatter_Base::create('Nobita_Teams_BbCode_Formatter_Comment', array(
			'view', $view
		));

		$bbCodeParser = XenForo_BbCode_Parser::create($bbCodeBase);
		$commentBbCodeParser = XenForo_BbCode_Parser::create($commentBbCode);

		$bbCodeOptions = array(
			'states' => array(
				'viewAttachments' => isset($params['canViewAttachments']) ? $params['canViewAttachments'] : false,
			),
			'contentType' => 'team_post',
			'contentIdKey' => 'post_id'
		);

		$params['post']['messageHtml'] = XenForo_ViewPublic_Helper_Message::getBbCodeWrapper(
			$params['post'], $bbCodeParser, $bbCodeOptions
		);

		XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages($params['post']['comments'], $commentBbCodeParser, array(
			'contentType' => 'team_comment',
			'contentIdKey' => 'comment_id'
		));

		return $view->createTemplateObject($templateName, $params);
	}

	protected function _getPostModel()
	{
		if(!$this->_postModel)
		{
			$this->_postModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
		}

		return $this->_postModel;
	}

	protected function _getCommentModel()
	{
		if(!$this->_commentModel)
		{
			$this->_commentModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');
		}

		return $this->_commentModel;
	}
}
