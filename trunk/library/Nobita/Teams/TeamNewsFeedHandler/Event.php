<?php

class Nobita_Teams_TeamNewsFeedHandler_Event extends Nobita_Teams_TeamNewsFeedHandler_Abstract
{
	private $_eventModel;
	private $_commentModel;

	public function getContentsByIds(array $contentIds, XenForo_Model $model, array $viewingUser)
	{
		$events = $this->_getEventModel()->getEventsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Event::FETCH_TEAM
					| Nobita_Teams_Model_Event::FETCH_USER
					| Nobita_Teams_Model_Event::FETCH_BBCODE_CACHE
		));

		return $this->_getCommentModel()->addCommentsToContentList($events);
	}

	public function canViewItem(array $item, array $content, array $viewingUser)
	{
		return $this->_getEventModel()->canViewEvent($content, $content, $content, $null, $viewingUser);
	}

	public function prepareContent(array $item, array $content, array $viewingUser)
	{
		return $this->_getEventModel()->prepareEvent($content, $content, $content, $viewingUser);
	}

	public function getContentViewLink(array $content)
	{
		return Nobita_Teams_Link::buildTeamLink('events', $content);
	}

	public function getContentDate(array $content)
	{
		return $content['publish_date'];
	}

	public function getNewsFeedActivity(array $feedItem)
	{
		return new XenForo_Phrase('Teams_x_created_an_event', array('user' => ''));
	}

	public function getContentShareVisibility(array $content)
	{
		if($content['event_type'] == 'public') 
		{
			return new XenForo_Phrase('Teams_public');
		}

		return new XenForo_Phrase(sprintf('Teams_%s_only', $content['event_type']));
	}

	public function renderContentHtml(XenForo_View $view, array $feedItem, array $content)
	{
		$templateName = $this->_getContentTemplate($feedItem['content_type']);

		$parser = XenForo_BbCode_Parser::create(
			XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_Text')
		);

		$commentBbCode = XenForo_BbCode_Formatter_Base::create('Nobita_Teams_BbCode_Formatter_Comment', array(
			'view', $view
		));

		$commentBbCodeParser = XenForo_BbCode_Parser::create($commentBbCode);
		XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages($content['comments'], $commentBbCodeParser, array(
			'contentType' => 'team_comment',
			'contentIdKey' => 'comment_id'
		));

		$content['descriptionText'] = $parser->render($content['event_description']);

		$params = array(
			'event' => $content
		) + $view->getParams();

		return $view->createTemplateObject($templateName, $params)->render();
	}

	protected function _getCommentModel()
	{
		if(!$this->_commentModel)
		{
			$this->_commentModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');
		}

		return $this->_commentModel;
	}

	protected function _getEventModel()
	{
		if(!$this->_eventModel)
		{
			$this->_eventModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
		}

		return $this->_eventModel;
	}
}
