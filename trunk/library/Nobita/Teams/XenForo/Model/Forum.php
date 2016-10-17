<?php

class Nobita_Teams_XenForo_Model_Forum extends XFCP_Nobita_Teams_XenForo_Model_Forum
{
	/**
	 * {@overwrite}
	 */
	public function getForumByNodeName($name, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareForumJoinOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT node.*, forum.*
				' . $joinOptions['selectFields'] . '
			FROM xf_forum AS forum
			INNER JOIN xf_node AS node ON (node.node_id = forum.node_id)
			' . $joinOptions['joinTables'] . '
			WHERE node.node_name = ?
				AND (node.node_type_id = \'Forum\' OR node.node_type_id = ?)
		', array($name, Nobita_Teams_Listener::NODE_TYPE_ID));
	}

	public function canPostThreadInForum(array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$team = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getTeamDataFromArray($forum);
		if(isset($team['privacy_state']) && $team['privacy_state'])
		{
			return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canViewTeamAndContainer($team, $team, $errorPhraseKey, $viewingUser);
		}

		return parent::canPostThreadInForum($forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	public function prepareForumJoinOptions(array $fetchOptions)
	{
		$response = parent::prepareForumJoinOptions($fetchOptions);
		extract($response);

		if(isset($fetchOptions[Nobita_Teams_Listener::FORUM_FETCHOPTIONS_JOIN_TEAM]))
		{
			$selectFields .= ',team.team_id as team_team_id,team.user_id as team_user_id,team.privacy_state as team_privacy_state,
				team.team_state as team_team_state,team_category.allow_uploaded_file as team_allow_uploaded_file';
			$joinTables .= '
				LEFT JOIN xf_team AS team ON (team.team_id = forum.team_id)
				LEFT JOIN xf_team_category AS team_category ON
					(team_category.team_category_id = team.team_category_id)';
		}

		return compact('selectFields', 'joinTables');
	}

	public function canViewForum(array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($forum['team_id']))
		{
			return parent::canViewForum($forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		$team = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getTeamDataFromArray($forum);
		if(isset($team['privacy_state']) && $team['privacy_state'])
		{
			return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canViewTeamAndContainer($team, $team, $errorPhraseKey, $viewingUser);
		}

		return parent::canViewForum($forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}
}
