<?php

class Nobita_Teams_ControllerPublic_Team extends Nobita_Teams_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$teamId = $this->_input->filterSingle('team_id', XenForo_Input::UINT);
		$teamUrl = $this->_input->filterSingle('custom_url', XenForo_Input::STRING);

		if ($teamId || $teamUrl)
		{
			return $this->responseReroute(__CLASS__, 'view'); // random
		}

		$teamModel = $this->_getTeamModel();
		$categoryModel = $this->_getCategoryModel();

		$defaultOrder = 'last_updated';
		$defaultOrderDirection = 'desc';

		$order = $this->_input->filterSingle('order', XenForo_Input::STRING, array('default' => $defaultOrder));
		$criteria = array();

		$criteria += $categoryModel->getPermissionBasedFetchConditions();
		$viewableCategories = $categoryModel->getViewableCategories();
		$criteria['team_category_id'] = array_keys($viewableCategories);

		$categoryList = $categoryModel->groupCategoriesByParent($viewableCategories);
		$categoryList = $categoryModel->applyRecursiveCountsToGrouped($categoryList);

		$categories = array();
		if (isset($categoryList['categoriesGrouped'][0]))
		{
			$categories = $categoryList['categoriesGrouped'][0];
		}

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage = Nobita_Teams_Option::get("teamsPerPage");

		if ($criteria['deleted'] === true || $criteria['moderated'] === true)
		{
			$totalTeams = $teamModel->countTeams($criteria);
		}
		else
		{
			$totalTeams = 0;
			foreach ($categories AS $category)
			{
				$totalTeams += $category['team_count'];
			}
		}

		$totalFeatured = 0;
		foreach ($categories AS $category)
		{
			$totalFeatured += $category['featured_count'];
		}

		$teamFetchOptions = $this->_getTeamListFetchOptions();
		$featuredTeams = array();
		$teams = array();

		if ($totalFeatured)
		{
			$featuredTeams = Nobita_Teams_Helper_Widget::getFeaturedGroupsWidget($criteria['team_category_id']);
		}

		if ($order == 'top')
		{
			$this->_assertRegistrationRequired();

			$teams = Nobita_Teams_Helper_Widget::getSuggestedGroupsWidget($perPage, $criteria['team_category_id']);
			$totalTeams = count($teams);
		}
		else
		{
			$teamFetchOptions = array_merge($teamFetchOptions, array(
				'page' => $page,
				'perPage' => $perPage,
				'direction' => $defaultOrderDirection
			));
			$teams = Nobita_Teams_Helper_Widget::getMostGroupsWidgetByType($order, $criteria, $teamFetchOptions);
		}
		$inlineModOptions = $teamModel->getInlineModOptionsForTeams($teams);

		$this->canonicalizeRequestUrl(Nobita_Teams_Link::buildTeamLink(''));
		$this->canonicalizePageNumber($page, $perPage, $totalTeams, Nobita_Teams_Link::buildTeamLink(''));

		$pageNavParams = array(
			'order' => ($order != $defaultOrder ? $order : false)
		);

		$viewParams = array(
			'categories' => $categoryModel->prepareCategories($categories),
			'categoriesGrouped' => $categoryList,

			'teams' => $teams,
			'featuredTeams' => $featuredTeams,
			'ignoredNames' => $this->_getIgnoredContentUserNames($teams),
			'inlineModOptions' => $inlineModOptions,
			'canAddTeam' => $categoryModel->canAddTeam(),

			'page' => $page,
			'perPage' => $perPage,
			'pageNavParams' => $pageNavParams,
			'pageRoute' => TEAM_ROUTE_PREFIX,
			'pageLinkParam' => false,

			'totalTeams' => $totalTeams,
			'order' => $order,
			'statsCriteria' => $criteria,
			'quickActionReturn' => Nobita_Teams_Link::buildTeamLink('')
		);

		return $this->_getTeamIndexWrapper('index',
			$this->responseView('Nobita_Teams_ViewPublic_Home_List', 'Team_index', $viewParams)
		);
	}

	/* BUILDING EVENT SYSTEM! */
	public function actionEventAdd()
	{
		// display form allow add event
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		if (!$this->_getTeamModel()->canViewTabAndContainer('events', $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$this->_request->setParam('team_id', $team['team_id']);
		return $this->responseReroute('Nobita_Teams_ControllerPublic_Event', 'add');
	}

	public function actionForums()
	{
		return $this->responseReroute('Nobita_Teams_ControllerPublic_Forum', 'index');
	}

	/**
	 * Finds valid teams matching the specified username prefix.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionFind()
	{
		$q = ltrim($this->_input->filterSingle('q', XenForo_Input::STRING, array('noTrim' => true)));

		if ($q !== '' && utf8_strlen($q) >= 2)
		{
			$teams = $this->_getTeamModel()->getTeams(
				array(
					'title' => $q,
					'deleted' => false
				),
				array('limit' => 10)
			);
		}
		else
		{
			$teams = array();
		}

		$viewParams = array(
			'teams' => $teams
		);

		return $this->responseView(
			'Nobita_Teams_ViewPublic_Team_Find',
			'team_autocomplete',
			$viewParams
		);
	}

	public function actionTabs()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamModel()->canManageTabs($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$fieldModel = $this->_getFieldModel();
		$teamModel = $this->_getTeamModel();

		$customFieldsGrouped = $fieldModel->getTeamFieldsForEdit(
			$category['team_category_id'], empty($team['team_id']) ? 0 : $team['team_id']
		);

		$customFieldsGrouped = $fieldModel->prepareTeamFields($customFieldsGrouped, true,
			!empty($team['customFields']) ? $team['customFields'] : array()
		);

		$tabs = Nobita_Teams_Option::getTabsSupported();
		if (empty($category['allow_enable_disable_tabs']))
		{
			$hiddenTabs = array_map('trim', explode(',', $category['disable_tabs_default']));

			foreach($hiddenTabs as $hiddenTabId)
			{
				if (isset($tabs[$hiddenTabId]))
				{
					unset($tabs[$hiddenTabId]);
				}
			}
		}

		foreach($customFieldsGrouped as $fieldId => $fieldDef)
		{
			if($fieldDef['display_group'] != 'new_tab')
			{
				continue;
			}

			$tabs[$fieldDef['field_id']] = array(
				'tab_id' => $fieldDef['field_id'],
				'title' => $fieldDef['title'],
			);
		}

		if ($this->_request->isPost())
		{
			$enableTabs = $this->_input->filterSingle('enable_tabs', XenForo_Input::ARRAY_SIMPLE);
			$disableTabs = array();

			foreach ($tabs as $tabId => $tab)
			{
				if (in_array($tabId, $enableTabs))
				{
					continue;
				}

				$disableTabs[] = $tabId;
			}

			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
			$dw->setExistingData($team['team_id']);
			$dw->set('disable_tabs', implode(',', $disableTabs));
			$dw->save();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('', $team)
			);
		}
		else
		{
			return $this->_getTeamViewWrapper('information', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Team_Tab', 'Team_disable_tabs', array(
					'team' => $team,
					'category' => $category,
					'tabs' => $tabs,
					'disabledTabs' => $team['disabledTabs']
				))
			);
		}
	}

	public function actionStatsDaily()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamModel()->canViewTabAndContainer('statsDaily', $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$grouping = 'daily';
		$statsModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Stats');

		$tz = new DateTimeZone('GMT');

		if (!$start = $this->_input->filterSingle('start', XenForo_Input::DATE_TIME, array('timeZone' => $tz)))
		{
			$start = strtotime('-1 month');
		}

		if (!$end = $this->_input->filterSingle('end', XenForo_Input::DATE_TIME, array('dayEnd' => true, 'timeZone' => $tz)))
		{
			$end = XenForo_Application::$time;
		}

		if (!$statsTypes = $this->_input->filterSingle('statsTypes', XenForo_Input::ARRAY_SIMPLE))
		{
			$statsTypes = array('member_wall', 'staff_wall');
		}

		$groupings = array(
			'daily' => array(
				'printDateFormat' => 'absolute',
				'xAxisTime' => true
			),
			'monthly' => array(
				'printDateFormat' => 'M Y',
				'groupDateFormat' => 'Ym'
			),
			'weekly' => array(
				'printDateFormat' => '\WW Y',
				'groupDateFormat' => 'YW'
			)
		);

		if (!isset($groupings[$grouping]))
		{
			$grouping = 'daily';
		}

		$groupingConfig = $groupings[$grouping];

		$plots = $statsModel->getStatsData($team['team_id'], $start, $end, $statsTypes, $grouping);
		$dateMap = array();

		foreach ($plots AS $type => $plot)
		{
			$output = $statsModel->prepareGraphData($plot, $grouping);

			$plots[$type] = $output['plot'];
			$dateMap[$type] = $output['dateMap'];
		}

		if (empty($groupingConfig['xAxisTime']))
		{
			$output = $statsModel->filterGraphDataDates($plots, $dateMap);
			$plots = $output['plots'];
			$dateMap = $output['dateMap'];
		}

		$viewParams = array(
			'plots' => $plots,
			'dateMap' => $dateMap,
			'start' => $start,
			'end' => $end,
			'endDisplay' => ($end >= XenForo_Application::$time ? 0 : $end),
			'grouping' => $grouping,
			'groupingConfig' => $groupingConfig,

			'team' => $team,
			'category' => $category,
			'datePresets' => XenForo_Helper_Date::getDatePresets(),
			'statsTypeOptions' => $statsModel->getStatsTypeOptions($statsTypes),
			'statsTypePhrases' => $statsModel->getStatsTypePhrases($statsTypes),
		);

		return $this->_getTeamViewWrapper('statsDaily', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Team_StatsDaily', 'Team_stats_daily', $viewParams)
		);
	}

	public function actionToggleFeatured()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));

		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		if (!$this->_getTeamModel()->canFeatureUnfeatureTeam($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);
		if (empty($redirect))
		{
			$redirect = Nobita_Teams_Link::buildTeamLink('', $team);
		}

		if ($team['feature_date'])
		{
			$this->_getTeamModel()->unfeatureTeam($team);
			$redirectPhrase = 'Teams_team_unfeatured';
			$actionPhrase = 'Teams_feature_team';
		}
		else
		{
			$this->_getTeamModel()->featureTeam($team);

			$redirectPhrase = 'Teams_feature_team';
			$actionPhrase = 'Teams_team_unfeatured';
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirect,
			new XenForo_Phrase($redirectPhrase),
			array('actionPhrase' => new XenForo_Phrase($actionPhrase))
		);
	}

	public function actionFeatured()
	{
		$this->canonicalizeRequestUrl(Nobita_Teams_Link::buildTeamLink('featured'));

		$teamModel = $this->_getTeamModel();
		$categoryModel = $this->_getCategoryModel();

		$viewableCategories = $categoryModel->prepareCategories($categoryModel->getViewableCategories());

		$categoryList = $categoryModel->groupCategoriesByParent($viewableCategories);
		$categoryList = $categoryModel->applyRecursiveCountsToGrouped($categoryList);

		$searchCategoryIds = array_keys($viewableCategories);


		$teams = $teamModel->getFeaturedTeamsInCategories($searchCategoryIds,
			$this->_getTeamListFetchOptions()
		);
		$teams = Nobita_Teams_Helper_Widget::filterUnviewableAndPrepareTeams($teams);

		if (!$teams)
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				Nobita_Teams_Link::buildTeamLink('')
			);
		}

		$viewParams = array(
			'categoriesGrouped' => $categoryList,

			'teams' => $teams,
			'ignoredNames' => $this->_getIgnoredContentUserNames($teams),

			'inlineModOptions' => $teamModel->getInlineModOptionsForTeams($teams)
		);
		return $this->responseView('Nobita_Teams_ViewPublic_Team_Featured', 'Team_featured', $viewParams);
	}

	public function actionReassign()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamModel()->canReassignTeam($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		if ($this->isConfirmedPost())
		{
			$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserByName(
				$this->_input->filterSingle('username', XenForo_Input::STRING),
				array('join' => XenForo_Model_User::FETCH_USER_PERMISSIONS)
			);
			$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
			if (!$user || !$this->_getTeamModel()->canViewTeams($null, $user))
			{
				return $this->responseError(new XenForo_Phrase('Teams_you_may_only_reassign_team_to_user_with_permission_to_view'));
			}

			$memberRecord = $this->_getTeamModel()->getTeamMemberRecord($team['team_id'], $user);
			if(!$this->_getTeamModel()->isTeamMember($team['team_id'], $user))
			{
				return $this->responseError(new XenForo_Phrase('Teams_you_may_only_reassign_team_to_members_of_team'));
			}

			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
			$dw->setExistingData($team['team_id']);

			$dw->bulkSet(array(
				'user_id' => $user['user_id'],
				'username' => $user['username']
			));
			$dw->save();

			/* update new position for exisiting owner. */
			$oldUser = array(
				'user_id' => $dw->getExisting('user_id'),
				'team_id' => $team['team_id']
			);
			$memberDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Member');
			$memberDw->setExistingData($oldUser);
			$memberDw->set('member_role_id', 'member');
			$memberDw->save();

			$newUserData = array(
				'user_id' => $user['user_id'],
				'team_id' => $team['team_id'],
				'username' => $user['username']
			);

			$newDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Member');

			$newDw->setExistingData($memberRecord);
			$newDw->bulkSet($newUserData);
			$newDw->set('member_role_id', 'admin');
			$newDw->save();

			$visitor = XenForo_Visitor::getInstance();
			if (XenForo_Model_Alert::userReceivesAlert($user, 'team', 'reassign') && $visitor['user_id'] != $user['user_id'])
			{
				// make sure user allow get alerts.
				if ($memberRecord['send_alert'])
				{
					XenForo_Model_Alert::alert($user['user_id'],
						$visitor['user_id'], $visitor['username'],
						'team', $team['team_id'],
						'reassign'
					);
				}
			}

			XenForo_Model_Log::logModeratorAction('team', $team, 'reassign', array('from' => $dw->getExisting('username')));
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('', $team)
			);
		}
		else
		{
			return $this->_getTeamViewWrapper('information', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Team_Reassign', 'Team_reassign', array(
					'team' => $team,
					'category' => $category
				))
			);
		}
	}

	public function actionRibbon()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$teamModel = $this->_getTeamModel();
		if (!$this->_getTeamModel()->canChooseRibbon($team, $category, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}

		if ($this->_request->isPost())
		{
			$remove = $this->_input->filterSingle('remove', XenForo_Input::UINT);

			if (empty($remove))
			{
				$text = $this->_input->filterSingle('ribbon_text', XenForo_Input::STRING);
				$textLength = strlen($text);
				if ($textLength == 0)
				{
					return $this->responseError(new XenForo_Phrase('please_enter_valid_value'));
				}

				$class = $this->_input->filterSingle('ribbon_display_class', XenForo_Input::STRING);
				if (!in_array($class, $category['ribbonStyling']))
				{
					// hack of class
					return $this->responseError(new XenForo_Phrase('please_enter_valid_value'));
				}

				$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
				$dw->setExistingData($team);

				$dw->set('ribbon_text', $text);
				$dw->set('ribbon_display_class', $class);

				$visitor = XenForo_Visitor::getInstance();
				$dw->setOption(Nobita_Teams_DataWriter_Team::OPTION_ADMIN_EDIT, $visitor->is_admin);

				$dw->save();
			}
			else
			{
				$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
				$dw->setExistingData($team);

				$dw->set('ribbon_text', '');
				$dw->set('ribbon_display_class', '');
				$dw->save();

				$this->_getTeamModel()->updateAllRibbonForMember($team['team_id']);
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('', $team)
			);
		}
		else
		{
			return $this->_getTeamViewWrapper('information', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Team_Ribbon', 'Team_choose_ribbon', array(
					'team' => $team,
					'category' => $category,

					'displayStyles' => $category['ribbonStyling'],
					'remove' => ($team['ribbon_text'] && $team['ribbon_display_class'])
				))
			);
		}
	}

	public function actionChooseRibbon()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));
		$teamFtpHelper = $this->_getTeamHelper();
		list($team, $category) = $teamFtpHelper->assertTeamValidAndViewable();

		if (!XenForo_Visitor::getUserId())
		{
			return $this->responseNoPermission();
		}

		$visitor = XenForo_Visitor::getInstance();

		$member = $this->_getMemberModel()->getRecordByKeys($visitor['user_id'], $team['team_id']);

		if (!$member)
		{
			return $this->responseError(new XenForo_Phrase('Teams_you_can_not_select_this_ribbon'), 404);
		}

		if ($visitor['team_ribbon_id'] == $team['team_id'])
		{
			$this->_getTeamModel()->removeUserRibbon(XenForo_Visitor::getUserId());
		}
		else
		{
			$this->_getTeamModel()->applyUserRibbon($team['team_id'], XenForo_Visitor::getUserId());
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$this->getDynamicRedirect(Nobita_Teams_Link::buildTeamLink('', $team))
		);
	}

	/**
	 * Action allow quick approve team.
	 *
	 * @return XenForo_ControllerResponse_Redirect
	 */
	public function actionApprove()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamModel()->canApproveTeam($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
		$dw->setExistingData($team);
		$dw->set('team_state', 'visible');
		$dw->save();

		XenForo_Model_Log::logModeratorAction('team', $team, 'approve');
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildTeamLink('', $team)
		);
	}

	/**
	 * Action allow quick unapprove team.
	 *
	 * @return XenForo_ControllerResponse_Redirect
	 */
	public function actionUnapprove()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamModel()->canUnapproveTeam($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
		$dw->setExistingData($team);
		$dw->set('team_state', 'moderated');
		$dw->save();

		XenForo_Model_Log::logModeratorAction('team', $team, 'unapprove');
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildTeamLink('', $team)
		);
	}

	public function actionAdd()
	{
		$categoryModel = $this->_getCategoryModel();

		$visitor = XenForo_Visitor::getInstance();

		$maxTeams = Nobita_Teams_Option::get('maxAddGroups');

		$categoryId = $this->_input->filterSingle('team_category_id', XenForo_Input::UINT);
		if (empty($categoryId))
		{
			$categories = $categoryModel->prepareCategories($categoryModel->getViewableCategories());
			return $this->responseView('Nobita_Teams_ViewPublic_Team_ChooseCategory', 'Team_choose_category', array(
				'categories' => $categories
			));
		}

		$category = $this->_getTeamHelper()->assertCategoryValidAndViewable($categoryId);
		if (!$categoryModel->canAddTeam($category, $error))
		{
			$errorPhrase = new XenForo_Phrase($error, array(
				'max_group' => XenForo_Locale::numberFormat($maxTeams),
				'group_count' => XenForo_Locale::numberFormat($visitor['team_count'])
			));
			throw $this->getErrorOrNoPermissionResponseException($errorPhrase, false);
		}

		$team = array(
			'team_category_id' => $category['team_category_id'],
			'privacy_state' => $category['default_privacy']
		);

		return $this->_getTeamAddOrEditResponse($team, $category);
	}

	protected function _getTeamAddOrEditResponse(array $team, array $category)
	{
		$categoryModel = $this->_getCategoryModel();

		$categories = $categoryModel->getViewableCategories();
		// TODO: filter out ones that they can't add to that don't have children?
		// May need to do something slightly different for editing.

		$fieldModel = $this->_getFieldModel();
		$customFields = $fieldModel->getTeamFieldsForEdit(
			$category['team_category_id'], empty($team['team_id']) ? 0 : $team['team_id']
		);
		$customFields = $fieldModel->prepareTeamFields($customFields, true,
			!empty($team['customFields']) ? $team['customFields'] : array()
		);

		// added 1.0.7
		$parentTabsGrouped = array();
		foreach ($customFields as $fieldId => $field)
		{
			if ($field['display_group'] == 'parent_tab')
			{
				$parentTabsGrouped[$fieldId] = $field;
				unset($customFields[$fieldId]);
			}
		}

		$visitor = XenForo_Visitor::getInstance();
		$canEditPrivacy  = false;

		if (empty($team['team_id']))
		{
			$canEditCategory = true;
			$canEditTitle = true;
			$canEditPrivacy = Nobita_Teams_Option::get('showPrivacyUponCreating');
		}
		else
		{
			$canEditCategory = XenForo_Permission::hasPermission($visitor['permissions'], 'Teams', 'editAny');
			$canEditTitle = $this->_getTeamModel()->canEditTitleAndTagLine($team, $category);

			if($canEditCategory || ($team['user_id'] == $visitor['user_id']))
			{
				$canEditPrivacy = Nobita_Teams_Option::get('showPrivacyUponCreating');
			}
		}

		/** @var XenForo_Model_Tag $tagModel */
		$tagModel = Nobita_Teams_Container::getModel('XenForo_Model_Tag');
		$tagger = $tagModel->getTagger('team');
		$tagger->setPermissionsFromContext($team, $category);

		$canEditTags = false;
		$editTags = array();

		if ($this->_getTeamModel()->canEditTags(empty($team['user_id']) ? null : $team, $category))
		{
			$canEditTags = true;

			if (! empty($team['team_id']))
			{
				/** @var XenForo_Model_Tag $tagModel */
				$tagModel = Nobita_Teams_Container::getModel('XenForo_Model_Tag');
				$tagger = $tagModel->getTagger('team');
				$tagger->setContent($team['team_id'])->setPermissionsFromContext($team, $category);

				$editTags = $tagModel->getTagListForEdit('team', $team['team_id'], $tagger->getPermission('removeOthers'));
			}
		}

		$viewParams = array(
			'team' => $team,

			'category' => $category,
			'categories' => $categoryModel->prepareCategories($categories),
			'categoryBreadcrumbs' => $this->_getCategoryModel()->getCategoryBreadcrumb($category),

			'canEditCategory' => $canEditCategory,
			'canEditTitle' => $canEditTitle,

			'customFields' => $fieldModel->groupTeamFields($customFields),
			'parentTabsGrouped' => $fieldModel->groupTeamFields($parentTabsGrouped),

			'canEditPrivacy' => $canEditPrivacy,
			'secretPrivacy' => $this->_getTeamModel()->canAddSecretTeam(),
			'canEditTags' => $canEditTags,
			'tags' => $editTags
		);

		return $this->responseView('Nobita_Teams_ViewPublic_Team_Add', 'Team_add', $viewParams);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$categoryModel = $this->_getCategoryModel();

		$visitor = XenForo_Visitor::getInstance();

		$teamId = $this->_input->filterSingle('team_id', XenForo_Input::UINT);
		$teamUrl = $this->_input->filterSingle('custom_url', XenForo_Input::STRING);

		$teamId = (empty($teamId) ? $teamUrl : $teamId);
		if ($teamId)
		{
			list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
			if (!$this->_getTeamModel()->canEditTeam($team, $category, $key))
			{
				throw $this->getErrorOrNoPermissionResponseException($key);
			}

			$canEditCategory = XenForo_Permission::hasPermission($visitor['permissions'], 'Teams', 'editAny');
			$canEditTitle = $this->_getTeamModel()->canEditTitleAndTagLine($team, $category);
		}
		else
		{
			$team = false;
			$category = false;
			$canEditCategory = true;
			$canEditTitle = true;
		}

		$teamData = $this->_input->filter(array(
			'team_category_id' => XenForo_Input::UINT,
			'title' => XenForo_Input::STRING,
			'tag_line' => XenForo_Input::STRING,
			'tags' => XenForo_Input::STRING,
		));
		if (!$teamData['team_category_id'])
		{
			return $this->responseError(new XenForo_Phrase('Teams_you_must_select_category'));
		}

		if (!$canEditTitle)
		{
			unset($teamData['title'], $teamData['tag_line']);
		}

		$newCategory = $category;

		if ($canEditCategory)
		{
			if (!$team || $team['team_category_id'] != $teamData['team_category_id'])
			{
				// new team or changing category - let's make sure we can do that
				$newCategory = $this->_getTeamHelper()->assertCategoryValidAndViewable($teamData['team_category_id']);
				if (!$this->_getCategoryModel()->canAddTeam($newCategory, $key))
				{
					throw $this->getErrorOrNoPermissionResponseException($key);
				}
			}

			$categoryId = $teamData['team_category_id'];
		}
		else
		{
			$categoryId = $team['team_category_id'];
			unset($teamData['team_category_id']);
		}

		$about = $this->getHelper('Editor')->getMessageText('about', $this->_input);
		$about = XenForo_Helper_String::autoLinkBbCode($about);

		/**	 @var $writer Nobita_Teams_DataWriter_Team   **/
		$writer = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');

		$canEditPrivacy = false;
		if (empty($teamId))
		{
			$canEditPrivacy = Nobita_Teams_Option::get('showPrivacyUponCreating');
		}
		else
		{
			$canEditPrivacy = (Nobita_Teams_Option::get('showPrivacyUponCreating') AND $team['user_id'] == $visitor['user_id']);
		}

		if ($canEditPrivacy)
		{
			$teamData = array_merge($teamData, $this->_input->filter(array(
				'allow_guest_posting' 		=> XenForo_Input::UINT,
				'always_moderate_join' 		=> XenForo_Input::UINT,
				'always_moderate_posting' 	=> XenForo_Input::UINT,
				'allow_member_posting' 		=> XenForo_Input::UINT,
				'privacy_state' 			=> XenForo_Input::STRING,
				'always_req_message' 		=> XenForo_Input::UINT,

				// 1.1.3
				'allow_member_event' 		=> XenForo_Input::UINT,
			)));

			if ($teamData['privacy_state'] == Nobita_Teams_Model_Team::PRIVACY_SECRET)
			{
				if (! $this->_getTeamModel()->canAddSecretTeam())
				{
					return $this->responseError(new XenForo_Phrase('Teams_please_enter_valid_privacy_value'));
				}
			}
		}

		$tagger = null;

		if ($this->_getTeamModel()->canEditTags($team ? $team : null, $newCategory)
			&& XenForo_Application::$versionId > 1050000
		)
		{
			$editTags = array();
			$tags = $teamData['tags'];

			$tagModel = Nobita_Teams_Container::getModel('XenForo_Model_Tag');
			$tagger = $tagModel->getTagger('team');

			if (!empty($team['team_id']))
			{
				$tagger->setPermissionsFromContext($team, $newCategory);
				$editTags = $tagModel->getTagListForEdit('team', $team['team_id'], $tagger->getPermission('removeOthers'));
				if ($editTags['uneditable'])
				{
					// this is mostly a sanity check; this should be ignored
					$tags .= (strlen($tags) ? ', ' : '') . implode(', ', $editTags['uneditable']);
				}
			}
			else
			{
				$tagger->setPermissionsFromContext($newCategory);
			}

			$teamData['tags'] = $tagModel->splitTags($tags);
			$tagger->setTags($teamData['tags']);
			$writer->mergeErrors($tagger->getErrors());
		}
		else
		{
			unset($teamData['tags']);
		}

		if ($teamId)
		{
			$writer->setExistingData($team['team_id']);
		}
		else
		{
			$writer->set('user_id', $visitor['user_id']);
			$writer->set('username', $visitor['username']);

			$writer->set('privacy_state', $newCategory['default_privacy']);
			$writer->set('disable_tabs', $newCategory['disable_tabs_default']);
		}

		$writer->bulkSet($teamData);

		if (!utf8_strlen($about))
		{
			return $this->responseError(new XenForo_Phrase('Teams_please_enter_your_group_description'));
		}
		$writer->set('about', $about);

		if (!$teamId && $newCategory['team_category_id'] != $category['team_category_id'])
		{
			if ($newCategory['always_moderate_create']
				&& ($writer->get('team_state') == "visible" || !$teamId)
				&& !XenForo_Visitor::getInstance()->hasPermission('Teams', 'approveUnapprove')
			)
			{
				$writer->set('team_state', "moderated");
			}
		}

		if (!$teamId)
		{
			$watch = XenForo_Visitor::getInstance()->default_watch_state;
			if (!$watch)
			{
				$watch = 'watch_no_email';
			}

			$writer->setExtraData(Nobita_Teams_DataWriter_Team::DATA_THREAD_WATCH_DEFAULT, $watch);
		}

		$customFields = $this->_getTeamHelper()->getCustomFieldValues($null, $shownCustomFields);
		$writer->setCustomFields($customFields, $shownCustomFields);

		$writer->preSave();

		if (!$writer->hasErrors())
		{
			// processing something! example: spam check!
			$this->assertNotFlooding('post'); // use the action of "posting" as the trigger
		}

		$writer->save();
		$team = $writer->getMergedData();

		if ($tagger)
		{
			$tagger->setContent($team['team_id'], true)
				->save();
		}

		if ($writer->isUpdate() && XenForo_Visitor::getUserId() != $team['user_id'])
		{
			$basicLog = $this->_getLogChanges($writer);
			if ($basicLog)
			{
				XenForo_Model_Log::logModeratorAction('team', $team, 'edit', $basicLog);
			}
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildTeamLink('', $team)
		);
	}

	public function actionEdit()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		return $this->_getTeamAddOrEditResponse($team, $category);
	}

	public function actionURLPortions()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamModel()->canCustomizeUrlPortions($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$input = $this->_input->filterSingle('custom_url', XenForo_Input::STRING);
		if ($this->_request->isPost())
		{
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
			$dw->setExistingData($team);
			$dw->set('custom_url', $input);
			$dw->save();

			$newData = $dw->getMergedData();
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
				Nobita_Teams_Link::buildTeamLink('', $newData)
			);
		}
		else
		{
			return $this->responseView('Nobita_Teams_ViewPublic_Team_URLPortions', 'Team_url_portions', array(
				'team' => $team,
				'category' => $category,
				'categoryBreadcrumbs' => $this->_getCategoryModel()->getCategoryBreadcrumb($category)
			));
		}
	}

	public function actionRules()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$update = $this->_input->filterSingle('update', XenForo_Input::BOOLEAN);
		$canUpdateRules = $this->_getTeamModel()->canUpdateRules($team, $category, $error);
		if ($update && !$canUpdateRules)
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$user = $this->getModelFromcache('XenForo_Model_User')->getUserById($team['last_update_user_id']);

		return $this->_getTeamViewWrapper('information', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Team_UpdateRule', 'Team_rule_view', array(
				'team' => $team,
				'category' => $category,
				'update' => $update,
				'user' => $user,
				'canUpdateRules' => $canUpdateRules
			))
		);
	}

	public function actionUpdateRules()
	{
		$this->_assertPostOnly();

		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamModel()->canUpdateRules($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$rules = $this->getHelper('Editor')->getMessageText('rules', $this->_input);
		$rules = XenForo_Helper_String::autoLinkBbCode($rules);

		$teamDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
		$teamDw->setExistingData($team);

		$teamDw->set('rules', $rules);
		$teamDw->set('last_update_rule', XenForo_Application::$time);
		$teamDw->set('last_update_user_id', XenForo_Visitor::getUserId());

		$teamDw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildTeamLink('rules', $team)
		);
	}

	public function actionUpdatePrivacy()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$visitor = XenForo_Visitor::getInstance();
		if ($visitor['user_id'] != $team['user_id'])
		{
			return $this->responseNoPermission();
		}

		$viewParams = array(
			'team' => $team,
			'category' => $category,
			'categoryBreadcrumbs' => $this->_getCategoryModel()->getCategoryBreadcrumb($category),
			'secretPrivacy' => $this->_getTeamModel()->canAddSecretTeam()
		);

		return $this->_getTeamViewWrapper('information', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Team_UpdateInfo', 'Team_update_privacy', $viewParams)
		);
	}

	public function actionUpdatePrivacySave()
	{
		$this->_assertPostOnly();

		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$visitor = XenForo_Visitor::getInstance();
		if ($visitor['user_id'] != $team['user_id'])
		{
			return $this->responseNoPermission();
		}

		$privacy = $this->_input->filterSingle('privacy_state', XenForo_Input::STRING);
		if ($privacy == Nobita_Teams_Model_Team::PRIVACY_SECRET
			&& !$this->_getTeamModel()->canAddSecretTeam($error)
		)
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$inputDw = $this->_input->filter(array(
			'allow_guest_posting' 		=> XenForo_Input::UINT,
			'always_moderate_join' 		=> XenForo_Input::UINT,
			'always_moderate_posting' 	=> XenForo_Input::UINT,
			'allow_member_posting' 		=> XenForo_Input::UINT,
			'privacy_state' 			=> XenForo_Input::STRING,
			'always_req_message' 		=> XenForo_Input::UINT,

			// 1.1.3
			'allow_member_event' 		=> XenForo_Input::UINT,
			'remove_inactive_date' 		=> XenForo_Input::UINT
		));

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
		$dw->setExistingData($team);

		$dw->bulkSet($inputDw);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildTeamLink('', $team)
		);
	}

	public function actionPhotos()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamModel()->canViewTabAndContainer('photos', $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		return Nobita_Teams_Helper_Photo::responseView($this, $this->_input, array(
			'team' => $team,
			'category' => $category
		));
	}

	public function actionExtra()
	{
		$selectedTab = $this->_input->filterSingle('type', XenForo_Input::STRING);
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$teamId = $team['team_id'];
		$visitor = XenForo_Visitor::getInstance();

		$fieldModel = $this->_getFieldModel();
		$teamModel = $this->_getTeamModel();

		$customFields = $fieldModel->getTeamFieldsForEdit(
			$category['team_category_id'], empty($team['team_id']) ? 0 : $team['team_id']
		);
		$customFields = $fieldModel->prepareTeamFields($customFields, true,
			!empty($team['customFields']) ? $team['customFields'] : array()
		);

		$customFieldsGrouped = array();
		$parentTabsGrouped = array();

		$ruleUpdater = array();

		if ($selectedTab == 'information')
		{
			foreach ($customFields as $fieldId => $field)
			{
				if ($field['display_group'] == 'extra_tab')
				{
					$customFieldsGrouped[$fieldId] = $field;
				}
			}

			$ruleUpdater = $this->getModelFromcache('XenForo_Model_User')->getUserById($team['last_update_user_id']);

		}
		else
		{
			if (!$this->_getTeamModel()->canViewTabAndContainer('extra', $team, $category, $error))
			{
				throw $this->getErrorOrNoPermissionResponseException($error);
			}

			foreach ($customFields as $fieldId => $field)
			{
				if ($fieldId == $selectedTab)
				{
					$customFieldsGrouped[$fieldId] = $field;
				}
			}
			unset($fieldId, $field);

			foreach($customFieldsGrouped as $fieldId => $field)
			{
				if(in_array($fieldId, $team['disabledTabs']))
				{
					throw $this->getNoPermissionResponseException();
					break;
				}
			}

			foreach ($customFields as $fieldId => $field)
			{
				if ($field['display_group'] == 'parent_tab' && $field['parent_tab_id'] == $selectedTab)
				{
					$parentTabsGrouped[$fieldId] = $field;
					unset($customFields[$fieldId]);
				}
			}
		}

		$viewParams = array(
			'team' => $team,
			'category' => $category,
			'selectedTab' => $selectedTab,

			'ruleUpdater' => $ruleUpdater,
			'canUpdateRules' => $this->_getTeamModel()->canUpdateRules($team, $category),

			'customFieldsGrouped' => $fieldModel->groupTeamFields($customFieldsGrouped),
			'parentTabsGrouped' => $fieldModel->groupTeamFields($parentTabsGrouped)
		);

		return $this->_getTeamViewWrapper($selectedTab, $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Team_Extra', 'Team_extra', $viewParams)
		);
	}

	public function actionPreview()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		return $this->responseView('Nobita_Teams_ViewPublic_Team_Preview', 'Team_view_preview', array(
			'team' => $team,
			'category' => $category
		));
	}

	public function actionView()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$teamModel = $this->_getTeamModel();
		$memberModel = $this->_getMemberModel();
		$postModel = $this->_getPostModel();

		$visitor = XenForo_Visitor::getInstance();
		$memberRecord = $memberModel->getTeamMemberRecord($team['team_id']);

		if($teamModel->isClosed($team) && !$memberModel->isTeamMember($team['team_id']))
		{
			$this->_request->setParam('team_id', $team['team_id']);
			$this->_request->setParam('type', 'information');

			return $this->actionExtra();
		}

		$attachmentHash = null;
		$attachmentParams = $teamModel->getAttachmentParams($team, $category, array(
			'team_id' => $team['team_id'],
			'content_type' => 'team_post'
		), null, null, $attachmentHash);

		$posts = array();
		if($team['sticky_message_count'])
		{
			$posts = $postModel->getPostsForTeamId($team['team_id'], array(
				'sticky' => true
			), array(
				'join' => Nobita_Teams_Model_Post::FETCH_TEAM
					| Nobita_Teams_Model_Post::FETCH_BBCODE_CACHE
					| Nobita_Teams_Model_Post::FETCH_POSTER,
				'likeUserId' => $visitor['user_id'],
				'watchUserId' => $visitor['user_id'],
			));

			foreach($posts as $postId => &$post)
			{
				if(!$postModel->canViewPost($post, $team, $category))
				{
					unset($posts[$postId]);
					continue;
				}

				$post = $postModel->preparePost($post, $team, $category);
			}

			$posts = $postModel->getAndMergeAttachmentsIntoPosts($posts);
			$posts = $this->_getCommentModel()->addCommentsToContentList($posts);
		}

		$newsFeedModel = $this->_getNewsFeedModel();
		$newsFeeds = array();

		$showNewsFeeds = Nobita_Teams_Option::get('newsfeedItems');
		if($showNewsFeeds)
		{
			$conditions = array();
			$newsFeeds = $newsFeedModel->getNewsFeedForTeam($team['team_id'], $conditions, array(
				'limit' => $showNewsFeeds
			));

			$newsFeeds = $newsFeedModel->fillOutNewsFeedItems($newsFeeds);
		}

		$viewParams = array(
			'team' => $team,
			'category' => $category,
			'attachmentParams' => $attachmentParams,
			'attachmentConstraints' => Nobita_Teams_Container::getModel('XenForo_Model_Attachment')->getAttachmentConstraints(),
			'canViewAttachments' => $teamModel->canViewAttachments($team, $category),
			'canUploadAttachments' => $this->_getCategoryModel()->canUploadAttachments($category),

			'posts' => $posts,
			'newsFeeds' => $newsFeeds,
			'firstNewsFeedItem' => reset($newsFeeds),
			'lastNewsFeedItem' => end($newsFeeds),

			'sharePrivacy' => $this->_getPostModel()->getSharePrivacy($team),
			'canSharePost' => $teamModel->canPostOnTeam($team, $category),
		);

		return $this->_getTeamViewWrapper('newsfeed', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Team_NewsFeed', 'Team_wall_newsfeed', $viewParams)
		);
	}

	public function actionCover()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!Nobita_Teams_Container::getModel('Nobita_Teams_Model_Cover')->canUploadCover($team, $category, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}

		return $this->_getTeamViewWrapper('cover', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Team_Cover', 'Team_cover', array(
				'team' => $team,
				'category' => $category
			))
		);
	}

	public function actionCoverUpload()
	{
		$this->_assertPostOnly();
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$coverModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Cover');
		if (!Nobita_Teams_Container::getModel('Nobita_Teams_Model_Cover')->canUploadCover($team, $category, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}

		$coverPhoto = XenForo_Upload::getUploadedFile('coverPhoto');
		$deleteCoverPhoto = $this->_input->filterSingle('delete', XenForo_Input::UINT);

		// bugs: https://nobita.me/threads/409/
		$redirect = Nobita_Teams_Link::buildTeamLink('', $team);

		if ($coverPhoto)
		{
			$coverModel->doUpload($coverPhoto, $team);

			$redirect = Nobita_Teams_Link::buildTeamLink('', $team, array(
				'reposition' => 1,
				'actionuid' => XenForo_Visitor::getUserId()
			));
		}
		else if ($deleteCoverPhoto)
		{
			$coverModel->deleteCover($team['team_id']);
		}

		$newData = $this->_getTeamModel()->getTeamById($team['team_id'], array(
			'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
				| Nobita_Teams_Model_Team::FETCH_PRIVACY
				| Nobita_Teams_Model_Team::FETCH_PROFILE
		));

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirect
		);
	}

	public function actionCoverDraging()
	{
		$this->_assertPostOnly();
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$coverModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Cover');
		if (! $coverModel->canRepositionCover($team, $category, $null))
		{
			throw $this->getErrorOrNoPermissionResponseException($null);
		}

		$inputDw = $this->_input->filter(array(
			'cropX' => XenForo_Input::UINT,
			'cropY' => XenForo_Input::UINT,
			'containerW' => XenForo_Input::UINT
		));

		$cropped = $coverModel->cropCover($team, $inputDw);
		if (! $cropped)
		{
			return $this->responseError(new XenForo_Phrase('Teams_oops_something_went_wrong_try_again_later'));
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildTeamLink('', $team)
		);
	}

	public function actionReport()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if ($this->isConfirmedPost())
		{
			$reportMessage = $this->_input->filterSingle('message', XenForo_Input::STRING);
			if (!$reportMessage)
			{
				return $this->responseError(new XenForo_Phrase('please_enter_reason_for_reporting_this_message'));
			}

			$this->assertNotFlooding('report');

			$report['team'] = $team;
			$report['category'] = $category;

			/* @var $reportModel XenForo_Model_Report */
			$reportModel = Nobita_Teams_Container::getModel('XenForo_Model_Report');
			$reportModel->reportContent('team', $report, $reportMessage);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('', $team),
				new XenForo_Phrase('thank_you_for_reporting_this_message')
			);
		}
		else
		{
			$viewParams = array(
				'team' => $team,
				'category' => $category,
				'categoryBreadcrumbs' => $this->_getCategoryModel()->getCategoryBreadcrumb($category)
			);

			return $this->_getTeamViewWrapper('information', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Team_Report', 'Team_report', $viewParams)
			);
		}
	}

	public function actionDelete()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$hardDelete = $this->_input->filterSingle('hard_delete', XenForo_Input::UINT);
		$deleteType = ($hardDelete ? 'hard' : 'soft');

		if (!$this->_getTeamModel()->canDeleteTeam($team, $category, $deleteType, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}

		$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
			$dw->setExistingData($team['team_id']);

			if ($hardDelete)
			{
				$dw->delete();
				XenForo_Model_Log::logModeratorAction('team', $team, 'delete_hard');
			}
			else
			{
				$reason = $this->_input->filterSingle('reason', XenForo_Input::STRING);

				$dw->setExtraData(Nobita_Teams_DataWriter_Team::DATA_DELETE_REASON, $reason);
				$dw->set('team_state', 'deleted');
				$dw->save();

				if (XenForo_Visitor::getUserId() != $team['user_id'])
				{
					XenForo_Model_Log::logModeratorAction('team', $team, 'delete_soft', array('reason' => $reason));
				}
			}

			if (empty($redirect))
			{
				$redirect = Nobita_Teams_Link::buildTeamLink('categories', $category);
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect
			);
		}
		else
		{
			$viewParams = array(
				'team' => $team,
				'category' => $category,
				'canHardDelete' => $this->_getTeamModel()->canDeleteTeam($team, $category, 'hard'),
				'categoryBreadcrumbs' => $this->_getCategoryModel()->getCategoryBreadcrumb($category),
				'redirect' => $redirect
			);

			return $this->_getTeamViewWrapper('information', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Team_Delete', 'Team_delete', $viewParams)
			);
		}
	}

	public function actionUndelete()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamModel()->canUndeleteTeam($team, $category, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}

		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
		$dw->setExistingData($team['team_id']);
		$dw->set('team_state', 'visible');
		$dw->save();

		$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);
		if (empty($redirect))
		{
			$redirect = Nobita_Teams_Link::buildTeamLink('', $team);
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirect
		);
	}

	public function actionMembers()
	{
		return $this->responseReroute('Nobita_Teams_ControllerPublic_Member', 'index');
	}

	public function actionEvents()
	{
		return $this->responseReroute('Nobita_Teams_ControllerPublic_Event', 'index');
	}

	public function actionNotifications()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!XenForo_Visitor::getUserId())
		{
			throw $this->getNoPermissionResponseException();
		}

		$memberModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');
		$member = $memberModel->getRecordByKeys(XenForo_Visitor::getUserId(), $team['team_id']);

		if (!$member)
		{
			// invalid member!
			throw $this->getNoPermissionResponseException();
		}

		if ($this->isConfirmedPost())
		{
			$notify = $this->_input->filter(array(
				'send_alert' => XenForo_Input::BOOLEAN,
				'send_email' => XenForo_Input::BOOLEAN
			));

			$alert = true;
			if (!$notify['send_alert'] && !$notify['send_email'])
			{
				$alert = false;
			}

			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Member');
			$dw->setExistingData($member);

			$dw->set('alert', $alert);
			$dw->set('send_alert', $notify['send_alert']);
			$dw->set('send_email', $notify['send_email']);
			$dw->save();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('', $team)
			);
		}
		else
		{
			return $this->_getTeamHelper()->getTeamViewWrapper('members', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Member_Notification', 'Team_member_notification', array(
					'team' => $team,
					'category' => $category,

					'member' => $member
				))
			);
		}
	}


	public function actionLogo()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getLogoModel()->canUploadLogo($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$viewParams = array(
			'team' => $team,
			'category' => $category
		);

		return $this->_getTeamHelper()->getTeamViewWrapper('information', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Team_Avatar', 'Team_avatar', $viewParams)
		);
	}

	public function actionLogoUpload()
	{
		$this->_assertPostOnly();
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getLogoModel()->canUploadLogo($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$avatar = XenForo_Upload::getUploadedFile('logo');

		/* @var Nobita_Teams_Model_Logo */
		$avatarModel = $this->_getLogoModel();

		$inputData = $this->_input->filter(array(
			'delete' => XenForo_Input::BOOLEAN,
			'x' => XenForo_Input::UINT,
			'y' => XenForo_Input::UINT,
			'h' => XenForo_Input::UINT,
			'w' => XenForo_Input::UINT,
			'team_avatar_date' => XenForo_Input::UINT
		));

		if ($avatar)
		{
			$success = $avatarModel->uploadLogo($avatar, $team['team_id']);
			if ($success)
			{
				$team['team_avatar_date'] = $success;
			}
		}
		elseif ($inputData['delete'])
		{
			$success = $avatarModel->deleteLogo($team['team_id']);
			if ($success)
			{
				$team['team_avatar_date'] = 0;
			}
		}

		if (empty($team['team_avatar_date']))
		{
			// just delete avatar
			$message = new XenForo_Phrase('Teams_deleted_successfully');
			$redirect = '';
		}
		else
		{
			$message = new XenForo_Phrase('upload_completed_successfully');
			$redirect = Nobita_Teams_Link::buildTeamLink('', $team);
		}

		if ($this->_noRedirect())
		{
			return $this->responseView('Nobita_Teams_ViewPublic_Team_AvatarUpload', 'Team_avatar_upload',
				array(
					'team' => $team,
					'category' => $category,

					'team_avatar_date' => $team['team_avatar_date'],
					'message' => $message,
					'redirectUri' => $redirect
				)
			);
		}
		else
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('', $team),
				$message
			);
		}
	}

	protected function _getLogChanges(XenForo_DataWriter $dw)
	{
		$newData = $dw->getMergedNewData();
		$oldData = $dw->getMergedExistingData();
		$changes = array();

		foreach ($newData AS $key => $newValue)
		{
			if (isset($oldData[$key]))
			{
				$changes[$key] = $oldData[$key];
			}
		}

		return $changes;
	}

	public function actionSearch()
	{
		$this->_assertPostOnly();

		$query = $this->_input->filterSingle('q', XenForo_Input::STRING, array('noTrim' => true));
		if($query !== '' && utf8_strlen($query) > 2)
		{

			$teams = $this->_getTeamModel()->getTeams(array(
				'title' => $query,
				'moderated' => $this->_input->filterSingle('moderated', XenForo_Input::BOOLEAN),
				'deleted' => $this->_input->filterSingle('deleted', XenForo_Input::BOOLEAN),
			), array(
				'limit' => 15
			));

			$teams = $this->_getTeamModel()->filterUnviewableTeams($teams);
		}
		else
		{
			$teams = array();
		}

		return $this->responseView('Nobita_Teams_ViewPublic_Team_Search', 'Team_search_autocomplete', array(
			'teams' => $teams,
		));
	}

	public function actionMassAlert()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamModel()->canSendMassAlerts($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$this->assertNotFlooding('team_mass_alert', 3600); // 1 hour

		if ($this->_request->isPost())
		{
			$message = $this->_input->filterSingle('message', XenForo_Input::STRING);
			$messLength = Nobita_Teams_Option::get('massMessageLength');

			if (utf8_strlen($message) > intval($messLength))
			{
				return $this->responseError(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters', array('count' => 25)));
			}

			$this->_getTeamModel()->massAlert($team, $message);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('', $team)
			);
		}
		else
		{
			return $this->_getTeamHelper()->getTeamViewWrapper('information', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Team_MassAlert', 'Team_mass_alert', array(
					'team' => $team,
					'category' => $category
				))
			);
		}
	}

}
