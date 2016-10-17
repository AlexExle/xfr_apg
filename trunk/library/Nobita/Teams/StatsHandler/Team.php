<?php

class Nobita_Teams_StatsHandler_Team extends XenForo_StatsHandler_Abstract
{
	public function getStatsTypes()
	{
		return array(
			'team' 			=> new XenForo_Phrase('Teams_teams_addon_prefix'),
			'team_post' 	=> new XenForo_Phrase('Teams_team_posts_addon_prefix'),
			'team_comment' 	=> new XenForo_Phrase('Teams_team_comments_addon_prefix'),
			'team_event' 	=> new XenForo_Phrase('Teams_team_event_addon_prefix')
		);
	}

	public function getData($startDate, $endDate)
	{
		$db = $this->_getDb();
		$teams = $db->fetchPairs(
			$this->_getBasicDataQuery('xf_team', 'team_date', 'team_state = ?'),
			array($startDate, $endDate, 'visible')
		);

		$posts = $db->fetchPairs(
			$this->_getBasicDataQuery('xf_team_post', 'post_date', 'message_state = ?'),
			array($startDate, $endDate, 'visible')
		);

		$comments = $db->fetchPairs(
			$this->_getBasicDataQuery('xf_team_comment', 'comment_date'),
			array($startDate, $endDate)
		);

		$events = $db->fetchPairs(
			$this->_getBasicDataQuery('xf_team_event', 'publish_date'),
			array($startDate, $endDate)
		);

		return array(
			'team' => $teams,
			'team_post' => $posts,
			'team_comment' => $comments,
			'team_event' => $events
		);
	}
	
	
}