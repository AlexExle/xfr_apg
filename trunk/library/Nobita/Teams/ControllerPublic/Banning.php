<?php

class Nobita_Teams_ControllerPublic_Banning extends Nobita_Teams_ControllerPublic_Abstract
{
	public function actionAdd()
	{
		list ($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

		$memberModel = $this->_getMemberModel();
		$banningModel = $this->_getBanningModel();

		if (!$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserById($userId))
		{
			return $this->responseError(new XenForo_Phrase('requested_user_not_found'), 404);
		}

		$existedMember = $memberModel->getRecordByKeys($userId, $team['team_id']);
		$banned = $banningModel->getBanningByKeys($team['team_id'], $user['user_id']);

		if(!$existedMember && !$banned)
		{
			// oops.. not found instead of
			throw $this->getNoPermissionResponseException();
		}

		$banningModel = $this->_getBanningModel();
		if (!$banningModel->canBanUser($existedMember ? $existedMember : $user, $team, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$deleteMember = false;
		if ($existedMember && $memberModel->canRemoveMember($existedMember, $team))
		{
			$deleteMember = true;
		}

		$visitor = XenForo_Visitor::getInstance();
		if ($this->_request->isPost())
		{
			$inputDw = $this->_input->filter(array(
				'delete_member' => XenForo_Input::BOOLEAN,
				'delete_content' => XenForo_Input::BOOLEAN,
				'end_date' => XenForo_Input::DATE_TIME,
				'user_reason' => XenForo_Input::STRING
			));

			if ($inputDw['end_date'] < XenForo_Application::$time)
			{
				return $this->responseError(new XenForo_Phrase('please_enter_a_date_in_the_future'));
			}

			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Banning');
			if ($banned)
			{
				$dw->setExistingData($banned);
			}
			else
			{
				$dw->set('ban_user_id', $visitor['user_id']);
			}

			$dw->set('user_id', $user['user_id']);
			$dw->set('team_id', $team['team_id']);
			$dw->set('end_date', $inputDw['end_date']);
			$dw->set('user_reason', $inputDw['user_reason']);

			if ($dw->save())
			{
				if ($deleteMember && $inputDw['delete_member'])
				{
					$memberDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Member');
					if ($memberDw->setExistingData($existedMember))
					{
						$memberDw->delete();
					}
				}
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('members', $team, array('order' => 'blocked'))
			);
		}
		else
		{
			$viewParams = array(
				'team' => $team,
				'category' => $category,
				'deleteMember' => $deleteMember,
				'existing' => $existedMember,
				'banned' => $banned,
				'user' => $user,
			);

			return $this->_getTeamHelper()->getTeamViewWrapper('members', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Banning_Add', 'Team_ban_edit', $viewParams)
			);
		}
	}

	public function actionLift()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));

		list ($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$banningModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Banning');
		if (!$banningModel->canBanUser(null, $team, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		if (!$existing = $banningModel->getBanningByKeys($team['team_id'], $userId))
		{
			throw $this->getNoPermissionResponseException();
		}

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Banning');
		$dw->setExistingData($existing);
		$dw->delete();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$this->_buildLink(TEAM_ROUTE_PREFIX . '/members', $team, array('order' => 'blocked'))
		);
	}
}
