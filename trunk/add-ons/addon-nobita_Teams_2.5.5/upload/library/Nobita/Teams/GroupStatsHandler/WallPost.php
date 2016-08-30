<?php

class Nobita_Teams_GroupStatsHandler_WallPost extends Nobita_Teams_GroupStatsHandler_Abstract
{
    public function getStatsTypes()
    {
        return array(
            'member_wall' => new XenForo_Phrase('Teams_member_wall_posts'),
            'staff_wall' => new XenForo_Phrase('Teams_staff_wall_posts')
        );
    }

    public function getData($teamId, $startDate, $endDate)
    {
        $db = $this->_getDb();

        $memberWallPosts = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_team_post', 'post_date', 'message_state = ? AND share_privacy = ? AND team_id = ?'),
            array($startDate, $endDate, 'visible', 'member', $teamId)
        );

        $adminWallPosts = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_team_post', 'post_date', 'message_state = ? AND share_privacy = ? AND team_id = ?'),
            array($startDate, $endDate, 'visible', 'staff', $teamId)
        );

        return array(
            'member_wall' => $memberWallPosts,
            'staff_wall' => $adminWallPosts,
        );
    }
}