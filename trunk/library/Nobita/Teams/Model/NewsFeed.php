<?php

class Nobita_Teams_Model_NewsFeed extends Nobita_Teams_Model_Abstract
{
	protected $_handlerCache = array();

	public function getNewsFeedById($feedId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_team_news_feed
			WHERE news_feed_id = ?
		', array($feedId));
	}

	public function getNewsFeedForTeam($teamId, array $conditions = array(), array $fetchOptions = array())
	{
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		$whereClause = $this->prepareNewsFeedConditions($conditions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT feed.*, team.*, profile.*, privacy.*, category.*
				FROM xf_team_news_feed AS feed
					INNER JOIN xf_team AS team
						ON (team.team_id = feed.team_id)
					INNER JOIN xf_team_profile AS profile
						ON (profile.team_id = team.team_id)
					INNER JOIN xf_team_privacy AS privacy
						ON (privacy.team_id = team.team_id)
					LEFT JOIN xf_team_category AS category
						ON (category.team_category_id = team.team_category_id)
				WHERE '. $whereClause .'
					AND feed.team_id = ?
				ORDER BY feed.event_date DESC
			', $limitOptions['limit'], $limitOptions['offset']
		), 'news_feed_id', array($teamId));
	}

	public function getNewsFeedPinnedForTeam($teamId)
	{
		return $this->fetchAllKeyed("
			SELECT feed.*, team.*, profile.*, privacy.*, category.*
			FROM xf_team_news_feed AS feed
				INNER JOIN xf_team AS team
					ON (team.team_id = feed.team_id)
				INNER JOIN xf_team_profile AS profile
					ON (profile.team_id = team.team_id)
				INNER JOIN xf_team_privacy AS privacy
					ON (privacy.team_id = team.team_id)
				LEFT JOIN xf_team_category AS category
					ON (category.team_category_id = team.team_category_id)
				INNER JOIN xf_team_post AS post ON (
					post.post_id = feed.content_id AND feed.content_type = 'post'
				)
			WHERE feed.team_id = ? AND post.sticky = '1'
			ORDER BY feed.event_date DESC
		", 'news_feed_id', array($teamId));
	}

	public function countNewsFeedForTeam($teamId, array $conditions = array())
	{
		$whereClause = $this->prepareNewsFeedConditions($conditions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_team_news_feed
			WHERE '. $whereClause .'
				AND team_id = ?
		', array($teamId));
	}

	public function getNewsFeedItemByKeys($teamId, $contentId, $contentType)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_team_news_feed
			WHERE team_id = ? AND content_id = ? AND content_type = ?
		', array($teamId, $contentId, $contentType));
	}

	public function prepareNewsFeedConditions(array $conditions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		foreach(array('news_feed_id', 'team_id', 'content_id', 'content_type') as $field)
		{
			if(empty($conditions[$field])) continue;

			$value = $db->quote($conditions[$field]);

			if(is_array($conditions[$field]))
			{
				$sqlConditions[] = "feed.{$field} IN ({$value})";
			}
			else
			{
				$sqlConditions[] = "feed.{$field} = {$value}";
			}
		}

		if(!empty($conditions['event_date']) && is_array($conditions['event_date']))
		{
			$sqlConditions[] = $this->getCutOffCondition('feed.event_date', $conditions['event_date']);
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function publish($teamId, $contentId, $contentType, array $extraData = array(), $eventDate = null)
	{
		$db = $this->_getDb();

		$existed = $this->getNewsFeedItemByKeys($teamId, $contentId, $contentType);
		if ($existed)
		{
			$time = isset($extraData['event_date']) ? $extraData['event_date'] : XenForo_Application::$time;
			$db->update('xf_team_news_feed', array('event_date' => $time), 'news_feed_id = ' . $db->quote($existed['news_feed_id']));
			return true;
		}

		if ($eventDate === null)
		{
			$eventDate = XenForo_Application::$time;
		}

		$db->insert('xf_team_news_feed', array(
			'team_id' => $teamId,
			'content_id' => $contentId,
			'content_type' => $contentType,
			'event_date' => $eventDate,
			'extra_data' => json_encode($extraData)
		));
	}

	public function fillOutNewsFeedItems(array $newsFeeds, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if ($newsFeeds)
		{
			$newsFeeds = $this->_getContentForNewsFeedItems($newsFeeds, $viewingUser);
			$newsFeeds = $this->_getViewableNewsFeedItems($newsFeeds, $viewingUser);
			$newsFeeds = $this->_prepareNewsFeedItems($newsFeeds, $viewingUser);
		}

		return $newsFeeds;
	}

	protected function _getContentForNewsFeedItems(array $data, array $viewingUser)
	{
		// group all content ids of each content type...
		$fetchQueue = array();
		foreach ($data AS $id => $item)
		{
			$fetchQueue[$item['content_type']][$item['news_feed_id']] = $item['content_id'];
		}

		// fetch data for all items of each content type in one go...
		$fetchData = array();
		foreach ($fetchQueue AS $contentType => $contentIds)
		{
			$handler = $this->getNewsFeedHandlerFromCache($contentType);
			if (!$handler)
			{
				continue;
			}

			$fetchData[$contentType] = $handler->getContentsByIds($contentIds, $this, $viewingUser);
		}

		// attach resulting content to each feed item...
		foreach ($data AS $id => $item)
		{
			if (!isset($fetchData[$item['content_type']][$item['content_id']]))
			{
				// For whatever reason, there was no related content found for this news feed item,
				// therefore remove it from this user's news feed
				unset($data[$id]);
				continue;
			}

			$data[$id]['content'] = $fetchData[$item['content_type']][$item['content_id']];
		}

		return $data;
	}

	protected function _getViewableNewsFeedItems(array $items, array $viewingUser)
	{
		foreach ($items AS $key => $item)
		{
			$handler = $this->getNewsFeedHandlerFromCache($item['content_type']);
			if (!$handler || !$handler->canViewItem($item, $item['content'], $viewingUser))
			{
				unset($items[$key]);
			}
		}

		return $items;
	}

	protected function _prepareNewsFeedItems(array $items, array $viewingUser)
	{
		foreach ($items AS $id => $item)
		{
			$items[$id] = $this->_prepareNewsFeedItem($item, $viewingUser);
		}

		return $items;
	}

	protected function _prepareNewsFeedItem(array $item, array $viewingUser)
	{
		$item['team'] = array(
			'team_id' => $item['team_id'],
			'title' => $item['title'],
			'custom_url' => $item['custom_url'],
			'team_state' => $item['team_state'],
			'privacy_state' => $item['privacy_state']
		);

		$item['category'] = array(
			'team_category_id' => $item['team_category_id'],
			'category_title' => $item['category_title']
		);

		$handler = $this->getNewsFeedHandlerFromCache($item['content_type']);
		$item['handler'] = $handler;

		$item['contentDate'] = $item['event_date'];

		if ($handler)
		{
			$item['content'] = $handler->prepareContent($item, $item['content'], $viewingUser);
			$contentDate = $handler->getContentDate($item['content']);

			if($contentDate !== false || $contentDate !== null)
			{
				$item['contentDate'] = $contentDate;
			}

			$item['contentViewLink'] = $handler->getContentViewLink($item['content']);
			$item['feedActivity'] = $handler->getNewsFeedActivity($item);
			$item['shareVisibility'] = $handler->getContentShareVisibility($item['content']);
			$item['contentStatePhrase'] = $handler->getContentStatePhrase($item['content']);
		}

		return $item;
	}

	public function delete($teamId, $contentId, $contentType)
	{
		$db = $this->_getDb();

		if (!is_array($contentId))
		{
			$contentId = array($contentId);
		}

		$condition = 'content_type = ' . $db->quote($contentType)
			. ' AND team_id = ' . $db->quote($teamId)
			. ' AND content_id IN (' . $db->quote($contentId) . ')';

		$db->delete('xf_team_news_feed', $condition);
	}

	public function deleteUsingId($feedId)
	{
		$db = $this->_getDb();

		$db->delete('xf_team_news_feed', 'news_feed_id = ' . $db->quote($feedId));
	}

	public function getNewsFeedHandler($contentType)
	{
		if(strpos($contentType, '_') === false)
		{
			return 'Nobita_Teams_TeamNewsFeedHandler_'.ucfirst($contentType);
		}

		throw new XenForo_Exception("Unknown class handler for content: '$contentType'");
	}

	public function getNewsFeedHandlerFromCache($contentType)
	{
		$class = $this->getNewsFeedHandler($contentType);
		if (!$class || !class_exists($class))
		{
			return false;
		}

		if (!isset($this->_handlerCache[$contentType]))
		{
			$this->_handlerCache[$contentType] = Nobita_Teams_TeamNewsFeedHandler_Abstract::create($class);
		}

		return $this->_handlerCache[$contentType];
	}
}
