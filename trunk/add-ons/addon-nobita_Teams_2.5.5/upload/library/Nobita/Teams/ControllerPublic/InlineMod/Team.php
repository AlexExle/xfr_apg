<?php

class Nobita_Teams_ControllerPublic_InlineMod_Team extends XenForo_ControllerPublic_InlineMod_Abstract
{
	/**
	 * Key for inline mod data.
	 *
	 * @var string
	 */
	public $inlineModKey = 'teams';
	public function getInlineModTypeModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_InlineMod_Team');
	}
	
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			$teamIds = $this->getInlineModIds(false);
			
			$hardDelete = $this->_input->filterSingle('hard_delete', XenForo_Input::STRING);
			$options = array(
				'deleteType' => ($hardDelete ? 'hard' : 'soft'),
				'reason' => $this->_input->filterSingle('reason', XenForo_Input::STRING)
			);

			$deleted = $this->_getTeamInlineModel()->deleteTeams(
				$teamIds, $options, $errorPhraseKey
			);
			
			if (!$deleted)
			{
				throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
			}

			$this->clearCookie();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$this->getDynamicRedirect(false, false)
			);
		}
		else
		{
			$teamIds = $this->getInlineModIds();
			
			$handler = $this->_getTeamInlineModel();
			if (!$handler->canDeleteTeams($teamIds, 'soft', $errorPhraseKey))
			{
				throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
			}
			
			$redirect = $this->getDynamicRedirect();

			if (!$teamIds)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					$redirect
				);
			}

			$viewParams = array(
				'teamIds' => $teamIds,
				'teamCount' => count($teamIds),
				'canHardDelete' => $handler->canDeleteTeams($teamIds, 'hard'),
				'redirect' => $redirect,
			);

			return $this->responseView('Nobita_Teams_ViewPublic_InlineMod_Team_Mod', 'Team_inline_mod_team_delete', $viewParams);
		}
	}
	
	/**
	 * Undeletes the specified teams.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUndelete()
	{
		return $this->executeInlineModAction('undeleteTeams');
	}
	
	/**
	 * Approves the specified teams.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionApprove()
	{
		return $this->executeInlineModAction('approveTeams');
	}

	/**
	 * Unapproves the specified teams.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUnapprove()
	{
		return $this->executeInlineModAction('unapproveTeams');
	}

	/**
	 * Features the specified teams.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionFeature()
	{
		return $this->executeInlineModAction('featureTeams');
	}

	/**
	 * Unfeatures the specified teams.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUnfeature()
	{
		return $this->executeInlineModAction('unfeatureTeams');
	}
	
	protected function _getTeamInlineModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_InlineMod_Team');
	}
}