<?php

class Nobita_Teams_Search_DataHandler_Event extends XenForo_Search_DataHandler_Abstract
{
	private $_eventModel;
	private $_teamModel;

	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
	{
		$metadata = array();

		$title = $data['event_title'];
		$metadata['team'] = $data['team_id'];

		$indexer->insertIntoIndex(
			'team_event', $data['event_id'],
			$title, $data['event_description'],
			$data['publish_date'], $data['user_id'], $data['team_id'], $metadata
		);
	}

	protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
	{
		$indexer->updateIndex('team_event', $data['event_id'], $fieldUpdates);
	}

	protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
	{
		$eventIds = array();
		foreach ($dataList AS $data)
		{
			$eventIds[] = is_array($data) ? $data['event_id'] : $data;
		}

		$indexer->deleteFromIndex('team_event', $eventIds);
	}

	public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
	{
		$events = $this->_getEventModel()->getEventsByIds($contentIds);

		$teamIds = array();
		foreach ($events AS $event)
		{
			$teamIds[] = $event['team_id'];
		}

		$teams = $this->_getTeamModel()->getTeamsByIds(array_unique($teamIds));

		foreach ($events AS $event)
		{
			$team = (isset($teams[$event['team_id']]) ? $teams[$event['team_id']] : null);
			if (!$team)
			{
				continue;
			}

			$this->insertIntoIndex($indexer, $event, $team);
		}

		return true;
	}

	public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
	{
		$eventIds = $this->_getEventModel()->getEventIdsInRange($lastId, $batchSize);
		if (!$eventIds)
		{
			return false;
		}

		$this->quickIndex($indexer, $eventIds);

		return max($eventIds);
	}

	public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
	{
		return $this->_getEventModel()->getEventsByIds($ids, array(
			'join' => Nobita_Teams_Model_Event::FETCH_USER
					| Nobita_Teams_Model_Event::FETCH_TEAM
		));
	}

	public function canViewResult(array $result, array $viewingUser)
	{
		return $this->_getEventModel()->canViewEventAndContainer(
			$result, $result, $result, $null, $viewingUser
		);
	}

	public function prepareResult(array $result, array $viewingUser)
	{
		$result = $this->_getEventModel()->prepareEvent($result, $result, $result, $viewingUser);
		$result['title'] = XenForo_Helper_String::censorString($result['event_title']);

		return $result;
	}

	public function getResultDate(array $result)
	{
		return $result['publish_date'];
	}

	public function renderResult(XenForo_View $view, array $result, array $search)
	{
		return $view->createTemplateObject('Team_search_result_event', array(
			'event' => $result,
			'team' => $result,
			'search' => $search,
			'enableInlineMod' => $this->_inlineModEnabled,
			'searchContentTypePhrase' => $this->getSearchContentTypePhrase()
		));
	}

	public function getSearchContentTypes()
	{
		return array('team_event', 'team');
	}

	public function getSearchContentTypePhrase()
	{
		return new XenForo_Phrase('Teams_handler_phrase_key_events');
	}

	protected function _getTeamModel()
	{
		if(!$this->_teamModel)
		{
			$this->_teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		}

		return $this->_teamModel;
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