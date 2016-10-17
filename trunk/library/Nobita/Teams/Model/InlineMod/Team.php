<?php

class Nobita_Teams_Model_InlineMod_Team extends XenForo_Model
{
	public $enableLogging = true;
	public function canDeleteTeams(array $teamIds, $deleteType = 'soft', &$errorKey = '', array $viewingUser = null)
	{
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds, $viewingUser);
		return $this->canDeleteTeamsData($teams, $deleteType, $categories, $errorKey, $viewingUser);
	}
	
	public function canDeleteTeamsData(array $teams, $deleteType, array $categories, &$errorKey = '', array $viewingUser = null)
	{
		if (!$teams)
		{
			return true;
		}
		
		$this->standardizeViewingUserReference($viewingUser);
		
		$teamModel = $this->_getTeamModel();
		foreach ($teams as $team)
		{
			$category = $this->_getCategoryFromTeam($team, $categories);

			if (!$teamModel->canViewTeamAndContainer($team, $category, $errorKey, $viewingUser))
			{
				return false;
			}

			if (!$teamModel->canDeleteTeam($team, $category, $deleteType, $errorKey, $viewingUser))
			{
				return false;
			}
		}
		
		return true;
	}
	
	public function deleteTeams(array $teamIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		$options = array_merge(
			array(
				'deleteType' => '',
				'reason' => ''
			), $options
		);

		if (!$options['deleteType'])
		{
			throw new XenForo_Exception('No deletion type specified.');
		}
		
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds, $viewingUser);
		if (empty($options['skipPermissions']) && !$this->canDeleteTeamsData($teams, $options['deleteType'], $categories, $errorKey, $viewingUser))
		{
			return false;
		}
		
		foreach ($teams as $team)
		{
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
			if (!$dw->setExistingData($team))
			{
				continue;
			}
			
			if ($options['deleteType'] == 'hard')
			{
				$dw->delete();

				if ($this->enableLogging)
				{
					XenForo_Model_Log::logModeratorAction('team', $team, 'delete_hard');
				}
			}
			else
			{
				$dw->setExtraData(Nobita_Teams_DataWriter_Team::DATA_DELETE_REASON, $options['reason']);
				$dw->set('team_state', 'deleted');
				$dw->save();

				if ($this->enableLogging)
				{
					XenForo_Model_Log::logModeratorAction('team', $team, 'delete_soft', array('reason' => $options['reason']));
				}
			}
		}
		
		return true;
	}
	
	public function canUndeleteTeams(array $teamIds, &$errorKey = '', array $viewingUser = null)
	{
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds, $viewingUser);
		return $this->canUndeleteTeamsData($teams, $categories, $errorKey, $viewingUser);
	}
	
	public function canUndeleteTeamsData(array $teams, array $categories, &$errorKey = '', array $viewingUser = null)
	{
		return $this->_checkPermissionOnTeams('canUndeleteTeam', $teams, $categories, $errorKey, $viewingUser);
	}
	
	public function undeleteTeams(array $teamIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds, $viewingUser);
		
		if (empty($options['skipPermissions']) && !$this->canUndeleteTeamsData($teams, $categories, $errorKey, $viewingUser))
		{
			return false;
		}
		
		$this->_updateTeamState($teams, $categories, 'visible', 'deleted');

		return true;
	}

	public function canApproveUnapproveTeams(array $teamIds, &$errorKey = '', array $viewingUser = null)
	{
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds, $viewingUser);
		return $this->canApproveUnapproveTeamsData($teams, $categories, $errorKey, $viewingUser);
	}

	public function canApproveUnapproveTeamsData(array $teams, array $categories, &$errorKey = '', array $viewingUser = null)
	{
		return $this->_checkPermissionOnTeams('canApproveUnapproveTeam', $teams, $categories, $errorKey, $viewingUser);
	}

	public function approveTeams(array $teamIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds, $viewingUser);
		
		if (empty($options['skipPermissions']) && !$this->canApproveUnapproveTeamsData($teams, $categories, $errorKey, $viewingUser))
		{
			return false;
		}
		
		$this->_updateTeamState($teams, $categories, 'visible', 'moderated');
		return true;
	}

	public function unapproveTeams(array $teamIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds, $viewingUser);
		
		if (empty($options['skipPermissions']) && !$this->canApproveUnapproveTeamsData($teams, $categories, $errorKey, $viewingUser))
		{
			return false;
		}
		
		$this->_updateTeamState($teams, $categories, 'moderated', 'visible');
		return true;
	}

	/**
	 * Determines if the selected team IDs can be featured.
	 *
	 * @param array $teamIds List of IDs to check
	 * @param string $errorKey Modified by reference. If no permission, may include a key of a phrase that gives more info
	 * @param array|null $viewingUser Viewing user reference
	 *
	 * @return boolean
	 */
	public function canFeatureTeams(array $teamIds, &$errorKey = '', array $viewingUser = null)
	{
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds);
		return $this->canFeatureOrUnfeatureTeamsData($teams, $categories, $errorKey, $viewingUser);
	}
	
	/**
	 * Determines if the selected team data can be featured.
	 *
	 * @param array $teams List of data to be checked
	 * @param array $categories List of categories the teams are in
	 * @param string $errorKey Modified by reference. If no permission, may include a key of a phrase that gives more info
	 * @param array|null $viewingUser Viewing user reference
	 *
	 * @return boolean
	 */
	public function canFeatureOrUnfeatureTeamsData(array $teams, array $categories, &$errorKey = '', array $viewingUser = null)
	{
		if (!$teams)
		{
			return true;
		}
		
		$this->standardizeViewingUserReference($viewingUser);
		$teamModel = $this->_getTeamModel();
		
		foreach ($teams as $team)
		{
			$category = $this->_getCategoryFromTeam($team, $categories);
			if (!$teamModel->canFeatureUnfeatureTeam($team, $category, $null, $viewingUser))
			{
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Features the specified teams if permissions are sufficient.
	 *
	 * @param array $teamIds List of IDs to approve
	 * @param array $options Options that control the action. Nothing supported at this time.
	 * @param string $errorKey Modified by reference. If no permission, may include a key of a phrase that gives more info
	 * @param array|null $viewingUser Viewing user reference
	 *
	 * @return boolean True if permissions were ok
	 */
	public function featureTeams(array $teamIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds);
		
		if (empty($options['skipPermissions']) && !$this->canFeatureOrUnfeatureTeamsData($teams, $categories, $errorKey, $viewingUser))
		{
			return false;
		}
		
		foreach ($teams as $team)
		{
			$this->_getTeamModel()->featureTeam($team);
		}
		
		return true;
	}

	/**
	 * Determines if the selected team IDs can be unfeatured.
	 *
	 * @param array $teamIds List of IDs to check
	 * @param string $errorKey Modified by reference. If no permission, may include a key of a phrase that gives more info
	 * @param array|null $viewingUser Viewing user reference
	 *
	 * @return boolean
	 */
	public function canUnfeatureTeams(array $teamIds, &$errorKey = '', array $viewingUser = null)
	{
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds);
		return $this->canFeatureOrUnfeatureTeamsData($teams, $categories, $errorKey, $viewingUser);
	}

	/**
	 * Unfeatures the specified teams if permissions are sufficient.
	 *
	 * @param array $teamIds List of IDs to approve
	 * @param array $options Options that control the action. Nothing supported at this time.
	 * @param string $errorKey Modified by reference. If no permission, may include a key of a phrase that gives more info
	 * @param array|null $viewingUser Viewing user reference
	 *
	 * @return boolean True if permissions were ok
	 */
	public function unfeatureTeams(array $teamIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		list($teams, $categories) = $this->getTeamsAndParentData($teamIds);
		if (empty($options['skipPermissions']) && !$this->canFeatureOrUnfeatureTeamsData($teams, $categories, $errorKey, $viewingUser))
		{
			return false;
		}
		
		foreach ($teams as $team)
		{
			$this->_getTeamModel()->unfeatureTeam($team);
		}

		return true;
	}

	/**
	 * Internal helper to update the team_state of a collection of teams.
	 *
	 * @param array $teams Information about the teams to update
	 * @param array $categories Information about the categories that the teams are in
	 * @param string $newState New message state (visible, moderated, deleted)
	 * @param string|false $expectedOldState If specified, only updates if the old state matches
	 */
	protected function _updateTeamState(array $teams, array $categories, $newState, $expectedOldState = false)
	{
		switch ($newState)
		{
			case 'visible':
				switch (strval($expectedOldState))
				{
					case 'visible': return;
					case 'moderated': $logAction = 'approve'; break;
					case 'deleted': $logAction = 'undelete'; break;
					default: $logAction = 'undelete'; break;
				}
				break;

			case 'moderated':
				switch (strval($expectedOldState))
				{
					case 'visible': $logAction = 'unapprove'; break;
					case 'moderated': return;
					case 'deleted': $logAction = 'unapprove'; break;
					default: $logAction = 'unapprove'; break;
				}
				break;

			case 'deleted':
				switch (strval($expectedOldState))
				{
					case 'visible': $logAction = 'delete_soft'; break;
					case 'moderated': $logAction = 'delete_soft'; break;
					case 'deleted': return;
					default: $logAction = 'delete_soft'; break;
				}
				break;

			default: return;
		}
		
		foreach ($teams as $team)
		{
			if ($expectedOldState && $team['team_state'] != $expectedOldState)
			{
				continue;
			}
			
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
			if (!$dw->setExistingData($team))
			{
				continue;
			}
			
			$dw->set('team_state', $newState);
			$dw->save();
			
			if ($this->enableLogging)
			{
				XenForo_Model_Log::logModeratorAction('team', $team, $logAction);
			}
		}
	}

	protected function _checkPermissionOnTeams($permissionMethod, array $teams, array $categories, &$errorKey = '', array $viewingUser = null)
	{
		if (!$teams)
		{
			return true;
		}
		
		$this->standardizeViewingUserReference($viewingUser);
		$teamModel = $this->_getTeamModel();
		
		foreach ($teams as $team)
		{
			$category = $this->_getCategoryFromTeam($team, $categories);
			
			if (!$teamModel->canViewTeamAndContainer($team, $category, $null, $viewingUser))
			{
				return false;
			}
			
			if ($permissionMethod && !$teamModel->$permissionMethod($team, $category, $null, $viewingUser))
			{
				return false;
			}
		}
		
		return true;
	}
	
	protected function _getCategoryFromTeam(array $team, array $categories)
	{
		return $categories[$team['team_category_id']];
	}

	/**
	 * From a list of teams IDs, gets info about the teams and
	 * the categories the teams are in.
	 *
	 * @param array $teamIds List of team IDs
	 * @param array|null $viewingUser
	 *
	 * @return array Format:  [0] => list of teams, [1] => list of categories
	 */
	public function getTeamsAndParentData(array $teamIds, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		return $this->_getTeamModel()->getTeamsAndParentData($teamIds);
	}
	
	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}
}