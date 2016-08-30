<?php

class Nobita_Teams_ReportHandler_Team extends XenForo_ReportHandler_Abstract
{
	public function getReportDetailsFromContent(array $content)
	{
		/* @var $teamModel Nobita_Teams_Model_Team */
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		$team = $teamModel->getFullTeamById($content['team']['team_id'], array(
			'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
		));
		
		if (!$team)
		{
			return array(false, false, false);
		}
		
		return array(
			$team['team_id'],
			$team['user_id'],
			array(
				'team_id' => $content['team']['team_id'],
				'title' => $content['team']['title'],
				'message' => $team['tag_line']
			)
		);
	}
	
	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		$teamReportIds = array();

		/* @var $teamModel Nobita_Teams_Model_Team */
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		foreach ($reports AS $reportId => $report)
		{
			$teamReportIds[$report['content_id']] = $reportId;
		}
		
		$teams = $teamModel->getTeamsByIds(array_keys($teamReportIds), array(
			'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
				| Nobita_Teams_Model_Team::FETCH_PRIVACY
				| Nobita_Teams_Model_Team::FETCH_PROFILE
		));

		foreach ($teams as $teamId => $team)
		{
			$invalidReport = false;

			if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteAny')
				|| !XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editAny')
			)
			{
				$invalidReport = true;
			}

			if ($invalidReport)
			{
				if (isset($teamReportIds[$teamId]))
				{
					unset($teamReportIds[$teamId]);
				}
			}
		}

		return $reports;
	}
	
	public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
	{
		if (!isset($contentInfo['message']))
		{
			$team = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getTeamById($contentInfo['team_id']);
			if ($team)
			{
				$contentInfo['message'] = $team['tag_line'];
			}
		}

		$parser = XenForo_BbCode_Parser::create(
			XenForo_BbCode_Formatter_Base::create('Base', array('view' => $view))
		);

		return $view->createTemplateObject('Team_report_team_content', array(
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
		return new XenForo_Phrase('Teams_team_x', array('team_title' => $contentInfo['title']));
	}
	
	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX, $contentInfo);
	}
	
}