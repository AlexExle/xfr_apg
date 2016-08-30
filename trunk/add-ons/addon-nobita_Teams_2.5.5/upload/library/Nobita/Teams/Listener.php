<?php

$constants = array(
	'TEAM_DATAREGISTRY_KEY' => 'Teams_group_perms',
	'TEAM_ROUTE_PREFIX' 	=> Nobita_Teams_Option::get('routePrefix'),
	'ADDON_GROUP_DIR'		=> __DIR__,
);

foreach($constants as $defineName => $defineValue)
{
	if (!defined($defineName)) define($defineName, $defineValue);
}

$autoload = XenForo_Autoloader::getInstance();
$autoload->autoload('Nobita_Teams_helpers');

class Nobita_Teams_Listener
{
	const TEAM_CONTROLLERPUBLIC_FORUM_ADDTHREAD 		= 'Nobita_Teams_XenForo_ControllerPublic_Forum::actionAddThread';
	const XENGALLERY_CONTROLLERPUBLIC_ALBUM_ACTIONSAVE 	= 'Nobita_Teams_sonnb_XenGallery_ControllerPublic_XenGallery_Album::actionSave';

	const DATA_REG_THREADS	= 'nobita_Teams_threads';
	const NODE_TYPE_ID		= 'nobita_Teams_Forum';

	const THREAD_FETCHOPTIONS_JOIN_TEAM = 'fetchOptions_joinTeam';
	const FORUM_FETCHOPTIONS_JOIN_TEAM 	= 'fetchOptions_joinTeam';

	/**
	 * @see Nobita_Teams_XenForo_ControllerAdmin_Option::actionSave
	 */
	public static $defaultRulesData = null;

	/**
	 * @see Nobita_Teams_XenForo_ControllerPublic_Forum::actionAddThread
	 */
	public static $newThreadPosted = false;

	public static function loadControllers($class, array &$extend)
	{
		static $_classes = array(
			'XenForo_ControllerPublic_SpamCleaner',
			'XenForo_ControllerPublic_Forum',
			'XenForo_ControllerPublic_Member',

			'XenForo_ControllerAdmin_Option',
			'XenForo_ControllerAdmin_Tools',
			'XenForo_ControllerAdmin_User',
			'XenForo_ControllerAdmin_Node',

			'XenForo_Model_Post',
			'XenForo_Model_Forum',
			'XenForo_Model_Node',
			'XenForo_Model_Thread',
			'XenForo_Model_Import',
			'XenForo_Model_ForumWatch',

			'XenForo_DataWriter_Forum',
			'XenForo_DataWriter_User',
			'XenForo_DataWriter_Option',
			'XenForo_DataWriter_Discussion_Thread',
			'XenForo_DataWriter_DiscussionMessage_Post',

			// required addon: [bd] Cache
			'bdCache_Model_Cache',

			// sonnb XenGallery
			'sonnb_XenGallery_Model_Album',
			'sonnb_XenGallery_ControllerPublic_XenGallery',
			'sonnb_XenGallery_ControllerPublic_XenGallery_Category',
			'sonnb_XenGallery_ControllerPublic_XenGallery_Author',
			'sonnb_XenGallery_ControllerPublic_XenGallery_Album',
			'sonnb_XenGallery_DataWriter_Album',
			'sonnb_XenGallery_Model_Category',

			// XenMedia
			'XenGallery_ControllerPublic_Media',
			'XenGallery_DataWriter_Media',
			'XenGallery_Model_Media',
			'XenGallery_ViewPublic_Media_Wrapper',
			'XenGallery_Model_Comment',
		);

		if (in_array($class, $_classes))
		{
			$extend[] = 'Nobita_Teams_' . $class;
		}

		if ($class === 'XenForo_Model_Import')
		{
			XenForo_Model_Import::$extraImporters['nSocialGroups_wSocial'] 	= 'Nobita_Teams_Importer_Waindigo_Social';
			XenForo_Model_Import::$extraImporters['nSocialGroups_vB38x'] 	= 'Nobita_Teams_Importer_vBulletin_vB38x';
			XenForo_Model_Import::$extraImporters['nSocialGroups_XFAGroup'] = 'Nobita_Teams_Importer_XfAddOns_Group';
		}
		elseif ($class === 'XenForo_ControllerAdmin_Tools')
		{
			XenForo_CacheRebuilder_Abstract::$builders['Team'] = 'Nobita_Teams_CacheRebuilder_Team';
			XenForo_CacheRebuilder_Abstract::$builders['TeamCategory'] = 'Nobita_Teams_CacheRebuilder_Category';
		}
	}

