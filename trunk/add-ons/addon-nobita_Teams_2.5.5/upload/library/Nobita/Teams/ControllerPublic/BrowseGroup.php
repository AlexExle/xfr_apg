<?php

class Nobita_Teams_ControllerPublic_BrowseGroup extends Nobita_Teams_ControllerPublic_Abstract
{
	protected $_criteria 	= array();
	protected $_userCache 	= array();

	protected $_browseCategory 	= array('admin', 'user', 'invited', 'pending');

	protected $_routeLink;

	public function getRouteLink()
	{
		return Nobita_Teams_Option::get('routePrefix').'/browsegroups';
	}

	public function setBrowseCategory($category)
	{
		$this->_browseCategory[] = $category;
	}

	public function actionIndex()
	{
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
			Nobita_Teams_Link::buildTeamLink('browsegroups/membership')
		);
	}

	public function actionUser()
	{
		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

		$categoryModel = $this->_getCategoryModel();

		$viewableCategories = $this->_getCategoryModel()->getViewableCategories();

		$this->_criteria['team_category_id'] = array_keys($viewableCategories);
		$this->_criteria['team_state'] = 'visible';

		$admins = $this->_getMembershipCategoryAdminResponse($userId, 5, true);
		$members = $this->_getMembershipCategoryMemberResponse($userId, 5, true);

		$viewParams = array(
			'admins' => isset($admins['teams']) ? $admins['teams'] : array(),
			'members' => isset($members['teams']) ? $members['teams'] : array(),
			'user' => $admins['user'],
			'profile' => $this->_input->filterSingle('profile', XenForo_Input::BOOLEAN),
		);

		return $this->responseView('Nobita_Teams_ViewPublic_BrowseGroup_User', 'Team_browsegroup_user', $viewParams);
	}

	public function actionMembership()
	{
		$category = $this->_input->filterSingle('category', XenForo_Input::STRING);

		$allowedCategory = $this->_browseCategory;

		if (!empty($category) && !in_array($category, $allowedCategory))
		{
			// like invalid parameter
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
				Nobita_Teams_Link::buildTeamLink()
			);
		}

		$categoryModel = $this->_getCategoryModel();

		$viewableCategories = $this->_getCategoryModel()->getViewableCategories();

		$this->_criteria['team_category_id'] = array_keys($viewableCategories);
		$this->_criteria += $this->_getCategoryModel()->getPermissionBasedFetchConditions();

		if (in_array($category, $allowedCategory))
		{
			$method = sprintf('_getMembershipCategory%sResponse', ucfirst($category));
			if (method_exists($this, $method))
			{
				$viewParams = call_user_func_array(array($this, $method), array());
			}
		}
		else
		{
			$viewParams = $this->_getMembershipCategoryMemberResponse();
		}

		$pageNavParams = array();
		if (!isset($viewParams['order']))
		{
			$viewParams['order'] = $category ? $category : 'joined';
		}

		if (!isset($viewParams['pageNavParams']))
		{
			$viewParams['pageNavParams'] = $pageNavParams;
		}

		if (!isset($viewParams['browseNavParams']))
		{
			$viewParams['browseNavParams'] = array(
				'category' => $category
			);
		}

		$viewParams = array_merge($viewParams, array(
			'pageRoute' 		=> $this->getRouteLink() . '/membership',
			'pageLinkParam' 	=> false,
			'membershipPage' 	=> true,
			'statsCriteria' 	=> $this->_criteria,

			// we not want to show categories in this page
			'disableBrowseCategory' => true
		));

		return $this->_getTeamIndexWrapper('browsegroups/membership',
			$this->responseView('Nobita_Teams_ViewPublic_BrowseGroup_Membership', 'Team_browsegroup_membership', $viewParams)
		);
	}

	/**
	 * List all the groups which current visitor has sent an request to join
	 *
	 * @return array
	 */
	protected function _getMembershipCategoryPendingResponse()
	{
		$this->_assertRegistrationRequired();
		$userId = XenForo_Visitor::getUserId();

		$memberModel = $this->_getMemberModel();
		$teamModel = $this->_getTeamModel();

		$teamIds = $memberModel->getTeamIdsByPendingForUser($userId);
		if (empty($teamIds))
		{
			$teams = array();
		}
		else
		{
			$teams = $teamModel->getTeamsByIds($teamIds, $this->_getTeamListFetchOptions());
			$teams = Nobita_Teams_Helper_Widget::filterUnviewableAndPrepareTeams($teams);
		}

		return array(
			'teams' 			=> $teams,
			'totalTeams' 		=> count($teams),
			'noTeamsPhrase' => new XenForo_Phrase('Teams_you_did_not_sent_any_requests'),
			'pageTitle' => new XenForo_Phrase('Teams_pending_groups'),
			//'quickActionReturn' => $this->_buildLink('canonical:' . $this->getRouteLink() . '/membership', false, array('category' => 'pending'))
			'quickActionReturn' => Nobita_Teams_Link::buildTeamLink('browsegroups/membership', null, array('category' => 'pending'))
		);
	}

	/**
	 * Listed the groups invited
	 *
	 * @return array
	 */
	protected function _getMembershipCategoryInvitedResponse()
	{
		$this->_assertRegistrationRequired();
		$userId = XenForo_Visitor::getUserId();

		$memberModel = $this->_getMemberModel();
		$teamModel = $this->_getTeamModel();

		$teamIds = $memberModel->getTeamIdsByInvitedForUser($userId);

		if (empty($teamIds))
		{
			$teams = array();
		}
		else
		{
			$teams = $teamModel->getTeamsByIds($teamIds, $this->_getTeamListFetchOptions());
			$teams = Nobita_Teams_Helper_Widget::filterUnviewableAndPrepareTeams($teams);
		}

		return array(
			'teams' 			=> $teams,
			'totalTeams' 		=> count($teams),
			'noTeamsPhrase' => new XenForo_Phrase('Teams_there_are_no_invites_to_display'),
			'pageTitle' => new XenForo_Phrase('Teams_invited_groups'),
			//'quickActionReturn' => $this->_buildLink('canonical:' . $this->getRouteLink() . '/membership', false, array('category' => 'invited'))
			'quickActionReturn' => Nobita_Teams_Link::buildTeamLink('browsegroups/membership', null, array('category' => 'invited'))
		);
	}

	/**
	 *
	 * @return array
	 */
	protected function _getMembershipCategoryUserResponse()
	{
		$type = $this->_input->filterSingle('type', XenForo_Input::STRING);
		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

		$userModel = Nobita_Teams_Container::getModel('XenForo_Model_User');
		$user = $userModel->getUserById($userId);

		if (!$user)
		{
			throw $this->responseException(
				$this->responseError(new XenForo_Phrase('requested_member_not_found'))
			);
		}
		$userId = $user['user_id'];
		$this->_userCache = $user;

		if ($type == 'joined')
		{
			$pageTitle = new XenForo_Phrase('Teams_teams_xs_joined', array(
				'user' => $user['username']
			));

			$noTeamsPhrase = new XenForo_Phrase('Teams_xs_have_not_joined_any', array(
				'user' => $user['username']
			));

			$viewParams = $this->_getMembershipCategoryMemberResponse($userId);
		}
		elseif ($type == 'admin')
		{
			$pageTitle = new XenForo_Phrase('Teams_groups_xs_manage', array(
				'user' => $user['username']
			));

			$noTeamsPhrase = new XenForo_Phrase('Teams_xs_do_not_manage_any', array(
				'user' => $user['username']
			));

			$viewParams = $this->_getMembershipCategoryAdminResponse($userId);
		}
		else
		{
			throw $this->responseException(
				$this->responseError(new XenForo_Phrase('requested_page_not_found'))
			);
		}

		$pageNavParams = array(
			'category' => 'user',
			'user_id' => $userId
		);

		return array_merge($viewParams, array(
			'modifyMembershipTabTitle' => true,
			'user' => $user,

			'order' => $type,
			'noTeamsPhrase' => $noTeamsPhrase,
			'pageTitle' => $pageTitle,
			'pageNavParams' => $pageNavParams,
			'browseNavParams' => array_merge($pageNavParams, array('type' => $type))
		));
	}

	protected function _getMembershipCategoryMemberResponse($userId = null, $limit = null, $random = false)
	{
		$this->_assertRegistrationRequired();

		$memberModel = $this->_getMemberModel();
		$teamModel = $this->_getTeamModel();

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		if (is_null($limit))
		{
			$perPage = Nobita_Teams_Option::get('teamsPerPage');
		}
		else
		{
			$perPage = $limit;
		}

		if (is_null($userId))
		{
			$user = XenForo_Visitor::getInstance();
			$userId = $user['user_id'];
		}
		else
		{
			if ($this->_userCache)
			{
				$user = $this->_userCache;
				$userId = $this->_userCache['user_id'];
			}
			else
			{
				$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserById($userId);

				if (!$user)
				{
					throw $this->responseException(
						$this->responseError(new XenForo_Phrase('requested_member_not_found'))
					);
				}
				$userId = $user['user_id'];
				$this->_userCache = $user;
			}
		}

		$teamIds = $memberModel->getTeamIdsByUserId($userId, array('member_state' => 'accept'), array(
			'join' => Nobita_Teams_Model_Member::FETCH_TEAM,
			'order' => 'last_updated',
			'direction' => 'desc',
			'page' => $page,
			'perPage' => $perPage
		));

		if (empty($teamIds))
		{
			$teams 				= array();
			$inlineModOptions 	= array();
			$totalTeams 		= 0;
		}
		else
		{
			$conditions = array_merge($this->_criteria, array(
				'team_id' => $teamIds
			));

			$totalTeams = $teamModel->countTeamsYouJoined($userId, $this->_criteria);
			$this->canonicalizePageNumber($page, $perPage, $totalTeams, $this->getRouteLink() . '/membership');

			$fetchOptions = $this->_getTeamListFetchOptions();
			if ($random)
			{
				$fetchOptions['order'] = 'random';
			}
			else
			{
				$fetchOptions['order'] = 'last_updated';
				$fetchOptions['direction'] = 'desc';
			}

			$teams = $teamModel->getTeams($conditions, $fetchOptions);
			$teams = Nobita_Teams_Helper_Widget::filterUnviewableAndPrepareTeams($teams);

			$inlineModOptions = $teamModel->getInlineModOptionsForTeams($teams);
		}

		return array(
			'page' 				=> $page,
			'perPage' 			=> $perPage,
			'teams' 			=> $teams,
			'totalTeams' 		=> $totalTeams,
			'inlineModOptions' 	=> $inlineModOptions,
			'pageTitle' 		=> new XenForo_Phrase('Teams_teams_you_are_joined'),
			'noTeamsPhrase'		=> new XenForo_Phrase('Teams_you_did_not_joined_any_groups'),
			'user'				=> $user
		);
	}

	/**
	 * Fetch all groups which user has admin
	 *
	 * @param $userId
	 * @param $limit
	 * @param $random
	 *
	 * @return array|exception
	 */
	protected function _getMembershipCategoryAdminResponse($userId = null, $limit = null, $random = false)
	{
		$this->_assertRegistrationRequired();

		if (is_null($userId))
		{
			$user = XenForo_Visitor::getInstance()->toArray();
			$userId = $user['user_id'];
		}
		else
		{
			if ($this->_userCache)
			{
				$user = $this->_userCache;
				$userId = $this->_userCache['user_id'];
			}
			else
			{
				$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserById($userId);

				if (!$user)
				{
					throw $this->responseException(
						$this->responseError(new XenForo_Phrase('requested_member_not_found'))
					);
				}
				$userId = $user['user_id'];
				$this->_userCache = $user;
			}
		}

		$memberModel = $this->_getMemberModel();
		$teamModel = $this->_getTeamModel();

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));

		if (is_null($limit))
		{
			$perPage = Nobita_Teams_Option::get('teamsPerPage');
		}
		else
		{
			$perPage = $limit;
		}

		$conditions = $this->_criteria;
		$fetchOptions = array(
			'page' => $page,
			'perPage' => $perPage,
			'order' => 'last_updated',
			'direction' => 'desc',
		);

		if ($random)
		{
			$fetchOptions['order'] = 'random';
		}

		$teams = $teamModel->getTeamsYouAdmin($userId, $conditions, $fetchOptions);
		$teams = Nobita_Teams_Helper_Widget::filterUnviewableAndPrepareTeams($teams);

		$inlineModOptions = $teamModel->getInlineModOptionsForTeams($teams);
		$totalTeams = $teamModel->countTeamsYouAdmin($userId, $conditions);

		$this->canonicalizePageNumber($page, $perPage, $totalTeams, $this->getRouteLink() . '/membership', array(
			'category' => 'admin'
		));

		return array(
			'page' 				=> $page,
			'perPage' 			=> $perPage,
			'teams' 			=> $teams,
			'totalTeams' 		=> $totalTeams,
			'inlineModOptions' 	=> $inlineModOptions,
			'pageTitle' 		=> new XenForo_Phrase('Teams_groups_you_manage'),
			'noTeamsPhrase'		=> new XenForo_Phrase('Teams_you_have_no_manage_any_teams', array(
				'group_add_url' => Nobita_Teams_Link::buildTeamLink('add', null, array('ref' => 'membership')),
			)),
			'user' 				=> $user
		);
	}



}
