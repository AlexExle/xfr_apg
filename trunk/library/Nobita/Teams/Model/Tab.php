<?php

class Nobita_Teams_Model_Tab extends Nobita_Teams_Model_Abstract
{
	private $_tabCache = array();

	public static $extraTabs = array();
	public static function registerTab($tabId, $tabTitle, $tabLink, $enable, $tabExplain = null)
	{
		self::$extraTabs[$tabId] = array(
			'tab_id'	=> $tabId,
			'title' 	=> $tabTitle,
			'link' 		=> $tabLink,
			'enable' 	=> (boolean) $enable,
			'explain' 	=> $tabExplain
		);
	}

	public function getAllTabs($tabExplain = false)
	{
		$tabs = $this->_loadDefaultTabs($tabExplain);
		$extraTabs = Nobita_Teams_Model_Tab::$extraTabs;

		if (empty($this->_tabCache))
		{
			$this->_tabCache = array_merge($tabs, $extraTabs);
		}

		return $this->_tabCache;
	}

	protected function _loadDefaultTabs($explain)
	{
		if (Nobita_Teams_AddOnChecker::getInstance()->isXenMediaExistsAndActive())
		{
			$photoTabTitle = 'Teams_media';
		}
		elseif (Nobita_Teams_AddOnChecker::getInstance()->isSonnbXenGalleryExistsAndActive())
		{
			$photoTabTitle = 'Teams_gallery';
		}
		else
		{
			$photoTabTitle = 'Teams_photos';
		}

		$tabs = array(
			/*'wtype_member' => array(
				'tab_id' => 'member',
				'title' => new XenForo_Phrase('Teams_member_wall'),
			),
			'wtype_staff' => array(
				'tab_id' => 'staff',
				'title' => new XenForo_Phrase('Teams_staff_wall'),
			),*/
			'information' => array(
				'tab_id' => 'information',
				'title'	 => new XenForo_Phrase('Teams_information')
			),
			'member_list' => array(
				'tab_id' => 'members',
				'title' => new XenForo_Phrase('Teams_members'),
			),
			'photos' => array(
				'tab_id' => 'photos',
				'title' => new XenForo_Phrase($photoTabTitle),
			),
			'events' => array(
				'tab_id' => 'events',
				'title' => new XenForo_Phrase('Teams_events'),
			),
			'forums' => array(
				'tab_id' => 'forums',
				'title' => new XenForo_Phrase('Teams_forums'),
			),
			// 'rules' => array(
			// 	'tab_id' => 'rules',
			// 	'title' => new XenForo_Phrase('Teams_rules'),
			// ),
			'statsDaily' => array(
				'tab_id' => 'statsDaily',
				'title' => new XenForo_Phrase('statistics'),
			),
		);

		if ($explain)
		{
			$this->_loadTabExplain($tabs);
		}

		return $tabs;
	}

	protected function _loadTabExplain(array &$tabs)
	{
		foreach($tabs as $key => &$tab)
		{
			$tabId = $tab['tab_id'];
			if ($tabId == 'information')
			{
				continue;
			}

			if (in_array($key, array('wtype_member', 'wtype_moderator')))
			{
				$tab['explain'] = new XenForo_Phrase('Teams_disable_wall_tab_explain');
			}
			else
			{
				$tab['explain'] = new XenForo_Phrase('Teams_disable_'.$key.'_explain');
			}
		}
		unset($tab);

		return $this;
	}

	public function buildTabComponents($tabType, array $tab, array $team)
	{
		if (array_key_exists('link', $tab))
		{
			return $tab;
		}

		$teamModel = $this->_getTeamModel();
		$tabId = $tab['tab_id'];

		switch ($tabType) {
			case 'information':
				$tab['link'] = XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX.'/extra', $team, array('type' => 'information'));
				$tab['enable'] = true;
				break;
			case 'events':
				$tab['link'] = XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX.'/events', $team);
				$tab['enable'] = $teamModel->canViewTabAndContainer($tabId, $team, $team);
				break;
			default:
				$tab['link'] = XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX.'/'.$tabId, $team);
				$tab['enable'] = $teamModel->canViewTabAndContainer($tabId, $team, $team);
		}

		return $tab;
	}

}
