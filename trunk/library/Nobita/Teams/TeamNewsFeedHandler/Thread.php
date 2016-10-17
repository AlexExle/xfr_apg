<?php

class Nobita_Teams_TeamNewsFeedHandler_Thread extends Nobita_Teams_TeamNewsFeedHandler_Abstract
{
	private $_threadModel;

	public function getContentsByIds(array $contentIds, XenForo_Model $model, array $viewingUser)
	{
		return $this->_getThreadModel()->getThreadsByIds($contentIds, array(
			'join' => XenForo_Model_Thread::FETCH_AVATAR
					| XenForo_Model_Thread::FETCH_FORUM
					| XenForo_Model_Thread::FETCH_FIRSTPOST,
			Nobita_Teams_Listener::THREAD_FETCHOPTIONS_JOIN_TEAM => true,
			'permissionCombinationId' => $viewingUser['permission_combination_id'],
			'watchUserId' => $viewingUser['user_id']
		));
	}

	public function canViewItem(array $item, array $content, array $viewingUser)
	{
		$nodePermissions = XenForo_Permission::unserializePermissions($content['node_permission_cache']);

		return $this->_getThreadModel()->canViewThreadAndContainer($content, $content, $null, $nodePermissions, $viewingUser);
	}

	public function prepareContent(array $item, array $content, array $viewingUser)
	{
		$nodePermissions = XenForo_Permission::unserializePermissions($content['node_permission_cache']);
		return $this->_getThreadModel()->prepareThread($content, $content, $nodePermissions, $viewingUser);
	}

	public function getContentViewLink(array $content)
	{
		return XenForo_Link::buildPublicLink('threads', $content);
	}

	public function getContentDate(array $content)
	{
		return $content['post_date'];
	}

	public function getNewsFeedActivity(array $feedItem)
	{
		return new XenForo_Phrase('Teams_x_created_an_thread', array('user' => ''));
	}

	public function getContentStatePhrase(array $content)
	{
		if($content['discussion_state'] == 'moderated'
			|| $content['discussion_state'] == 'deleted'
		)
		{
			return new XenForo_Phrase(sprintf('Teams_%s', $content['discussion_state']));
		}
	}

	public function renderContentHtml(XenForo_View $view, array $feedItem, array $content)
	{
		$templateName = $this->_getContentTemplate($feedItem['content_type']);
		$parser = XenForo_BbCode_Parser::create(
			XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_Text')
		);

		$content['messageText'] = $parser->render($content['message']);

		$threadModel = $this->_getThreadModel();

		$params = array(
			'thread' => $content,
			'canEditThread' => $threadModel->canEditThread($content, $content),
			'canDeleteThread' => $threadModel->canDeleteThread($content, $content),
		) + $view->getParams();

		return $view->createTemplateObject($templateName, $params)->render();
	}

	protected function _getThreadModel()
	{
		if(!$this->_threadModel)
		{
			$this->_threadModel = Nobita_Teams_Container::getModel('XenForo_Model_Thread');
		}

		return $this->_threadModel;
	}
}
