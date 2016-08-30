<?php

class Nobita_Teams_ReportHandler_Post extends XenForo_ReportHandler_Abstract
{
	public function getReportDetailsFromContent(array $content)
	{
		$postModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
		$post = $postModel->getPostById($content['post_id'], array(
			'join' => Nobita_Teams_Model_Post::FETCH_TEAM
		));
		
		if (!$post)
		{
			return array(false, false, false);
		}
		
		return array(
			$post['post_id'],
			$post['user_id'],
			array(
				'message' => $post['message'],
				'team_title' => $post['title'],
				'team' => array(
					'team_id' => $post['team_id'],
					'title' => $post['title']
				)
			)
		);
	}

	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		$postReportIds = array();
		foreach($reports as $reportId => $report)
		{
			$postReportIds[$report['content_id']] = $reportId;
		}

		$postModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		$posts = $postModel->getPostsByIds(array_keys($postReportIds), array('join' => Nobita_Teams_Model_Post::FETCH_TEAM));
		if (!$posts)
		{
			return array();
		}

		$teamIds = array();
		foreach($posts as $postId => $post)
		{
			$teamIds[] = $post['team_id'];
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

		foreach($posts as $postId => $postReport)
		{
			$invalidReport = false;

			if (!isset($teams[$postReport['team_id']]))
			{
				$invalidReport = true;
			}
			else
			{
				$team = $teams[$postReport['team_id']];
				if (!$teamModel->canViewTeamAndContainer($team, $team, $null, $viewingUser))
				{
					$invalidReport = true;
				}
				else
				{
					if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editPostAny')
						&& !XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deletePostAny')
					)
					{
						$invalidReport = true;
					}
				}
			}

			if ($invalidReport)
			{
				if (isset($postReportIds[$postId]))
				{
					unset($reports[$postReportIds[$postId]]);
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

		return $view->createTemplateObject('Team_report_post_content', array(
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
		return new XenForo_Phrase('Teams_post_in_team_x', array('group_title' => $contentInfo['team_title']));
	}

	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/posts', array('post_id' => $report['content_id']));
	}
	
}