<?php

class Nobita_Teams_GroupStatsHandler_Member extends Nobita_Teams_GroupStatsHandler_Abstract
{
    public function getStatsTypes()
    {
        return array(
            'member' => new XenForo_Phrase('Teams_members')
        );
    }

    public function getData($teamId, $startDate, $endDate)
    {
        $db = $this->_getDb();

        $members = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_team_member', 'join_date', 'member_state = ? AND team_id = ?'),
            array($startDate, $endDate, 'accept', $teamId)
        );

        return array(
            'member' => $members
        );
    }
}