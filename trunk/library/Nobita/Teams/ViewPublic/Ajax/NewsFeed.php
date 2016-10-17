<?php

class Nobita_Teams_ViewPublic_Ajax_NewsFeed extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		XenForo_Application::set('view', $this);

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
	}

	public function renderJson()
	{
		$output = $this->_renderer->getDefaultOutputArray(get_class($this), $this->_params, $this->_templateName);
		if($this->_params['isNextPage'])
		{
			$output['nextPageUrl'] = $this->_params['nextPageUrl'];
		}

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}