	public static function loadClasses($class, array &$extend)
	{
		static $classes = array(
			// Image Processor
			'XenForo_Image_Gd',

			'XenForo_ControllerHelper_ForumThreadPost'
		);

		if (in_array($class, $classes))
		{
			$extend[] = 'Nobita_Teams_' . $class;
		}
	}

	public static function controller_post_dispatch(XenForo_Controller $controller, $controllerResponse, $controllerName, $action)
	{
		$type = $controller->getInput()->filterSingle('type', XenForo_Input::STRING);
		if (empty($type))
			$type = $controller->getInput()->filterSingle('t', XenForo_Input::STRING);

		if ('team' == $type)
		{
			$controller->getRouteMatch()->setSections(TEAM_ROUTE_PREFIX);
		}
	}

	public static function threadViewDispatch(XenForo_Controller $controller, $response, $controllerName, $action)
	{
		if (!($response instanceof XenForo_ControllerResponse_View))
		{
			return;
		}

		if ($response->viewName != 'XenForo_ViewPublic_Thread_View' || empty($response->params['thread']))
		{
			return;
		}

		$thread = $response->params['thread'];
		$threadModel = $controller->getModelFromCache('XenForo_Model_Thread');

		$isPollThread = $threadModel->isThreadPoll($thread);
		$isGroupThread = $threadModel->isThreadGroup($thread);

		if (!XenForo_Visitor::getInstance()->hasPermission('Teams', 'view'))
		{
			// use did not permission to view group
			return;
		}

		if ($isPollThread AND empty($thread['team_id']))
		{
			return;
		}

		if (!$isPollThread AND !$isGroupThread)
		{
			return;
		}

		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		$team = $teamModel->getFullTeamById($thread['team_id'], array(
			'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
		));
		if (!$team)
		{
			return;
		}

		if (!$teamModel->canViewTeamAndContainer($team, $team, $error))
		{
			return $controller->responseException($controller->responseError($error));
		}

		$team = $teamModel->prepareTeam($team, $team);
		$response->params['team'] = $team;

		$response->params['groupCatBreadcrumbs'] = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category')->getCategoryBreadcrumb($team);

		$tabs = array(
			'public_wall' => array(
				'link' => Nobita_Teams_Link::buildTeamLink('', $team, array('wtype' => 'public')),
				'title' => $team['title']
			)
		);
		if ($teamModel->canViewTabAndContainer('photos', $team, $team))
		{
			$tabSupported = Nobita_Teams_Option::getTabsSupported();
			$tabs['photos'] = array(
				'link' => Nobita_Teams_Link::buildTeamLink('photos', $team),
				'title' => $tabSupported['photos']['title']
			);
		}
		if ($teamModel->canViewTabAndContainer('threads', $team, $team))
		{
			$tabs['threads'] = array(
				'link' 	=> Nobita_Teams_Link::buildTeamLink('forums', $team),
				'title' => new XenForo_Phrase('Teams_group_forums')
			);
		}
		if ($teamModel->canViewTabAndContainer('events', $team, $team))
		{
			$tabs['events'] = array(
				'link' 	=> Nobita_Teams_Link::buildTeamLink('events', $team),
				'title' => new XenForo_Phrase('Teams_events')
			);
		}

		$response->params['teamViewTabsHeader'] = $tabs;
	}

	public static function widget_framework(array &$renderers)
	{
		// support widget framework.
		$renderers[] = 'Nobita_Teams_WidgetFramework_WidgetRenderer_Team';
		$renderers[] = 'Nobita_Teams_WidgetFramework_WidgetRenderer_Event';
	}

