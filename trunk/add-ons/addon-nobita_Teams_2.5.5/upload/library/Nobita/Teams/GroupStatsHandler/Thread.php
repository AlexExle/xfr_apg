<?php

class Nobita_Teams_GroupStatsHandler_Thread extends Nobita_Teams_GroupStatsHandler_Abstract
{
    public function getData($teamId, $startDate, $endDate)
    {
        $db = $this->_getDb();

        $threads = $this->_getDb()->fetchPairs(
            $this->_getBasicDataQuery('xf_thread', 'post_date', 'discussion_state = ? AND team_id = ?'),
            array($startDate, $endDate, 'visible', $teamId)
        );

        return array(
            'thread' => $threads,
        );
    }

    public function getStatsTypes()
    {
        return array(
            'thread' => new XenForo_Phrase('threads')
        );
    }
}