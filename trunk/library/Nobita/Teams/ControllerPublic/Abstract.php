<?php

abstract class Nobita_Teams_ControllerPublic_Abstract extends XenForo_ControllerPublic_Abstract
{
	protected function _preDispatch($action)
	{
		if (XenForo_Application::isRegistered('addOns'))
		{
			$addOns = XenForo_Application::get('addOns');
			if (!empty($addOns['nobita_Teams']) && $addOns['nobita_Teams'] < 2050512)
			{
				$response = $this->responseMessage(new XenForo_Phrase('board_currently_being_upgraded'));
				throw $this->responseException($response, 503);
			}
		}

		if (!$this->_getTeamModel()->canViewTeams($error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}

	protected function _postDispatch($controllerResponse, $controllerName, $action)
	{
		if (isset($controllerResponse->params['category']))
		{
			$controllerResponse->containerParams['teamCategory'] = $controllerResponse->params['category'];
		}

		$team = false;
		if (isset($controllerResponse->params['team']))
		{
			$team = $controllerResponse->params['team'];
			$controllerResponse->containerParams['team'] = $controllerResponse->params['team'];
		}

		$counterControllers = array(
			'Nobita_Teams_ControllerPublic_Team',
			'Nobita_Teams_ControllerPublic_Event'
		);

		$counterActions = array(
			'index', 'view', 'discussions', 'rules', 'photos', 'members', 'extra', 'photos'
		);
		if ($controllerResponse instanceof XenForo_ControllerResponse_View
			&& in_array($controllerName, $counterControllers)
			&& in_array(strtolower($action), $counterActions)
			&& $team
		)
		{
			$this->_updateCounter($team['team_id']);
		}

		return parent::_postDispatch($controllerResponse, $controllerName, $action);
	}

	public function _getTeamViewWrapper($selectedTab, array $team, array $category,
		XenForo_ControllerResponse_View $subView)
	{
		return $this->_getTeamHelper()->getTeamViewWrapper($selectedTab, $team, $category, $subView);
	}

	protected function _getTeamIndexWrapper($selectedTab, XenForo_ControllerResponse_View $subView)
	{
		$subParams =& $subView->params;
		$subParams['isMobile'] = XenForo_Visitor::getInstance()->isBrowsingWith('mobile');

		$viewParams = array(
			'selectedTab' => $selectedTab,
			'subViewName' => $subView->templateName
		);
		$viewParams = array_merge_recursive($viewParams, $subParams);
		$viewParams = array_merge($viewParams, $this->_getRecentAndTrendingThreadsWidget());

		$openCategoryLevelIds = XenForo_Helper_Cookie::getCookie('group_collaseLevelIds');
		$openCategoryLevelIds = array_map('intval', explode(',', $openCategoryLevelIds));

		$noSidebar = false;
		if (!empty($subParams['statsCriteria']))
		{
			$criteria = $subParams['statsCriteria'];
			$fetchOptions = $this->_getTeamListFetchOptions();

			$viewParams['mostMembers'] = Nobita_Teams_Helper_Widget::getMostGroupsWidgetByType(
				Nobita_Teams_Helper_Widget::MOST_MEMBERS, $criteria
			);

			$noSidebar = empty($viewParams['mostMembers']);
		}

		$noSidebar = empty($viewParams['disableBrowseCategory']) ? false : $noSidebar;

		$response = $this->responseView('Nobita_Teams_ViewPublic_Home_IndexWrapper', 'Team_index_wrapper', $viewParams);
		$response->subView = $subView;

		return $response;
	}

	protected function _getRecentAndTrendingThreadsWidget()
	{
		$dataRegistryModel = Nobita_Teams_Container::getModel('XenForo_Model_DataRegistry');
		$data = $dataRegistryModel->get(Nobita_Teams_Listener::DATA_REG_THREADS);

		$nodePermissions = Nobita_Teams_Container::getModel('XenForo_Model_Node')->getNodePermissionsForPermissionCombination();

		$recentThreads = array();
		$trendingThreads = array();

		$limit = array(
			'recent' => Nobita_Teams_Option::get('recentThreadsLimit'),
			'trending' => Nobita_Teams_Option::get('trendingThreadsLimit')
		);

		if(isset($data['recent']) && is_array($data['recent']))
		{
			$recentThreads = $this->_filterUnviewableThreads($data['recent'], $nodePermissions);
			$recentThreads = array_slice($recentThreads, 0, $limit['recent']);
		}

		if(isset($data['trending']) && is_array($data['trending']))
		{
			$trendingThreads = $this->_filterUnviewableThreads($data['trending'], $nodePermissions);
			$trendingThreads = array_slice($trendingThreads, 0, $limit['trending']);
		}

		return array(
			'recent' => $recentThreads,
			'trending' => $trendingThreads
		);
	}

	protected function _filterUnviewableThreads(array $threads, array $nodePermissions)
	{
		$threadModel = Nobita_Teams_Container::getModel('XenForo_Model_Thread');

		foreach($threads as $threadId => &$thread)
		{
			$nodePermission = isset($nodePermissions[$thread['node_id']]) ? $nodePermissions[$thread['node_id']] : false;
			if (! $nodePermission)
			{
				unset($threads[$threadId]);
				continue;
			}

			if (!$threadModel->canViewThread($thread, $thread, $null, $nodePermission))
			{
				unset($threads[$threadId]);
				continue;
			}
		}
		unset($thread);

		return $threads;
	}

	protected function _getPostsAndPrepare(array $conditions, array $fetchOptions, array $team, array $category)
	{
		$postModel = $this->_getPostModel();
		$commentModel = $this->_getCommentModel();

		$posts = $postModel->getPostsForTeamId($team['team_id'], $conditions, $fetchOptions);
		$posts = $postModel->getAndMergeAttachmentsIntoPosts($posts);

		foreach ($posts as &$post)
		{
			$post = $postModel->preparePost($post, $team, $category);
		}

		// normal posts.
		foreach ($posts as $postId => &$post)
		{
			if (!$postModel->canViewPostAndContainer($post, $team, $category, $error))
			{
				unset($posts[$postId]);
			}
		}
		unset($post);

		$posts = $postModel->addCommentsToPosts($posts);

		foreach ($posts as &$post)
		{
			if (empty($post['comments']))
			{
				continue;
			}

			foreach ($post['comments'] as &$comment)
			{
				$comment = $commentModel->prepareComment($comment, $post, $team);
			}
		}

		return $posts;
	}

	protected function _updateCounter($teamId)
	{
		$this->_getTeamModel()->logTeamView($teamId);
	}

	public function getPostSpecificRedirect(array $post, array $team,
		$redirectType = XenForo_ControllerResponse_Redirect::SUCCESS, array $redirectParams = array()
	)
	{
		return $this->responseRedirect($redirectType, Nobita_Teams_Link::buildTeamLink('posts', $post), null, $redirectParams);
	}

	protected function _getTeamListFetchOptions()
	{
		$fetchOptions = array();

		$fetchOptions['join'] = Nobita_Teams_Model_Team::FETCH_PROFILE
				| Nobita_Teams_Model_Team::FETCH_PRIVACY
				| Nobita_Teams_Model_Team::FETCH_CATEGORY
				| Nobita_Teams_Model_Team::FETCH_USER
				| Nobita_Teams_Model_Team::FETCH_FEATURED;

		$visitor = XenForo_Visitor::getInstance();

		$fetchOptions['banUserId'] = $visitor['user_id'];
		$fetchOptions['memberUserId'] = $visitor['user_id'];

		return $fetchOptions;
	}

	public static function getSessionActivityDetailsForList(array $activities)
	{
		$activityHelper = new Nobita_Teams_Helper_Activity($activities);
		return $activityHelper->output();
	}

	protected function _getTeamHelper()
	{
		return $this->getHelper('Nobita_Teams_ControllerHelper_Team');
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}

	protected function _getBanningModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Banning');
	}

	protected function _getCategoryModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category');
	}

	protected function _getCategoryWatchModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_CategoryWatch');
	}

	protected function _getFieldModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Field');
	}

	protected function _getLogoModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Logo');
	}

	protected function _getCoverModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Cover');
	}

	protected function _getMemberModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');
	}

	protected function _getMemberRoleModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
	}

	protected function _getPostModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
	}

	protected function _getCommentModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');
	}

	protected function _getEventModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
	}

	protected function _getNewsFeedModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_NewsFeed');
	}
}
