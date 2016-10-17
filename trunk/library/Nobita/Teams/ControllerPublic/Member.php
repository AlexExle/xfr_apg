<?php

class Nobita_Teams_ControllerPublic_Member extends Nobita_Teams_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		if (!$this->_getTeamModel()->canViewTabAndContainer('members', $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$order = $this->_input->filterSingle('order', XenForo_Input::STRING);
		$username = $this->_input->filterSingle('user', XenForo_Input::STRING);

		$lastSeen = $this->_input->filterSingle('last_seen', XenForo_Input::STRING);
		$extraCondition = null;

		if (!empty($lastSeen))
		{
			if (! in_array($lastSeen, array_keys(Nobita_Teams_Helper_Member::$lastSeenFilterable)))
			{
				// invalid parameters
				throw $this->getErrorOrNoPermissionResponseException('requested_page_not_found');
			}

			$time = XenForo_Application::$time;
			$lastSeenTime = Nobita_Teams_Helper_Member::$lastSeenFilterable[$lastSeen];

			switch($lastSeen)
			{
				case '1_month':
				case '3_months':
				case '6_months':
					$extraCondition = array(
						'last_seen_date_bw' => array($time - 2*$lastSeenTime, $time - $lastSeenTime)
					);
					break;
				case '1_year':
					$extraCondition = array(
						'last_seen_date_lt' => $time - $lastSeenTime
					);
					break;
			}
			$order = 'lastSeenGroup';
		}

		if (empty($order))
		{
			$order = empty($username) ? 'all' : 'all';
		}

		$memberModel = $this->_getMemberModel();
		$banningModel = $this->_getBanningModel();
		$memberRoleModel = $this->_getMemberRoleModel();

		$perPage = 20;
		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));

		$conditions = array();
		$fetchOptions = array();

		$memberRecord = $memberModel->getTeamMemberRecord($team['team_id']);

		if ($order == 'blocked')
		{
			if (!$banningModel->canViewBannedUsers($team, $category, $error))
			{
				throw $this->getErrorOrNoPermissionResponseException($error);
			}
		}
		elseif ($order == 'requests')
		{
			if(empty($memberRecord))
			{
				throw $this->getNoPermissionResponseException();
			}

			if (!$memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'acceptDenyMember'))
			{
				throw $this->getNoPermissionResponseException();
			}
		}
		elseif ($order == 'lastSeenGroup')
		{
			if (!$memberModel->isTeamAdmin($team['team_id']))
			{
				throw $this->getNoPermissionResponseException();
			}
		}

		if (empty($username))
		{
			list($members, $memberCount) = Nobita_Teams_Helper_Member::getMembersBaseType(
				$order, $team['team_id'], $page, $perPage, $extraCondition
			);
			$viewName = 'Nobita_Teams_ViewPublic_Team_Member';
		}
		else
		{
			list($members, $memberCount) = Nobita_Teams_Helper_Member::getMembersBaseSimilarUsername($username, $team['team_id']);
			$viewName = 'XenForo_ViewPublic_Member_Find';
		}

		// reset order tab. by the way the tab filter did not existing
		// so simple to using the an existing tab
		if ($order == 'lastSeenGroup')
		{
			$order = 'all';
			$team['member_count'] = $memberCount;
		}

		$pageRoute = TEAM_ROUTE_PREFIX . '/members';
		$pageParams = array(
			'order' => $order
		);

		$this->canonicalizePageNumber($page, $perPage, $memberCount, $pageRoute);
		if ($order != 'blocked')
		{
			foreach ($members as &$user)
			{
				$user = $memberModel->prepareMember($user, $team);
			}
		}

		$tabsFilter = array(
			'all' => array(
				'order' => 'all',
				'title' => new XenForo_Phrase('Teams_all_members'),
				'count' => $team['member_count']
			),
			'admins' => array(
				'order' => 'admins',
				'title' =>  new XenForo_Phrase('Teams_staff_members')
			),
			'activities' => array(
				'order' => 'activities',
				'title' => new XenForo_Phrase('Teams_most_active')
			),
			'alphabetical' => array(
				'order' => 'alphabetical',
				'title' => new XenForo_Phrase('Teams_members_by_name')
			),
			'date' => array(
				'order' => 'date',
				'title' => new XenForo_Phrase('Teams_members_by_join_date')
			)
		);

		$canViewRequest = false;
		if($memberRecord && $memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'acceptDenyMember'))
		{
			$canViewRequest = true;
		}

		if ($canViewRequest)
		{
			$tabsFilter['requests'] = array(
				'order' => 'requests',
				'title' => new XenForo_Phrase('Teams_awaiting_approval'),
				'count' => $team['member_request_count']
			);

			$tabsFilter['invited'] = array(
				'order' => 'invited',
				'title' => new XenForo_Phrase('Teams_invited_people'),
				'count' => $team['invite_count']
			);
		}

		$canEditBanned = $banningModel->canBanUser(null, $team);
		if ($canEditBanned)
		{
			$tabsFilter['blocked'] = array(
				'order' => 'blocked',
				'title' => new XenForo_Phrase('banned_users')
			);
		}

		$viewParams = array(
			'team' => $team,

			'tabsFilter' => $tabsFilter,

			'members' => $members,
			'page' => $page,
			'perPage' => $perPage,
			'totalMembers' => $memberCount,
			'pageRoute' => $pageRoute,
			'pageParams' => $pageParams,
			'username' => $username,

			'order' => $order,
			'disableAdminQuery' => true,
			'canEditBanned' => $canEditBanned,
			'canViewRequest' => $canViewRequest,
			'canInvitePeople' => $memberModel->canInvitePeople($team),
			'canFilterLastViewDate' => $memberModel->isTeamAdmin($team['team_id']),
			'canAddPeople' => $memberModel->canAddPeople($team)
		);

		return $this->_getTeamViewWrapper('members', $team, $category,
			$this->responseView($viewName, 'Team_member', $viewParams)
		);
	}

	public function actionRoles()
	{
		$this->_assertRegistrationRequired();

		$memberRoleId = $this->_input->filterSingle('member_role_id', XenForo_Input::STRING);

		$memberRoleModel = $this->_getMemberRoleModel();
		$memberRoles = $memberRoleModel->getAllMemberRoles();

		$defaultMemberRoles = $memberRoleModel->getBasicMemberRoles();

		$selectedMemberRole = array();
		if($memberRoleId)
		{
			if(!isset($memberRoles[$memberRoleId]))
			{
				return $this->responseError(new XenForo_Phrase('Teams_requested_member_role_not_found'), 404);
			}

			$memberRole = $memberRoles[$memberRoleId];

			$selectedMemberRole = $memberRole;
			$selectedMemberRole['roleValues'] = $defaultMemberRoles;

			foreach($selectedMemberRole['roleValues'] as $group => &$roleList)
			{
				$selectedRole = isset($memberRole['roles'][$group]) ? $memberRole['roles'][$group] : array();
				foreach($roleList as $roleId => &$role)
				{
					if(!empty($selectedRole[$roleId]))
					{
						$role['valueLabel'] = new XenForo_Phrase('Teams_yes');
					}
					else
					{
						$role['valueLabel'] = new XenForo_Phrase('Teams_no');
					}
				}
			}
		}

		$viewParams = array(
			'memberRoles' => $memberRoles,
			'memberRoleGrouped' => $memberRoleModel->getMemberRoleGrouped(),
			'selectedMemberRole' => $selectedMemberRole,
		);

		return $this->responseView('Nobita_Teams_ViewPublic_Member_RoleList', 'Team_member_role_description', $viewParams);
	}

	public function actionInvite()
	{
		$this->_assertPostOnly();

		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		$teamId = $team['team_id'];

		$this->_assertCanViewMemberTab($team, $category);

		$memberModel = $this->_getMemberModel();
		if (!$memberModel->canInvitePeople($team, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$username = $this->_input->filterSingle('username', XenForo_Input::STRING);
		$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserByName($username, array(
			'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
		));

		if (!$user)
		{
			return $this->responseError(new XenForo_Phrase('requested_member_not_found'), 404);
		}
		$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);

		$existing = $memberModel->getRecordByKeys($user['user_id'], $team['team_id']);
		if ($existing)
		{
			return $this->responseError(new XenForo_Phrase('Teams_user_already_joined_in_team'));
		}

		$this->_checkUserJoinableGroup($user, false);

		$visitor = XenForo_Visitor::getInstance();
		$actionUser = array(
			'action' => 'invite',
			'action_user_id' => $visitor['user_id'],
			'action_username' => $visitor['username']
		);
		$memberModel->insertMember(
			$user['user_id'], $teamId,
			'member', 'request',
			$actionUser
		);

		$hash = '#member-' . $teamId . '-' . $user['user_id'];
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$this->getDynamicRedirect(),
			new XenForo_Phrase('Teams_you_sent_an_invite_to_x', array('user' => $user['username']))
		);
	}

	public function actionAdd()
	{
		$this->_assertPostOnly();

		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		$teamId = $team['team_id'];

		$this->_assertCanViewMemberTab($team, $category);

		$memberModel = $this->_getMemberModel();
		if (!$memberModel->canAddPeople($team, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$username = $this->_input->filterSingle('username', XenForo_Input::STRING);
		$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserByName($username, array(
			'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
		));

		if (!$user)
		{
			return $this->responseError(new XenForo_Phrase('requested_member_not_found'), 404);
		}
		$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);

		$existing = $memberModel->getRecordByKeys($user['user_id'], $team['team_id']);
		if ($existing)
		{
			return $this->responseError(new XenForo_Phrase('Teams_user_already_joined_in_team'));
		}

		$this->_checkUserJoinableGroup($user, false);

		$visitor = XenForo_Visitor::getInstance();

		$memberModel->insertMember(
			$user['user_id'], $teamId,
			'member', 'accept',
			array(
				'action' => 'add',
				'action_user_id' => $visitor['user_id'],
				'action_username' => $visitor['username']
			)
		);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$this->getDynamicRedirect()
		);
	}

	protected function _checkUserJoinableGroup(array $user, $selfMessage = false)
	{
		if (!isset($user['permissions']))
		{
			throw new XenForo_Exception('No permission key found', true);
		}

		$maxJoin = Nobita_Teams_Option::get('maxGroupsJoin');
		$bypass = XenForo_Permission::hasPermission($user['permissions'], 'Teams', 'maxGroupsJoin_bypass');

		$joinedCount = $this->_getMemberModel()->countAllTeamsForUser($user['user_id']);

		if (!$maxJoin && !$bypass)
		{
			$phraseKey = $selfMessage ? 'Teams_you_can_not_join_any_groups' : 'Teams_x_can_not_join_any_groups';
			throw $this->responseException(
				$this->responseError(new XenForo_Phrase($phraseKey, array('user' => $user['username'])))
			);
		}

		if ($joinedCount >= $maxJoin && !$bypass)
		{
			$phraseKey = $selfMessage ? 'Teams_you_only_join_x_teams' : 'Teams_x_only_join_y_groups';
			throw $this->responseException(
				$this->responseError(new XenForo_Phrase($phraseKey, array(
					'max' => XenForo_Locale::numberFormat($maxJoin),
					'user' => $user['username'],
					'joined_count' => XenForo_Locale::numberFormat($joinedCount)
				)))
			);
		}
	}

	public function actionJoin()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		if (!$this->_getMemberModel()->canAsktoJoin($team, $category, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}
		$this->_request->setParam('team_id', $team['team_id']);

		$visitor = XenForo_Visitor::getInstance();

		if (empty($team['always_req_message']))
		{
			// Did Not Require Reg Message
			$this->_checkUserJoinableGroup($visitor->toArray(), true);

			$member = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->insertMember(
				$visitor['user_id'], $team['team_id'],
				'member', ($team['always_moderate_join']) ? "request" : "accept",
				array(), array(), null,
				''
			);

			$message = '';
			if (is_array($member) && $member['member_state'] == 'request')
			{
				$message = new XenForo_Phrase('Teams_request_waiting_approval');
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('', $team),
				$message
			);
		}

		if ($this->isConfirmedPost())
		{
			$reqMessage = $this->_input->filterSingle('req_message', XenForo_Input::STRING);
			if ($team['always_req_message'] && strlen(trim($reqMessage)) == 0)
			{
				return $this->responseError(new XenForo_Phrase('Teams_please_provide_brief_message'));
			}

			$this->_checkUserJoinableGroup($visitor->toArray(), true);

			$agree = $this->_input->filterSingle('agree', XenForo_Input::BOOLEAN);
			if (empty($agree))
			{
				return $this->responseError(new XenForo_Phrase('Teams_please_agree_the_group_rules'));
			}

			$member = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->insertMember(
				$visitor['user_id'], $team['team_id'],
				'member', ($team['always_moderate_join']) ? "request" : "accept",
				array(), array(), null,
				$reqMessage
			);

			$message = '';
			if (is_array($member) && $member['member_state'] == 'request')
			{
				$message = new XenForo_Phrase('Teams_request_waiting_approval');
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('', $team),
				$message
			);
		}
		else
		{
			$viewParams = array(
				'team' => $team,
				'category' => $category,
				'rulesLink' => Nobita_Teams_Link::buildTeamLink('rules', $team)
			);

			return $this->_getTeamViewWrapper('members', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Member_RegJoin', 'Team_member_reg_join', $viewParams)
			);
		}
	}

	public function actionAcceptInvite()
	{
		$this->_assertRegistrationRequired();

		list ($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		$this->_request->setParam('team_id', $team['team_id']);

		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));
		$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);

		if (empty($redirect))
		{
			if ($this->_getTeamModel()->canViewTeam($team, $category))
			{
				$redirect = Nobita_Teams_Link::buildTeamLink('', $team);
			}
			else
			{
				$redirect = Nobita_Teams_Link::buildTeamLink();
			}
		}

		$member = $this->_getMemberModel()->getRecordByKeys(XenForo_Visitor::getUserId(), $team['team_id']);
		if (empty($member))
		{
			return $this->responseError(new XenForo_Phrase('requested_member_not_found'));
		}

		if ($member['action'] != 'invite')
		{
			return $this->responseError(new XenForo_Phrase('Teams_the_invitation_not_valid'));
		}

		$memberDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Member');
		$memberDw->setExistingData($member);

		$memberDw->set('action', 'add');
		$memberDw->set('member_state', 'accept');

		$memberDw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirect
		);
	}

	public function actionLeave()
	{
		list ($userId, $team, $category) = $this->_getTeamHelper()->assertMemberValidAndViewable();
		$this->_request->setParam('team_id', $team['team_id']);

		if ($userId != XenForo_Visitor::getUserId())
		{
			throw $this->getNoPermissionResponseException();
		}

		$memberModel = $this->_getMemberModel();
		$member = $memberModel->getRecordByKeys($userId, $team['team_id']);

		if (!$member)
		{
			throw $this->getNoPermissionResponseException();
		}

		$member = $memberModel->prepareMember($member, $team);

		$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);
		if ($this->isConfirmedPost())
		{
			$memberModel->remove($member);

			if (empty($redirect))
			{
				if ($this->_getTeamModel()->canViewTeam($team, $category))
				{
					$redirect = Nobita_Teams_Link::buildTeamLink('', $team);
				}
				else
				{
					$redirect = Nobita_Teams_Link::buildTeamLink('');
				}
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect
			);
		}
		else
		{
			$viewParams = array(
				'redirect' => $redirect,
				'member' => $member,
				'team' => $team
			);
			return $this->_getTeamViewWrapper('members', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Member_Leave', 'Team_member_leave', $viewParams)
			);
		}
	}

	public function actionSuggestion()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$this->_assertCanViewMemberTab($team, $category);

		// hidden the token on URL...
		// im intelligent now :D
		$visitor = XenForo_Visitor::getInstance();
		$this->_request->setParam('_xfToken', $visitor['csrf_token_page']);

		$q = ltrim($this->_input->filterSingle('q', XenForo_Input::STRING, array('noTrim' => true)));

		$memberModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');
		if ($q !== '' && utf8_strlen($q) >= 2)
		{
			$users = $memberModel->getMembersByTeamId($team['team_id'], array(
				'username' => array($q , 'r'),
			), array(
				'limit' => 20,

				// Fixed the bug which did not show the avatar
				'join' => Nobita_Teams_Model_Member::FETCH_USER
			));
		}
		else
		{
			$users = array();
		}

		return $this->responseView(
			'XenForo_ViewPublic_Member_Find',
			'member_autocomplete',
			array('users' => $users)
		);
	}

	public function actionRequest()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));
		list($userId, $team, $category) = $this->_getTeamHelper()->assertMemberValidAndViewable();

		$this->_assertCanViewMemberTab($team, $category);

		$memberModel = $this->_getMemberModel();
		$memberRoleModel = $this->_getMemberRoleModel();

		$memberRecord = $memberModel->getTeamMemberRecord($team['team_id']);
		if(empty($memberRecord))
		{
			throw $this->getNoPermissionResponseException();
		}

		if (!$memberRoleModel->hasModeratorPermission($memberRecord['member_role_id'], 'acceptDenyMember'))
		{
			throw $this->getNoPermissionResponseException();
		}

		$user = $memberModel->getRecordByKeys($userId, $team['team_id']);
		if (!$user)
		{
			return $this->responseError(new XenForo_Phrase('requested_member_not_found'), 404);
		}

		if ($user['action'] == 'invite' && $user['member_state'] == 'request')
		{
			// yay.. hack?
			throw $this->getNoPermissionResponseException();
		}

		$action = $this->_input->filterSingle('req', XenForo_Input::STRING);
		if ($action == 'accept')
		{
			if ($user['member_state'] == "accept")
			{
				return $this->responseError(new XenForo_Phrase('Teams_user_already_joined_in_team'));
			}

			$memberModel->promote(
				$user, 'member',
				'approval', array('alert' => 1, 'member_state' => 'accept')
			);
		}
		elseif ($action == 'cancel')
		{
			$memberModel->remove($user);
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildTeamLink('members', $team, array('order' => 'requests'))
		);
	}

	public function actionPromote()
	{
		list ($userId, $team, $category) = $this->_getTeamHelper()->assertMemberValidAndViewable();

		$this->_assertCanViewMemberTab($team, $category);

		if (!$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserById($userId))
		{
			return $this->responseError(new XenForo_Phrase('requested_user_not_found'), 404);
		}

		$memberModel = $this->_getMemberModel();
		$record = $memberModel->getRecordByKeys($user['user_id'], $team['team_id']);

		if (!$record)
		{
			return $this->responseError(new XenForo_Phrase('requested_member_not_found'), 404);
		}

		if (!$memberModel->canPromoteMember($record, $team, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		if ($this->_request->isPost())
		{
			if ($record['member_state'] != 'accept')
			{
				return $this->responseError(new XenForo_Phrase('Teams_can_not_promote_awaiting_member', array(
					'member_name' => $record['username']
				)));
			}

			$position = $this->_input->filterSingle('position', XenForo_Input::STRING);

			$memberModel->promote($record, $position, 'promote');
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('members', $team)
			);
		}
		else
		{
			return $this->_getTeamViewWrapper('members', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Member_Promote', 'Team_member_promote', array(
					'team' => $team,
					'user' => $user,
					'record' => $record,
					'permsCache' => XenForo_Application::getSimpleCacheData(TEAM_DATAREGISTRY_KEY)
				))
			);
		}
	}

	public function actionRemove()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));

		list ($userId, $team, $category) = $this->_getTeamHelper()->assertMemberValidAndViewable();

		$this->_assertCanViewMemberTab($team, $category);

		if (!$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserById($userId))
		{
			return $this->responseError(new XenForo_Phrase('requested_user_not_found'), 404);
		}

		$memberModel = $this->_getMemberModel();
		$record = $memberModel->getRecordByKeys($user['user_id'], $team['team_id']);
		if (!$record)
		{
			return $this->responseError(new XenForo_Phrase('requested_member_not_found'), 404);
		}

		if (!$memberModel->canRemoveMember($record, $team, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$memberModel->remove($record);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildTeamLink('members', $team)
		);
	}

	protected function _assertCanViewMemberTab(array $team, array $category)
	{
		if (!$this->_getTeamModel()->canViewTabAndContainer('members', $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$this->_request->setParam('team_id', $team['team_id']);
	}
}
