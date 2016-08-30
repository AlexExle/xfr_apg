<?php

class Nobita_Teams_NodeHandler_Forum extends XenForo_NodeHandler_Forum
{
    /**
	 * Determines if the specified node is viewable with the given permissions.
	 *
	 * @param array $node Node info
	 * @param array $permissions Permissions for this node
	 *
	 * @return boolean
	 */
	public function isNodeViewable(array $node, array $permissions)
	{
        if(empty($node['team_id']))
		{
            return false;
        }

        return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canViewTeamAndContainer($node, $node);
    }
}
