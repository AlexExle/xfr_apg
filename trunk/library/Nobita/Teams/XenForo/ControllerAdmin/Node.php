<?php

class Nobita_Teams_XenForo_ControllerAdmin_Node extends XFCP_Nobita_Teams_XenForo_ControllerAdmin_Node
{
	public function actionGroups()
	{
		$nodeModel = $this->_getNodeModel();

		$nodes = $nodeModel->prepareNodesForAdmin($nodeModel->getAllTeamNodes());

		$moderatorsGrouped = array();
		$moderators = $this->_getModeratorModel()->getContentModerators(
			array('content' => 'node')
		);
		foreach ($moderators AS $moderator)
		{
			$moderatorsGrouped[$moderator['content_id']][] = $moderator;
		}
		foreach ($nodes AS &$node)
		{
			if (isset($moderatorsGrouped[$node['node_id']]))
			{
				$node['moderators'] = $moderatorsGrouped[$node['node_id']];
			}
			else
			{
				$node['moderators'] = array();
			}

			$node['moderatorCount'] = count($node['moderators']);
		}

		$permissionSets = $this->_getPermissionModel()->getUserCombinationsWithContentPermissions('node');
		$nodesWithPerms = array();
		foreach ($permissionSets AS $set)
		{
			$nodesWithPerms[$set['content_id']] = true;
		}

		$viewParams = array(
			'nodes' => $nodes,
			'nodesWithPerms' => $nodesWithPerms
		);

		return $this->responseView('XenForo_ViewAdmin_Node_List', 'node_list', $viewParams);
	}
}