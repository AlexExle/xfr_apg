<?php

class Nobita_Teams_SpamHandler_Team extends XenForo_SpamHandler_Abstract
{
	/**
	 * Checks that the options array contains a non-empty 'action_threads' key
	 *
	 * @param array $user
	 * @param array $options
	 *
	 * @return boolean
	 */
	public function cleanUpConditionCheck(array $user, array $options)
	{
		return !empty($GLOBALS['Nobita_Teams_XenForo_ControllerPublic_SpamCleaner']);
	}

	/**
	 * @see XenForo_SpamHandler_Abstract::cleanUp()
	 */
	public function cleanUp(array $user, array &$log, &$errorKey)
	{
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		$teams = $teamModel->getTeams(array(
			'user_id' => $user['user_id'],
			'moderated' => true,
			'deleted' => true
		));
		
		if ($teams)
		{
			$teamIds = array_keys($teams);
			
			$deleteType = (XenForo_Application::get('options')->spamMessageAction == 'delete' ? 'hard' : 'soft');

			$log['team'] = array(
				'deleteType' => $deleteType,
				'teamIds' => $teamIds
			);
			
			$inlineModModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_InlineMod_Team');
			$inlineModModel->enableLogging = false;
			
			$success = $inlineModModel->deleteTeams(
				$teamIds, array('deleteType' => $deleteType, 'skipPermissions' => true), $errorKey
			);
			
			if (!$success)
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * @see XenForo_SpamHandler_Abstract::restore()
	 */
	public function restore(array $log, &$errorKey = '')
	{
		if ($log['deleteType'] == 'soft')
		{
			$inlineModModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_InlineMod_Team');
			$inlineModModel->enableLogging = false;

			return $inlineModModel->undeleteTeams(
				$log['teamIds'], array('skipPermissions' => true), $errorKey
			);
		}

		return true;
	}

}