<?php

class Nobita_Teams_Search_DataHandler_Team extends XenForo_Search_DataHandler_Abstract
{
	/**
	 *	@var Nobita_Teams_Model_Team
	 */
	protected $_teamModel;

	/**
	 * Inserts into (or replaces a record) in the index.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::_insertIntoIndex()
	 */
	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
	{
		$teamModel = $this->_getTeamModel();

		if (!$teamModel->isVisible($data))
		{
			return;
		}

		$metadata = array();
		$metadata['category'] = $data['team_category_id'];
		$metadata['team'] = $data['team_id'];

		$indexer->insertIntoIndex(
			'team', $data['team_id'],
			$data['title'], $data['tag_line']. ' ' . $data['about'],
			$data['team_date'], $data['user_id'], $data['team_id'], $metadata
		);
	}

	/**
	 * Updates a record in the index.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::_updateIndex()
	 */
	protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
	{
		$indexer->updateIndex('team', $data['team_id'], $fieldUpdates);
	}

	/**
	 * Deletes one or more records from the index.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::_deleteFromIndex()
	 */
	protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
	{
		$teamIds = array();
		foreach ($dataList as $data)
		{
			$teamIds[] = is_array($data) ? $data['team_id'] : $data;
		}

		$indexer->deleteFromIndex('team', $teamIds);
	}

	/**
	 * Rebuilds the index for a batch.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::rebuildIndex()
	 */
	public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
	{
		$teamIds = $this->_getTeamModel()->getTeamIdsInRange($lastId, $batchSize);
		if (!$teamIds)
		{
			return false;
		}

		$this->quickIndex($indexer, $teamIds);
		return max($teamIds);
	}

	/**
	 * Rebuilds the index for the specified content.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::quickIndex()
	 */
	public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
	{
		$teamModel = $this->_getTeamModel();

		$teams = $teamModel->getTeamsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Team::FETCH_PROFILE
				| Nobita_Teams_Model_Team::FETCH_PRIVACY
				| Nobita_Teams_Model_Team::FETCH_CATEGORY
		));
		foreach ($teams as $team)
		{
			$this->insertIntoIndex($indexer, $team);
		}

		return true;
	}

	/**
	 * Gets the type-specific data for a collection of results of this content type.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::getDataForResults()
	 */
	public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
	{
		$teamModel = $this->_getTeamModel();

		return $teamModel->getTeamsByIds($ids, array(
			'join' => Nobita_Teams_Model_Team::FETCH_PROFILE
				| Nobita_Teams_Model_Team::FETCH_PRIVACY
				| Nobita_Teams_Model_Team::FETCH_CATEGORY
				| Nobita_Teams_Model_Team::FETCH_USER
		));
	}

	/**
	 * Determines if this result is viewable.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::canViewResult()
	 */
	public function canViewResult(array $result, array $viewingUser)
	{
		return $this->_getTeamModel()->canViewTeam(
			$result, $result, $null, $viewingUser
		);
	}

	/**
	 * Prepares a result for display.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::prepareResult()
	 */
	public function prepareResult(array $result, array $viewingUser)
	{
		return $this->_getTeamModel()->prepareTeam($result, $result, $viewingUser);
	}

	/**
	 * Gets the date of the result (from the result's content).
	 *
	 * @see XenForo_Search_DataHandler_Abstract::getResultDate()
	 */
	public function getResultDate(array $result)
	{
		return $result['team_date'];
	}

	/**
	 * Renders a result to HTML.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::renderResult()
	 */
	public function renderResult(XenForo_View $view, array $result, array $search)
	{
		return $view->createTemplateObject('Team_search_result_team', array(
			'team' => $result,
			'category' => array(
				'team_category_id' => $result['team_category_id'],
				'category_title' => $result['category_title']
			)
		));
	}

	public function getSearchContentTypes()
	{
		return array('team', 'team_post', 'team_comment', 'team_event');
	}

	public function getSearchContentTypePhrase()
	{
		return new XenForo_Phrase('Teams_handler_phrase_key_teams');
	}

	/**
	* Get type-specific constraints from input.
	*
	* @param XenForo_Input $input
	*
	* @return array
	*/
	public function getTypeConstraintsFromInput(XenForo_Input $input)
	{
		$constraints = array();

		$categories = $input->filterSingle('categories', XenForo_Input::UINT, array('array' => true));
		if ($categories && !in_array(0, $categories))
		{
			if ($input->inRequest('child_categories'))
			{
				$includeChildren = $input->filterSingle('child_categories', XenForo_Input::UINT);
			}
			else
			{
				$includeChildren = true;
			}

			if ($includeChildren)
			{
				$descendants = array_keys(Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category')->getDescendantsOfCategoryIds($categories));
				$categories = array_merge($categories, $descendants);
			}

			$categories = array_unique($categories);
			$constraints['category'] = implode(' ', $categories);
			if (!$constraints['category'])
			{
				unset($constraints['category']); // just 0
			}
		}

		$teamId = $input->filterSingle('team_id', XenForo_Input::UINT);
		if($teamId)
		{
			$constraints['team'] = $teamId;
		}

		return $constraints;
	}

	/**
	 * Process a type-specific constraint.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::processConstraint()
	 */
	public function processConstraint(XenForo_Search_SourceHandler_Abstract $sourceHandler, $constraint, $constraintInfo, array $constraints)
	{
		switch ($constraint)
		{
			case 'category':
				if ($constraintInfo)
				{
					return array(
						'metadata' => array('category', preg_split('/\D+/', strval($constraintInfo))),
					);
				}
				break;
			case 'team':
				if ($constraintInfo)
				{
					return array(
						'metadata' => array('team', preg_split('/\D+/', strval($constraintInfo))),
					);
				}
				break;
		}

		return false;
	}

	/**
	 * Gets the search form controller response for this type.
	 *
	 * @see XenForo_Search_DataHandler_Abstract::getSearchFormControllerResponse()
	 */
	public function getSearchFormControllerResponse(XenForo_ControllerPublic_Abstract $controller, XenForo_Input $input, array $viewParams)
	{
		$teamModel = $this->_getTeamModel();
		if (!$teamModel->canViewTeams($error))
		{
			return $controller->responseNoPermission();
		}

		$params = $input->filterSingle('c', XenForo_Input::ARRAY_SIMPLE);

		if (!empty($params['category']))
		{
			$viewParams['search']['categories'] = array_fill_keys(explode(' ', $params['category']), true);
		}
		else
		{
			$viewParams['search']['categories'] = array();
		}

		$viewParams['search']['child_categories'] = true;
		$viewParams['categories'] = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category')->getViewableCategories();

		return $controller->responseView('Nobita_Teams_ViewPublic_Search_Form_Team', 'Team_search_form_team', $viewParams);
	}

	protected function _getTeamModel()
	{
		if (!$this->_teamModel)
		{
			$this->_teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		}

		return $this->_teamModel;
	}
}