	protected static $_addedUsernameChange = false;
	public static function loadUserModel($class, array &$extend)
	{
		if (!self::$_addedUsernameChange)
		{
			self::$_addedUsernameChange = true;
			XenForo_Model_User::$userContentChanges['xf_team'] = array(array('user_id', 'username'));
			XenForo_Model_User::$userContentChanges['xf_team_privacy'] = array(array('last_update_user_id'));

			XenForo_Model_User::$userContentChanges['xf_team_comment'] = array(array('user_id', 'username'));
			XenForo_Model_User::$userContentChanges['xf_team_member'] = array(array('user_id', 'username'), array('action_user_id', 'action_username'));
			XenForo_Model_User::$userContentChanges['xf_team_post'] = array(array('user_id', 'username'));

			// 1.0.7
			XenForo_Model_User::$userContentChanges['xf_team_category_watch'] = array(array('user_id'));

			XenForo_Model_User::$userContentChanges['xf_team_ban'] = array(array('user_id'), array('ban_user_id'));
			XenForo_Model_User::$userContentChanges['xf_team_event'] = array(array('user_id', 'username'));
		}

		$extend[] = 'Nobita_Teams_' . $class;
	}

	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch($hookName)
		{
			case 'search_form_tabs':
				$contents .= $template->create('Team_search_form_tabs_team', $template->getParams())->render();
				break;
			case 'member_view_tabs_heading':
				$contents .= $template->create('Team_member_view_tabs_heading', $template->getParams())->render();
				break;
			case 'member_view_tabs_content':
				$contents .= $template->create('Team_member_view_tabs_content', $template->getParams())->render();
				break;
			case 'account_alerts_extra':
				$contents .= $template->create('Team_account_alert_preferences', $template->getParams())->render();
				break;
			case 'thread_view_tools_links':
				$contents .= $template->create('Team_thread_view_tools_links', $template->getParams())->render();
				break;
			case 'Team_sidebar_before_stats':
				$params = $template->getParams();

				if (isset($params['_subView']) && ($params['_subView'] instanceof XenForo_Template_Public))
				{
					$subView = $params['_subView'];
					$params = array_merge($params, $subView->getParams());

					$params += $hookParams;

					if ($subView->getTemplateName() == 'Team_event_list')
					{
						$contents .= $template->create('Team_event_list_sidebar', $params)->render();
					}
					else if ($subView->getTemplateName() == 'Team_xenmedia_view')
					{
						$contents .= $template->create('Team_xenmedia_view_sidebar', $params)->render();
					}
				}

				break;
			case 'member_view_info_block':
			case 'member_card_stats':
				$params = $template->getParams();
				$params += $hookParams;

				$params['_view_hook'] = $hookName;
				$contents .= $template->create('Team_user_view_info', $params)->render();
				break;
			//case 'member_card_stats'
			// admin CP
			case 'user_criteria_content':
				$contents .= $template->create('Team_user_criteria_extra', $template->getParams())->render();
				break;
		}
	}

	/**
	 * Determine if the current visitor has permission to view teams
	 *
	 * @var bool
	 */
	protected static $_canViewTeams;
	public static $teamOnNodeList = null;

	public static function template_create(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if (self::$_canViewTeams === null)
		{
			self::$_canViewTeams = XenForo_Visitor::getInstance()->hasPermission('Teams', 'view');
		}

		if (!array_key_exists('canViewTeams', $params))
		{
			$params['canViewTeams'] = self::$_canViewTeams;
		}

		if (!is_null(self::$teamOnNodeList)
			&& ($templateName == 'node_forum_level_2'
				|| $templateName == 'node_forum_level_n'
				|| $templateName == 'node_forum_level_1'
			))
		{

			$params['team'] = self::$teamOnNodeList;
			if (!empty($params['forum']) && isset($params['forum']['lastPost']))
			{
				// disable LastPost Information
				$params['forum']['lastPost'] = array();
			}
		}

		switch ($templateName)
		{
			case 'member_view':
				if (self::$_canViewTeams)
				{
					$template->preloadTemplate('Team_member_view_tabs_content');
					$template->preloadTemplate('Team_member_view_tabs_heading');
				}

				$template->preloadTemplate('Team_user_view_info');
				break;

			case 'search_form':
				if (self::$_canViewTeams)
				{
					$template->preloadTemplate('Team_search_form_tabs_team');
				}
				break;
			case 'thread_view':
				$template->preloadTemplate('Team_message_user_info_text');
				$template->preloadTemplate('Team_thread_view_tools_links');
				break;
			case 'Team_event_list':
				$template->preloadTemplate('Team_event_list_sidebar');
				break;
			case 'Team_xenmedia_view':
				$template->preloadTemplate('Team_xenmedia_view_sidebar');
				break;
		}
	}

	public static function navigation_tabs(array &$extraTabs, $selectedTabId)
	{
		if (self::$_canViewTeams)
		{
			$extraTabs[TEAM_ROUTE_PREFIX] = array(
				'href' 	=> Nobita_Teams_Link::buildTeamLink(''),
				'title' => new XenForo_Phrase("Teams_teams"),
				'position' => Nobita_Teams_Option::get('navigationPosition'),
				'selected' => ($selectedTabId == TEAM_ROUTE_PREFIX),
				'linksTemplate' => 'Team_navigation_tab_links'
			);
		}

	}

	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		if ($template instanceof XenForo_Template_Admin)
		{
			if ($templateName == "tools_rebuild")
			{
				$content .= $template->create('Team_tools_rebuild', $template->getParams())->render();
			}

			if ($templateName == 'option_list')
			{
				$params = $template->getParams();
				if ($params['group'] && $params['group']['group_id'] == 'nobita_Teams')
				{
					$content = $template->create('Team_option_list', $params)->render();
				}
			}

			return;
		}

		if ($templateName === 'Team_xenmedia_add')
		{
			// We are integrated with XenMedia with all versions.
			// Also fixed bug from: http://nobita.me/threads/627/

			$params = $template->getParams();
			$mediaCategoryId = Nobita_Teams_Option::get('XenMediaCategoryId');

			// GOOD. We are preapre inserting our content
			preg_match('#<dl class=\"ctrlUnit.*\">(.*)<\/dl>#siU', $content, $matched);
			if ($matched)
			{
				$inputGroup = array(
					'<input type="hidden" name="group_id" value="'. $params['team']['team_id'] .'" />'
				);

				$content = str_replace($matched[0], implode("\n", $inputGroup), $content);
			}

			preg_match_all('/<input.*name=\"(container_id|container_type)\".*\/>/i', $content, $matched);
			if(!empty($matched[0]))
			{
				foreach($matched[0] as $index => $inputHtml)
				{
					$value = ($matched[1][$index] == 'container_type') ? 'category' : $mediaCategoryId;
					$newInput = '<input type="hidden" name="'. $matched[1][$index] .'" value="'. $value .'" />';

					$content = str_replace($inputHtml, $newInput, $content);
				}
			}

			$entry = $params['mediaEntryArea'];
			if ($entry instanceof XenForo_Template_Public)
			{
				$entry = $entry->render();
			}

			$entryPlaceHolder = '<div class="MediaEntryArea">';
			$entryHolderLength = strlen($entryPlaceHolder);

			$positionOfEntry = strpos($content, $entryPlaceHolder);

			$content = substr($content, 0, $positionOfEntry + $entryHolderLength) . $entry . substr($content, $positionOfEntry + $entryHolderLength);
		}
	}

	public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		$callbacks = array();

		if ($dependencies instanceof XenForo_Dependencies_Public)
		{
			$callbacks += array(
				'grouplogo' 		=> array('Nobita_Teams_Template_Helper_Core', 'helperLogoUrl'),
				'commenttext' 		=> array('Nobita_Teams_Template_Helper_Core', 'helperCommentBodyText'),
				'teamcover' 		=> array('Nobita_Teams_Template_Helper_Core', 'helperCoverUrl'),
				'teamfieldtitle' 	=> array('Nobita_Teams_ViewPublic_Helper_Team', 'getTeamFieldTitle'),
				'teamfieldvalue' 	=> array('Nobita_Teams_ViewPublic_Helper_Team', 'getTeamFieldValueHtml'),

				'teamnumberformat' 	=> array('Nobita_Teams_Template_Helper_Core', 'helperNumberFormat'),
				'grouproute'		=> array('Nobita_Teams_Template_Helper_Core', 'buildGroupRoute'),
				'grouplinktype'		=> array('Nobita_Teams_Template_Helper_Core', 'buildGroupLinkType'),
				'groupoption'		=> array('Nobita_Teams_Option', 'get'),
				'groupribbon'		=> array('Nobita_Teams_Template_Helper_Core', 'helperRibbon'),
			);

			$GLOBALS['XenForoHelperUserBanner'] = XenForo_Template_Helper_Core::$helperCallbacks['userbanner'];
			if ($GLOBALS['XenForoHelperUserBanner'][0] === 'self')
			{
				$GLOBALS['XenForoHelperUserBanner'][0] = 'XenForo_Template_Helper_Core';
			}

			$callbacks['userbanner'] = array('Nobita_Teams_Template_Helper_Core', 'helperUserBanner');
		}

		$callbacks['teamcategoryicon'] = array('Nobita_Teams_Template_Helper_Core', 'helperCategoryIcon');

		foreach($callbacks as $helperName => $callback)
		{
			XenForo_Template_Helper_Core::$helperCallbacks[$helperName] = $callback;
		}
	}

	public static function criteria_user($rule, array $data, array $user, &$returnValue)
	{
		if ($rule == 'team_created')
		{
			if (array_key_exists('team_count', $user) && $user['team_count'] == intval($data['teams']))
			{
				$returnValue = true;
			}
		}

	}

	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		$hashes += Nobita_Teams_FileSums::getHashes();
	}
}
