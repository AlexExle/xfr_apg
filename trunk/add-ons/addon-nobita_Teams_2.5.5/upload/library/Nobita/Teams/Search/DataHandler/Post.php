<?php

class Nobita_Teams_Search_DataHandler_Post extends XenForo_Search_DataHandler_Abstract
{
	private $_postModel;
	private $_teamModel;

	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
	{
		if(!$this->_getPostModel()->isVisible($data))
		{
			return;
		}

		$metadata = array();

		$title = '';
		if(isset($parentData['title']))
		{
			$title = $parentData['title'];
		}

		$metadata['team'] = $data['team_id'];

		$indexer->insertIntoIndex(
			'team_post', $data['post_id'],
			$title, $data['message'],
			$data['post_date'], $data['user_id'], $data['team_id'], $metadata
		);
	}

	protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
	{
		$indexer->updateIndex('team_post', $data['post_id'], $fieldUpdates);
	}

	protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
	{
		$postIds = array();
		foreach ($dataList AS $data)
		{
			$postIds[] = is_array($data) ? $data['post_id'] : $data;
		}

		$indexer->deleteFromIndex('team_post', $postIds);
	}

	public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
	{
		$posts = $this->_getPostModel()->getPostsByIds($contentIds);

		$teamIds = array();
		foreach ($posts AS $post)
		{
			$teamIds[] = $post['team_id'];
		}

		$teams = $this->_getTeamModel()->getTeamsByIds(array_unique($teamIds));

		foreach ($posts AS $post)
		{
			$team = (isset($teams[$post['team_id']]) ? $teams[$post['team_id']] : null);
			if (!$team)
			{
				continue;
			}

			$this->insertIntoIndex($indexer, $post, $team);
		}

		return true;
	}

	public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
	{
		$postIds = $this->_getPostModel()->getPostIdsInRange($lastId, $batchSize);
		if (!$postIds)
		{
			return false;
		}

		$this->quickIndex($indexer, $postIds);

		return max($postIds);
	}

	public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
	{
		return $this->_getPostModel()->getPostsByIds($ids, array(
			'join' => Nobita_Teams_Model_Post::FETCH_POSTER
					| Nobita_Teams_Model_Post::FETCH_TEAM
		));
	}

	public function canViewResult(array $result, array $viewingUser)
	{
		return $this->_getPostModel()->canViewPostAndContainer(
			$result, $result, $result, $null, $viewingUser
		);
	}

	public function prepareResult(array $result, array $viewingUser)
	{
		$result = $this->_getPostModel()->preparePost($result, $result, $result, $viewingUser);
		$result['title'] = XenForo_Helper_String::censorString($result['title']);

		return $result;
	}

	public function getResultDate(array $result)
	{
		return $result['post_date'];
	}

	public function renderResult(XenForo_View $view, array $result, array $search)
	{
		return $view->createTemplateObject('Team_search_result_post', array(
			'post' => $result,
			'team' => $result,
			'search' => $search,
			'enableInlineMod' => $this->_inlineModEnabled,
			'searchContentTypePhrase' => $this->getSearchContentTypePhrase()
		));
	}

	public function getSearchContentTypes()
	{
		return array('team_post', 'team');
	}

	public function getSearchContentTypePhrase()
	{
		return new XenForo_Phrase('Teams_handler_phrase_key_posts');
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}

	protected function _getPostModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
	}
}