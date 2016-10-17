<?php

class Nobita_Teams_ControllerPublic_Forum extends Nobita_Teams_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		list ($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		$teamModel = $this->_getTeamModel();

		if (!$teamModel->canViewTabAndContainer('threads', $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$nodeModel = $this->_getNodeModel();

		$nodes = $nodeModel->getAllNodesInTeam($team['team_id']);
		$nodeList = $nodeModel->getNodeListDisplayData($nodes, 0, 0);

		$viewParams = array(
			'team' => $team,
			'category' => $category,
			'nodeList' => $nodeList,
			'level' => 1,
			'canAddForum' => $teamModel->canAddForum($team, $category),
		);

		return $this->_getTeamViewWrapper('forums', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Discussion_List', 'Team_forum_list', $viewParams)
		);
	}

	public function actionAdd()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		$this->_assertCanAddForum($team, $category);

		return $this->_getForumAddOrEditResponse($team, $category);
	}

	protected function _getForumAddOrEditResponse(array $team, array $category, array $forum = array())
	{
		$nodeModel = $this->_getNodeModel();
		$nodes = $nodeModel->getAllNodesInTeam($team['team_id']);

		$viewParams = array(
			'team' => $team,
			'category' => $category,
			'forum' => $forum,
			'nodes' => $nodes,
		);

		return $this->_getTeamViewWrapper('forums', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Forum_Add', 'Team_forum_add', $viewParams)
		);
	}

	public function actionEdit()
	{
		$forumHeper = $this->getHelper('ForumThreadPost');
		$nodeId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);

		$forum = $forumHeper->assertForumValidAndViewable($nodeId);
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable($forum['team_id']);

		$this->_assertCanEditForum($forum, $team, $category);
		return $this->_getForumAddOrEditResponse($team, $category, $forum);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$forumHeper = $this->getHelper('ForumThreadPost');
		$nodeId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);

		if(!empty($nodeId))
		{
			$forum = $forumHeper->assertForumValidAndViewable($nodeId);
			list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable($forum['team_id']);

			$this->_assertCanEditForum($forum, $team, $category);
		}
		else
		{
			list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
			$this->_assertCanAddForum($team, $category);
		}

		$inputData = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'description' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT,
			'allowed_watch_notifications' => XenForo_Input::STRING,
			'default_sort_order' => XenForo_Input::STRING,
			'default_sort_direction' => XenForo_Input::STRING,
			'list_date_limit_days' => XenForo_Input::UINT,
			'parent_node_id' => XenForo_Input::UINT,
		));

		$inputData += array(
			'node_type_id' => 'nobita_Teams_Forum',
			'team_id' => $team['team_id'],
		);

		$nodeModel = $this->_getNodeModel();
		$nodes = $nodeModel->getAllNodesInTeam($team['team_id']);

		if($inputData['parent_node_id'])
		{
			if(!isset($nodes[$inputData['parent_node_id']]) || $inputData['parent_node_id'] == $nodeId)
			{
				return $this->responseError(new XenForo_Phrase('Teams_please_select_valid_parent_node'));
			}
		}

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_Forum');
		if($nodeId)
		{
			$dw->setExistingData($nodeId);
		}

		$dw->bulkSet($inputData);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildPublicLink('forums', $dw->getMergedData())
		);
	}

	public function actionDelete()
	{
		$forumHeper = $this->getHelper('ForumThreadPost');
		$nodeId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);

		$forum = $forumHeper->assertForumValidAndViewable($nodeId);
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable($forum['team_id']);

		$this->_assertCanDeleteForum($forum, $team, $category);

		$nodeModel = $this->_getNodeModel();
		$childNodes = $nodeModel->getChildNodes($forum);
		$nodes = $nodeModel->getAllNodesInTeam($team['team_id']);

		if($this->isConfirmedPost())
		{
			$writer = XenForo_DataWriter::create('XenForo_DataWriter_Forum');
			$writer->setExistingData($forum);

			if ($this->_input->filterSingle('move_child_nodes', XenForo_Input::BINARY))
			{
				$parentNodeId = $this->_input->filterSingle('parent_node_id', XenForo_Input::UINT);

				if ($parentNodeId)
				{
					$parentNode = $this->_getNodeModel()->getNodeById($parentNodeId);

					if (!$parentNode)
					{
						return $this->responseError(new XenForo_Phrase('specified_destination_node_does_not_exist'));
					}

					if(!isset($nodes[$parentNode['node_id']]))
					{
						return $this->responseError(new XenForo_Phrase('Teams_please_select_valid_parent_node'));
					}
				}
				else
				{
					// no destination node id, so set it to 0 (root node)
					$parentNodeId = 0;
				}

				$writer->setOption(XenForo_DataWriter_Node::OPTION_CHILD_NODE_DESTINATION_PARENT_ID, $parentNodeId);
			}

			$writer->delete();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('forums', $team)
			);
		}

		$nodeParentOptions = $nodeModel->getNodeOptionsArray($nodeModel->getPossibleParentNodes($forum), $forum['parent_node_id'], true);
		foreach($nodeParentOptions as $nodeId => &$option)
		{
			if(empty($nodeId))
			{
				continue;
			}

			if(!isset($nodes[$option['value']]))
			{
				unset($nodeParentOptions[$nodeId]);
			}
		}

		return $this->_getTeamViewWrapper('forums', $team, $category, 
			$this->responseView('Nobita_Teams_ViewPublic_Forum_Delete', 'Team_forum_delete', array(
				'team' => $team,
				'forum' => $forum,
				'category' => $category,
				'nodes' => $nodes,
				'childNodes' => $childNodes,
				'nodeParentOptions' => $nodeParentOptions,
				'possibleMoveChildNodes' => count($nodeParentOptions) > 1 ? true :false,
			))
		);
	}
	
	protected function _assertCanDeleteForum(array $forum, array $team, array $category)
	{
		if(!$this->_getTeamModel()->canDeleteForum($forum, $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}

	protected function _assertCanEditForum(array $forum, array $team, array $category)
	{
		if(!$this->_getTeamModel()->canEditForum($forum, $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}

	protected function _assertCanAddForum(array $team, array $category)
	{
		if(!$this->_getTeamModel()->canAddForum($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}

	protected function _getNodeModel()
	{
		return $this->getModelFromCache('XenForo_Model_Node');
	}
}
