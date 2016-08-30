<?php

class Nobita_Teams_Search_DataHandler_Comment extends XenForo_Search_DataHandler_Abstract
{
	private $_commentModel;
	private $_teamModel;

	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
	{
		$metadata = array();

		$title = '';
		if(isset($parentData['title']))
		{
			$title = $parentData['title'];
		}

		$metadata['team'] = $data['team_id'];
		$metadata['contentId'] = $data['content_id'];
		$metadata['contentType'] = $data['content_type'];

		$indexer->insertIntoIndex(
			'team_comment', $data['comment_id'],
			$title, $data['message'],
			$data['comment_date'], $data['user_id'], $data['team_id'], $metadata
		);
	}

	protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
	{
		$indexer->updateIndex('team_comment', $data['comment_id'], $fieldUpdates);
	}

	protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
	{
		$commentIds = array();
		foreach ($dataList AS $data)
		{
			$commentIds[] = is_array($data) ? $data['comment_id'] : $data;
		}

		$indexer->deleteFromIndex('team_comment', $commentIds);
	}

	public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
	{
		$comments = $this->_getCommentModel()->getCommentsByIds($contentIds);

		$teamIds = array();
		foreach ($comments AS $comment)
		{
			$teamIds[] = $comment['team_id'];
		}

		$teams = $this->_getTeamModel()->getTeamsByIds(array_unique($teamIds));

		foreach ($comments AS $comment)
		{
			$team = (isset($teams[$comment['team_id']]) ? $teams[$comment['team_id']] : null);
			if (!$team)
			{
				continue;
			}

			$this->insertIntoIndex($indexer, $comment, $team);
		}

		return true;
	}

	public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
	{
		$commentIds = $this->_getCommentModel()->getCommentIdsInRange($lastId, $batchSize);
		if (!$commentIds)
		{
			return false;
		}

		$this->quickIndex($indexer, $commentIds);

		return max($commentIds);
	}

	public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
	{
		$commentModel = $this->_getCommentModel();
		$comments = $commentModel->getCommentsByIds($ids);

		if(empty($comments)) 
		{
			return array();
		}

		$postIds = array();
		$eventIds = array();

		foreach($comments as $comment)
		{
			if($comment['content_type'] == 'post')
			{
				$postIds[$comment['comment_id']] = $comment['content_id'];
			}
			else if($comment['content_type'] == 'event')
			{
				$eventIds[$comment['comment_id']] = $comment['content_id'];
			}
		}

		$commentsForPost = $commentModel->getCommentsByIds(array_keys($postIds), array(
			'join' => Nobita_Teams_Model_Comment::FETCH_USER
					| Nobita_Teams_Model_Comment::FETCH_TEAM
					| Nobita_Teams_Model_Comment::FETCH_CONTENT_POST
		));

		$commentsForEvent = $commentModel->getCommentsByIds(array_keys($eventIds), array(
			'join' => Nobita_Teams_Model_Comment::FETCH_USER
					| Nobita_Teams_Model_Comment::FETCH_TEAM
					| Nobita_Teams_Model_Comment::FETCH_CONTENT_POST
		));

		$output = array();
		foreach($ids as $id) {
			if(isset($commentsForPost[$id])) 
			{
				$output[$id] = $commentsForPost[$id];
			}
			else if(isset($commentsForEvent[$id]))
			{
				$output[$id] = $commentsForEvent[$id];
			}
		}

		return $output;
	}

	public function canViewResult(array $result, array $viewingUser)
	{
		if(!isset($result['teamData']))
		{
			$result['teamData'] = $this->_getTeamModel()->getTeamDataFromArray($result);
		}

		return $this->_getCommentModel()->canViewComment(
			$result, $result['teamData'], $result['teamData'], $null, $viewingUser
		);
	}

	public function prepareResult(array $result, array $viewingUser)
	{
		$result = $this->_getCommentModel()->prepareComment($result, $result, $result, $viewingUser);
		$result['title'] = XenForo_Helper_String::censorString($result['teamData']['title']);

		return $result;
	}

	public function getResultDate(array $result)
	{
		return $result['comment_date'];
	}

	public function renderResult(XenForo_View $view, array $result, array $search)
	{
		return $view->createTemplateObject('Team_search_result_comment', array(
			'comment' => $result,
			'team' => $result,
			'search' => $search,
			'enableInlineMod' => $this->_inlineModEnabled,
			'searchContentTypePhrase' => $this->getSearchContentTypePhrase()
		));
	}

	public function getSearchContentTypes()
	{
		return array('team_comment', 'team');
	}

	public function getSearchContentTypePhrase()
	{
		return new XenForo_Phrase('Teams_handler_phrase_key_comments');
	}

	protected function _getTeamModel()
	{
		if(!$this->_teamModel)
		{
			$this->_teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		}

		return $this->_teamModel;
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