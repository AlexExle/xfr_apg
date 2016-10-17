<?php

class Nobita_Teams_XenForo_Model_Thread extends XFCP_Nobita_Teams_XenForo_Model_Thread
{
	public function prepareThreadConditions(array $conditions, array &$fetchOptions)
	{
		$result = parent::prepareThreadConditions($conditions, $fetchOptions);
		$sqlConditions = array($result);

		if (!empty($conditions['team_id']))
		{
			$sqlConditions[] = 'thread.team_id = ' . $this->_getDb()->quote(($conditions['team_id']));
		}

		if (! empty($conditions['has_team_id']))
		{
			$sqlConditions[] = 'thread.team_id <> 0';
		}

		if (count ($sqlConditions) > 1)
		{
			return $this->getConditionsForClause($sqlConditions);
		}
		else
		{
			return $result;
		}
	}

	public function prepareThreadFetchOptions(array $fetchOptions)
	{
		if(!empty($fetchOptions['join'])) {
			$fetchOptions[Nobita_Teams_Listener::THREAD_FETCHOPTIONS_JOIN_TEAM] = true;
		}

		$response = parent::prepareThreadFetchOptions($fetchOptions);
		extract($response);

		if(isset($fetchOptions[Nobita_Teams_Listener::THREAD_FETCHOPTIONS_JOIN_TEAM]))
		{
			$selectFields .= ',team.title as team_title,team.team_id as team_team_id,
				team.user_id as team_user_id, team.team_state as team_team_state, team.privacy_state as team_privacy_state';
			$joinTables .= '
				LEFT JOIN xf_team AS team ON (team.team_id = thread.team_id)';
		}

		return compact('selectFields', 'joinTables', 'orderClause');
	}

	public function canViewThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		$team = $teamModel->getTeamDataFromArray($thread);

		if(Nobita_Teams_Listener::$newThreadPosted instanceof XenForo_ControllerPublic_Forum
			&& !empty($thread['team_id'])
		)
		{
			$team = $teamModel->getTeamById($thread['team_id']);
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');

		if(empty($thread['team_id']))
		{
			// this thread did not belong to any groups
			// just go to XenForo processing
			return parent::canViewThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		if(empty($team['team_state']))
		{
			// well. thread belong to some groups
			// but the group data did not found in array data
			// so must be false for all state.
			return false;
		}

		if(isset($team['team_state']) && $team['team_state'])
		{
			if(!$teamModel->canViewTeamAndContainer($team, $team, $errorPhraseKey, $viewingUser))
			{
				$errorPhraseKey = new XenForo_Phrase('Teams_you_should_be_a_member_of_the_group_x_to_view_thread', array(
						'group_url' => XenForo_Link::buildPublicLink('canonical:' . TEAM_ROUTE_PREFIX, array(
							'team_id' => $thread['team_id']
						)
					),
					'group_title' => $team['title']
				));
				return false;
			}
		}

		$notVisible = ($this->isDeleted($thread) || $this->isModerated($thread));

		if($notVisible)
		{
			$memberRecord = $teamModel->getTeamMemberRecord($team['team_id'], $viewingUser);
			if($this->isDeleted($thread))
			{
				if($memberRecord && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'viewDeleted'))
				{
					// thread is deleted in state.
					// just check the role permission of user
					return true;
				}
			}

			if($this->isModerated($thread))
			{
				if($memberRecord  && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'viewModerated'))
				{
					// thread is moderated in state.
					// just check the role permission of user
					return true;
				}
			}
		}

		return parent::canViewThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	/* [Nobita] Social Groups: Extends Thread Permissions */

