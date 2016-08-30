<?php

class Nobita_Teams_GroupStatsHandler_Event extends Nobita_Teams_GroupStatsHandler_Abstract
{
    public function getStatsTypes()
    {
        return array(
            'event_public' => new XenForo_Phrase('Teams_events_public'),
            'event_member' => new XenForo_Phrase('Teams_events_member'),
            'event_admin' => new XenForo_Phrase('Teams_events_admin'),
        );
    }

    public function getData($teamId, $startDate, $endDate)
    {
        $db = $this->_getDb();

        $eventsPublic = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_team_event', 'publish_date', 'event_type = ? AND team_id = ?'),
            array($startDate, $endDate, 'public', $teamId)
        );

        $eventsMember = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_team_event', 'publish_date', 'event_type = ? AND team_id = ?'),
            array($startDate, $endDate, 'member', $teamId)
        );

        $eventsAdmin = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_team_event', 'publish_date', 'event_type = ? AND team_id = ?'),
            array($startDate, $endDate, 'admin', $teamId)
        );

        return array(
            'event_public' => $eventsPublic,
            'event_member' => $eventsMember,
            'event_admin' => $eventsAdmin,
        );
    }
}
