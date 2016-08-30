<?php

class Nobita_Teams_ModeratorLogHandler_Team extends XenForo_ModeratorLogHandler_Abstract
{
	protected $_skipLogSelfActions = array(
		'edit'
	);

	protected function _log(array $logUser, array $content, $action, array $actionParams = array(), $parentContent = null)
	{
		if (isset($content['title']))
		{
			$title = $content['title'];
		}
		else if (is_array($parentContent) && isset($parentContent['title']))
		{
			$title = $parentContent['title'];
		}
		else
		{
			$team = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getTeamById($content['team_id']);
			$title = ($team ? $team['title'] : '');
		}

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_ModeratorLog');
		$dw->bulkSet(array(
			'user_id' => $logUser['user_id'],
			'content_type' => 'team',
			'content_id' => $content['team_id'],
			'content_user_id' => $content['user_id'],
			'content_username' => $content['username'],
			'content_title' => $title,
			'content_url' => Nobita_Teams_Link::buildTeamLink('', $content),
			'discussion_content_type' => '',
			'discussion_content_id' => 0,
			'action' => $action,
			'action_params' => $actionParams
		));
		$dw->save();

		return $dw->get('moderator_log_id');
	}

}
