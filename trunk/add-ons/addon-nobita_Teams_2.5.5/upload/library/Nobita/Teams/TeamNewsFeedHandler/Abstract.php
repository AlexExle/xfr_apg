<?php

abstract class Nobita_Teams_TeamNewsFeedHandler_Abstract
{
	public static function create($class)
	{
		if (XenForo_Application::autoload($class))
		{
			$class = XenForo_Application::resolveDynamicClass($class);
			$obj = new $class();
			if ($obj instanceof Nobita_Teams_TeamNewsFeedHandler_Abstract)
			{
				return $obj;
			}
		}

		throw new InvalidArgumentException("Invalid news feed handler '$class' specified");
	}

	abstract public function getContentsByIds(array $contentIds, XenForo_Model $model, array $viewingUser);
	abstract public function canViewItem(array $item, array $content, array $viewingUser);

	abstract public function prepareContent(array $item, array $content, array $viewingUser);
	abstract public function getContentViewLink(array $content);
	abstract public function getContentDate(array $content);

	abstract public function renderContentHtml(XenForo_View $view, array $feedItem, array $content);

	public function getNewsFeedActivity(array $feedItem)
	{
		// Should be return string or XenForo_Phrase object
	}

	public function getContentStatePhrase(array $content)
	{
		// Should be overwriten by child
	}

	public function getContentShareVisibility(array $content)
	{
		return new XenForo_Phrase('Teams_public');
	}

	protected function _getContentTemplate($contentType)
	{
		return sprintf('Team_newsfeed_item_%s', $contentType);
	}
}
