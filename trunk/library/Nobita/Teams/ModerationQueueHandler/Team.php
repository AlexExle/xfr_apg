<?php

class Nobita_Teams_ModerationQueueHandler_Team extends XenForo_ModerationQueueHandler_Abstract
{
	public function getVisibleModerationQueueEntriesForUser(array $contentIds, array $viewingUser)
	{
		/* @var $teamModel Nobita_Teams_Model_Team */
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		$teams = $teamModel->getTeamsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Team::FETCH_PROFILE
					| Nobita_Teams_Model_Team::FETCH_PRIVACY
					| Nobita_Teams_Model_Team::FETCH_CATEGORY
		));

		$output = array();
		foreach ($teams as $team)
		{
			$canManage = true;
			if (!$teamModel->canViewTeamAndContainer($team, $team, $key))
			{
				$canManage = false;
			}
			else if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editAny')
				|| !XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteAny')
			)
			{
				$canManage = false;
			}

			if ($canManage)
			{
				$output[$team['team_id']] = array(
					'message' => $team['tag_line'],
					'user' => array(
						'user_id' => $team['user_id'],
						'username' => $team['username']
					),
					'title' => $team['title'],
					'link' => Nobita_Teams_Link::buildTeamLink('', $team),
					'contentTypeTitle' => new XenForo_Phrase('Teams_teams'),
					'titleEdit' => false
				);
			}
		}

		return $output;
	}

	public function approveModerationQueueEntry($contentId, $message, $title)
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
		$dw->setExistingData($contentId);
		$dw->set('team_state', 'visible');
		$dw->set('tag_line', $message);

		if ($dw->save())
		{
			XenForo_Model_Log::logModeratorAction('team', $dw->getMergedData(), 'approve');
			return true;
		}
		else
		{
			return false;
		}
	}

	public function deleteModerationQueueEntry($contentId)
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
		$dw->setExistingData($contentId);
		$dw->set('team_state', 'deleted');

		if ($dw->save())
		{
			XenForo_Model_Log::logModeratorAction('team', $dw->getMergedData(), 'delete_soft', array('reason' => ''));
			return true;
		}
		else
		{
			return false;
		}
	}
}
