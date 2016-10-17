<?php

class Nobita_Teams_XenForo_ControllerPublic_Forum extends XFCP_Nobita_Teams_XenForo_ControllerPublic_Forum
{
	protected function _getForumFetchOptions()
	{
		$options = parent::_getForumFetchOptions();
		$options[Nobita_Teams_Listener::FORUM_FETCHOPTIONS_JOIN_TEAM] = true;

		return $options;
	}

	protected function _getThreadFetchElements(array $forum, array $displayConditions)
	{
		$options = parent::_getThreadFetchElements($forum, $displayConditions);
		if(!empty($forum['team_id']))
		{
			if(!isset($options['conditions']))
			{
				$options['conditions'] = array();
			}
			$options['conditions']['team_id'] = $forum['team_id'];
		}

		return $options;
	}

	public function actionForum()
	{
		$response = parent::actionForum();
		if($response instanceof XenForo_ControllerResponse_View && !empty($response->params))
		{
			$params =& $response->params;
			if(!isset($params['forum']))
			{
				// Maybe throw error: http://nobita.me/threads/789/
				return $response;
			}

			if(empty($params['forum']['team_id']))
			{
				return $response;
			}

			$helper = $this->getHelper('Nobita_Teams_ControllerHelper_Team');
			$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

			list($team, $category) = $helper->assertTeamValidAndViewable($params['forum']['team_id']);
			$params += array(
				'team' => $team,
				'category' => $category,
				'canEditForum' => $teamModel->canEditForum($params['forum'], $team, $category),
				'canDeleteForum' => $teamModel->canDeleteForum($params['forum'], $team, $category),
			);

			$response = $helper->getTeamViewWrapper('forums', $team, $category,
				$this->responseView('XenForo_ViewPublic_Forum_View', 'forum_view', $params)
			);
		}

		return $response;
	}

	public function actionCreateThread()
	{
		$response = parent::actionCreateThread();
		if($response instanceof XenForo_ControllerResponse_View)
		{
			$params =& $response->params;
			if(empty($params['forum']['team_id']))
			{
				return $response;
			}

			$helper = $this->getHelper('Nobita_Teams_ControllerHelper_Team');

			list($team, $category) = $helper->assertTeamValidAndViewable($params['forum']['team_id']);
			$params += array(
				'team' => $team,
				'category' => $category,
			);

			$response = $helper->getTeamViewWrapper('forums', $team, $category,
				$this->responseView('XenForo_ViewPublic_Thread_Create', 'thread_create', $params)
			);
		}

		return $response;
	}

	public function actionAddThread()
	{
		$GLOBALS[Nobita_Teams_Listener::TEAM_CONTROLLERPUBLIC_FORUM_ADDTHREAD] = $this;
		Nobita_Teams_Listener::$newThreadPosted = $this;

		return parent::actionAddThread();
	}

	public function Team_actionAddThread(XenForo_DataWriter_Discussion_Thread $dw)
	{
		$forum = $dw->getExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM);
		if(!$forum)
		{
			$forum = $this->_getForumModel()->getForumById($dw->get('node_id'));
		}

		if(empty($forum['team_id']))
		{
			// The forum did not belong to any groups
			return;
		}

		$teamHelper = $this->getHelper('Nobita_Teams_ControllerHelper_Team');
		list($team, $category) = $teamHelper->assertTeamValidAndViewable($forum['team_id']);

		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		if(!$teamModel->canViewTabAndContainer('threads', $team, $category))
		{
			$dw->error(new XenForo_Phrase('Teams_requested_team_not_found'));
			return false;
		}

		if(!$teamModel->canPostOnTeam($team, $category, $error))
		{
			// Fixed the bug: http://nobita.me/threads/464/
			$errorPhraseKey = new XenForo_Phrase('Teams_you_need_become_an_member_of_group_x_to_post_new_thread', array(
				'title' => $team['title'],
				'group_link' => Nobita_Teams_Link::buildTeamLink('', $team),
			));
			$dw->error($errorPhraseKey);
			return false;
		}

		$dw->set('team_id', $team['team_id']);

		// Bug report: https://xenforo.com/community/posts/947891
		if ($dw->get('discussion_type') == 'poll')
		{
			// good. This is poll thread. Do not change it
		}
		else
		{
			$dw->set('discussion_type', 'team');
		}
		unset($GLOBALS[Nobita_Teams_Listener::TEAM_CONTROLLERPUBLIC_FORUM_ADDTHREAD]);
	}
}