	public function canReplyToThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($thread['team_id']))
		{
			return parent::canReplyToThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		if(array_key_exists('allow_guest_posting', $thread))
		{
			if(empty($thread['allow_guest_posting']))
			{
				return parent::canReplyToThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
			}

			// well. group privacy did not allow guest posting into group
			// so you must become to member of group to posting
			return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->isTeamMember(
				$thread['team_id'], $viewingUser
			);
		}

		return parent::canReplyToThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	public function canViewDeletedPosts(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($thread['team_id']))
		{
			return parent::canViewDeletedPosts($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
		$memberRecord = $memberRoleModel->getTeamMemberRecord($thread['team_id'], $viewingUser);

		if($memberRecord && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'viewDeleted'))
		{
			return true;
		}

		return parent::canViewDeletedPosts($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	public function canViewModeratedPosts(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($thread['team_id']))
		{
			return parent::canViewModeratedPosts($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
		$memberRecord = $memberRoleModel->getTeamMemberRecord($thread['team_id'], $viewingUser);

		if($memberRecord && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'viewModerated'))
		{
			return true;
		}

		return parent::canViewModeratedPosts($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	public function canEditThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($thread['team_id']))
		{
			return parent::canEditThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
		$memberRecord = $memberRoleModel->getTeamMemberRecord($thread['team_id'], $viewingUser);

		if($memberRecord && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'editThreadAny'))
		{
			return true;
		}

		return parent::canEditThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	public function canEditThreadTitle(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($thread['team_id']))
		{
			return parent::canEditThreadTitle($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
		$memberRecord = $memberRoleModel->getTeamMemberRecord($thread['team_id'], $viewingUser);

		if($memberRecord && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'editThreadAny'))
		{
			return true;
		}

		return parent::canEditThreadTitle($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	public function canLockUnlockThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($thread['team_id']))
		{
			return parent::canLockUnlockThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
		$memberRecord = $memberRoleModel->getTeamMemberRecord($thread['team_id'], $viewingUser);

		if($memberRecord && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'lockUnlockThread'))
		{
			return true;
		}

		return parent::canLockUnlockThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	public function canStickUnstickThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($thread['team_id']))
		{
			return parent::canStickUnstickThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
		$memberRecord = $memberRoleModel->getTeamMemberRecord($thread['team_id'], $viewingUser);

		if($memberRecord && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'stickUnstickThread'))
		{
			return true;
		}

		return parent::canStickUnstickThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	public function canDeleteThread(array $thread, array $forum, $deleteType = 'soft', &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($thread['team_id']) || $deleteType != 'soft')
		{
			return parent::canDeleteThread($thread, $forum, $deleteType, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
		$memberRecord = $memberRoleModel->getTeamMemberRecord($thread['team_id'], $viewingUser);

		if($memberRecord && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'deleteThreadAny'))
		{
			return true;
		}

		return parent::canDeleteThread($thread, $forum, $deleteType, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	public function canUndeleteThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($thread['team_id']))
		{
			return parent::canUndeleteThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
		$memberRecord = $memberRoleModel->getTeamMemberRecord($thread['team_id'], $viewingUser);

		if($memberRecord && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'undeleteThread'))
		{
			return true;
		}

		return parent::canUndeleteThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	public function canApproveUnapproveThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		if(empty($thread['team_id']))
		{
			return parent::canApproveUnapproveThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
		$memberRecord = $memberRoleModel->getTeamMemberRecord($thread['team_id'], $viewingUser);

		if($memberRecord && $memberRoleModel->hasForumPermission($memberRecord['member_role_id'], 'approveUnapproveThread'))
		{
			return true;
		}

		return parent::canApproveUnapproveThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}

	/* END: Extend Thread Permissions */


	public function isThreadPoll(array $thread)
	{
		if(array_key_exists('team_id', $thread))
		{
			return $thread['discussion_type'] == 'poll';
		}

		throw new InvalidArgumentException("Unknown key discussion_type in thread data.");
	}

	public function isThreadGroup(array $thread)
	{
		if(array_key_exists('team_id', $thread))
		{
			return !empty($thread['team_id']);
		}

		throw new InvalidArgumentException("Unknown key team_id in thread data.");
	}
}
