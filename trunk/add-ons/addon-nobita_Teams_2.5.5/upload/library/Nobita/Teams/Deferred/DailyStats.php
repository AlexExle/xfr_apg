<?php

class Nobita_Teams_Deferred_DailyStats extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $data = array_merge(array(
            'position' => 0,
            'batch' => 100,
            'delete' => false
        ), $data);
        $data['batch'] = max(1, $data['batch']);

        /* @var $statsModel XenForo_Model_Stats */
        $statsModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Stats');

        // delete old stats cache if required
        if (!empty($data['delete']) && $data['position'] === 0)
        {
            $statsModel->deleteStats();
        }

        /* @var $teamModel Nobita_Teams_Model_Team */
        $teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
        
        $teamIds = $teamModel->getTeamIdsInRange($data['position'], $data['batch']);
        if (sizeof($teamIds) == 0)
        {
            return true;
        }

        $start = XenForo_Application::$time - $data['batch'] * 86400;
        $end = XenForo_Application::$time;

        foreach($teamIds as $teamId)
        {
            $data['position'] = $teamId;

            $statsModel->buildStatsData($teamId, $start, $end);
        }

        $rbPhrase = new XenForo_Phrase('rebuilding');
        $typePhrase = new XenForo_Phrase('Teams_handler_phrase_key_teams');
        $status = sprintf('%s... %s (%s)', $rbPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

        return $data;
    }

    public function canCancel()
    {
        return true;
    }
}