<?php

class Nobita_Teams_ReportHandler_Event extends XenForo_ReportHandler_Abstract
{
	public function getReportDetailsFromContent(array $content)
	{
		$eventModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
		$event = $eventModel->getEventById($content['event_id'], array(
			'join' => Nobita_Teams_Model_Post::FETCH_TEAM
		));
		
		if (!$event)
		{
			return array(false, false, false);
		}

		if (empty($content['team']))
		{
			$content['team'] = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getFullTeamById($event['team_id']);
			if (empty($content['team']))
			{
				return array(false, false, false);
			}
		}

		return array(
			$event['event_id'],
			$event['user_id'],
			array(
				'message' => $event['event_description'],
				'team' => $content['team'],
				'event' => $event
			)
		);
	}

	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		$eventReportIds = array();
		foreach($reports as $reportId => $report)
		{
			$eventReportIds[$report['content_id']] = $reportId;
		}

		$eventModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		$events = $eventModel->getEventsByIds(array_keys($eventReportIds), array('join' => Nobita_Teams_Model_Post::FETCH_TEAM));
		if (!$events)
		{
			return array();
		}

		$teamIds = array();
		foreach($events as $eventId => $event)
		{
			$teamIds[] = $event['team_id'];
		}

		$teams = $teamModel->getTeamsByIds($teamIds, array(
			'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
				| Nobita_Teams_Model_Team::FETCH_PRIVACY
				| Nobita_Teams_Model_Team::FETCH_PROFILE
		));

		if (!$teams)
		{
			return array();
		}

		foreach($events as $eventId => $eventReport)
		{
			$invalidReport = false;

			if (!isset($teams[$eventReport['team_id']]))
			{
				$invalidReport = true;
			}
			else
			{
				$team = $teams[$eventReport['team_id']];
				if (!$teamModel->canViewTeamAndContainer($team, $team, $null, $viewingUser))
				{
					$invalidReport = true;
				}
				else
				{
					if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editEventAny')
						&& !XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteEventAny')
					)
					{
						$invalidReport = true;
					}
				}
			}

			if ($invalidReport)
			{
				if (isset($eventReportIds[$eventId]))
				{
					unset($reports[$eventReportIds[$eventId]]);
				}
			}
		}

		return $reports;
	}
	
	public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
	{
		$parser = XenForo_BbCode_Parser::create(
			XenForo_BbCode_Formatter_Base::create('Base', array('view' => $view))
		);

		return $view->createTemplateObject('Team_report_event_content', array(
			'report' => $report,
			'content' => $contentInfo,
			'bbCodeParser' => $parser
		));
	}

	/**
	 * Gets the title of the specified content.
	 *
	 * @see XenForo_ReportHandler_Abstract:getContentTitle()
	 */
	public function getContentTitle(array $report, array $contentInfo)
	{
		return new XenForo_Phrase('Teams_event_x_in_team_y', array(
			'event_title' => $contentInfo['event']['event_title'],
			'group_title' => $contentInfo['team']['title']
		));
	}

	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/events', array('event_id' => $report['content_id']));
	}


}