<?php

class Nobita_Teams_CronEntry_DailyStats
{
    public static function runDaily()
    {
        // get the the timestamp of 00:00 UTC for today
        $time = XenForo_Application::$time - XenForo_Application::$time % 86400;

        /* @var $statsModel XenForo_Model_Stats */
        $statsModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Stats');
        
        $db = XenForo_Application::getDb();

        $teamIds = $db->fetchCol('SELECT team_id FROM xf_team');

        foreach($teamIds as $teamId)
        {
            $statsModel->buildStatsData($teamId, $time - 86400, $time);
        }
    }
}


