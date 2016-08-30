<?php

class Nobita_Teams_SitemapHandler_Team extends XenForo_SitemapHandler_Abstract
{
	protected $_teamModel;

	protected function _getTeamModel()
	{
		if (!$this->_teamModel)
		{
			$this->_teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		}

		return $this->_teamModel;
	}

	public function getRecords($previousLast, $limit, array $viewingUser)
	{
		$teamModel = $this->_getTeamModel();
		if (!$teamModel->canViewTeams($null, $viewingUser))
		{
			return array();
		}

		$ids = $teamModel->getTeamIdsInRange($previousLast, $limit);
		$teams = $teamModel->getTeamsByIds($ids, array(
			'join' => Nobita_Teams_Model_Team::FETCH_PROFILE
				| Nobita_Teams_Model_Team::FETCH_PRIVACY
				| Nobita_Teams_Model_Team::FETCH_CATEGORY
		));
		ksort($teams);

		return $teams;
	}

	public function isIncluded(array $entry, array $viewingUser)
	{
		return $this->_getTeamModel()->canViewTeamAndContainer($entry, $entry, $null, $viewingUser);
	}

	public function getData(array $entry)
	{
		$entry['title'] = XenForo_Helper_String::censorString($entry['title']);

		return array(
			'loc' => XenForo_Link::buildPublicLink('canonical:' . TEAM_ROUTE_PREFIX, $entry),
			'lastmod' => $entry['last_updated']
		);
	}

	public function getPhraseKey($key)
	{
		return 'Teams_handler_phrase_key_teams';
	}

	public function isInterruptable()
	{
		return true;
	}

}
