<?php

class Nobita_Teams_XenForo_Model_Node extends XFCP_Nobita_Teams_XenForo_Model_Node
{
    public function getAllTeamNodes($ignoreNestedSetOrdering = false, $listView = false)
    {
        if ($ignoreNestedSetOrdering)
        {
            return $this->fetchAllKeyed('
                SELECT *
                FROM xf_node
                WHERE node_type_id = ?
                ' . ($listView ? 'AND display_in_list = 1' : '') . '
                ORDER BY parent_node_id, display_order ASC
            ', 'node_id', array(Nobita_Teams_Listener::NODE_TYPE_ID));
        }
        else
        {
            return $this->fetchAllKeyed('
                SELECT *
                FROM xf_node
                WHERE node_type_id = ?
                ' . ($listView ? 'AND display_in_list = 1' : '') . '
                ORDER BY lft ASC
            ', 'node_id', array(Nobita_Teams_Listener::NODE_TYPE_ID));
        }
    }

    public function getAllNodesInTeam($teamId, $listView = false)
    {
        $nodes = $this->fetchAllKeyed('
            SELECT node.*, node.title as node_title, team.title as team_title, team.team_id,
                team.privacy_state, team.team_state, team.user_id
            FROM xf_node as node
                INNER JOIN xf_forum AS forum ON (forum.node_id = node.node_id)
                INNER JOIN xf_team AS team ON (team.team_id = forum.team_id)
            WHERE forum.team_id = ?
                ' . ($listView ? 'AND node.display_in_list = 1' : '') . '
            ORDER BY node.lft ASC
        ', 'node_id', array($teamId));

        $nodes = array_map(function($node) {
            $node['title'] = $node['node_title'];
            return $node;
        }, $nodes);

        return $nodes;
    }

    /**
     * @overwrite XenForo method
     */
    public function getChildNodes($node, $listView = false)
    {
        if (!$this->_isNode($node))
        {
            return false;
        }

        if (!$this->hasChildNodes($node))
        {
            return array();
        }

        return $this->fetchAllKeyed('
            SELECT node.*, node.title as node_title, team.title as team_title, team.team_id,
                team.privacy_state, team.team_state, team.user_id
            FROM xf_node as node
                LEFT JOIN xf_forum AS forum ON (forum.node_id = node.node_id)
                LEFT JOIN xf_team AS team ON (team.team_id = forum.team_id)
            WHERE node.lft > ? AND node.rgt < ?
                ' . ($listView ? ' AND node.display_in_list = 1' : '') . '
            ORDER BY node.lft ASC
        ', 'node_id', array($node['lft'], $node['rgt']));
    }
}
