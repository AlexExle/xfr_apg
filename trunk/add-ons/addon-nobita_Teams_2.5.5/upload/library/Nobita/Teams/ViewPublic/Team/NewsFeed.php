<?php

class Nobita_Teams_ViewPublic_Team_NewsFeed extends XenForo_ViewPublic_Base
{
    public function renderHtml()
    {
    	$bbCodeBase = XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this));
		$commentBbCode = XenForo_BbCode_Formatter_Base::create('Nobita_Teams_BbCode_Formatter_Comment', array('view', $this));

		$bbCodeParser = XenForo_BbCode_Parser::create($bbCodeBase);
		$commentBbCodeParser = XenForo_BbCode_Parser::create($commentBbCode);

		$bbCodeOptions = array(
			'states' => array(
				'viewAttachments' => $this->_params['canViewAttachments']
			),
			'contentType' => 'team_post',
			'contentIdKey' => 'post_id'
		);

		foreach($this->_params['posts'] as &$post)
		{
			$post['messageHtml'] = XenForo_ViewPublic_Helper_Message::getBbCodeWrapper(
				$post, $bbCodeParser, $bbCodeOptions
			);

			XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages($post['comments'], $commentBbCodeParser, array(
				'contentType' => 'team_comment',
				'contentIdKey' => 'comment_id'
			));
		}

		foreach($this->_params['newsFeeds'] as $newsFeedId => &$newsFeed)
		{
			if(!$newsFeed['handler'])
			{
				unset($this->_params['newsFeeds'][$newsFeedId]);
				continue;
			}

			$newsFeed['cardId'] = "newsfeed-$newsFeed[news_feed_id]";
			$this->setParams(['cardId' => $newsFeed['cardId']]);

			$contentHtml = $newsFeed['handler']->renderContentHtml($this, $newsFeed, $newsFeed['content']);
			$newsFeed['contentHtml'] = $contentHtml;
		}

        $this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getQuickReplyEditor($this, 'message', '');
    }
}
