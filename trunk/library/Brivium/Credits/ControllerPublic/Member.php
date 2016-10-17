<?php

class Brivium_Credits_ControllerPublic_Member extends XFCP_Brivium_Credits_ControllerPublic_Member
{
	public function actionIndex()
    {
		$response = parent::actionIndex();
		if ($this->_getCreditModel()->canUseCredits())
		{
			$currencyDisplay = XenForo_Application::get('options')->get('BRC_currencyDisplay');
			if(isset($currencyDisplay[0]) && ($currencyDisplay[0]==''||$currencyDisplay[0]==0)){
				$currencyDisplay = array();
			}
			$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
			$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);
			$response->params['currencyDisplay'] = $currencyDisplay;
			$response->params['currencyId'] = $currencyId;
			if($currencies){
				$response->params['brcCurrencies'] = $currencies;
			}
		}
		return $response;
    }

	protected function _getNotableMembers($type, $limit)
	{
		$result = parent::_getNotableMembers($type, $limit);
		if ($this->_getCreditModel()->canUseCredits())
		{
			if(!$result && $type=='richest_credits'){
				$userModel = $this->_getUserModel();
				$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);
				$currency = XenForo_Application::get('brcCurrencies')->$currencyId;
				if(!$currency)return $this->responseError(new XenForo_Phrase('BRC_requested_currency_not_found'));

				$notableCriteria = array(
					'is_banned' => 0
				);
				$typeMap = array(
					'richest_credits' => $currency['column'],
				);

				if (!isset($typeMap[$type]))
				{
					return false;
				}

				return array($userModel->getUsers($notableCriteria, array(
					'join' => XenForo_Model_User::FETCH_USER_FULL,
					'limit' => $limit,
					'order' => $currency['column'],
					'direction' => 'desc'
				)), $typeMap[$type]);
			}
		}
		return $result;
	}

	public function actionMember()
	{
		$response = parent::actionMember();
		if ($this->_getCreditModel()->canUseCredits() && !empty($response->params['user']))
		{
			$response->params['canEditUserCredits'] = $this->_getCreditModel()->canEditUserCredits();
		}
		return $response;
	}


	public function actionEditCredits()
	{
		if(!$this->_getCreditModel()->canEditUserCredits()){
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}
		$currencies = $this->getModelFromCache('Brivium_Credits_Model_Currency')->getAllCurrencies();
		if(!$currencies){
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$user = $this->_getUserModel()->getUserById($userId);
		if(!$user){
			return $this->responseError(new XenForo_Phrase('requested_member_not_found'), 404);
		}
		$this->getHelper('Admin')->checkSuperAdminEdit($user);

		if ($this->_getUserModel()->isUserSuperAdmin($user))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}
		if ($this->isConfirmedPost())
		{
			$this->_assertPostOnly();
			$newData = array();
			$changedData = array();
			foreach($currencies AS $currency){
				$credits = 0;
				$credits = $this->_input->filterSingle($currency['column'], XenForo_Input::NUM);

				$oldValue = !empty($user[$currency['column']])?$user[$currency['column']]:0;
				if($oldValue != $credits){
					$newData[$currency['column']] = $credits;
					$changedData[$currency['title']] = array(
						'old'	=>	$oldValue,
						'new'	=>	$credits,
					);
				}
			}
			$writer = XenForo_DataWriter::create('XenForo_DataWriter_User');
			$writer->setExistingData($userId);
			$writer->bulkSet($newData);

			$writer->preSave();

			if ($dwErrors = $writer->getErrors())
			{
				return $this->responseError($dwErrors);
			}

			$writer->save();

			XenForo_Model_Log::logModeratorAction('credit', $user, 'edit', $changedData);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('members', $user)
			);
		}
		else // show delete confirmation prompt
		{
			$viewParams = array(
				'user' => $user,
				'currencies' => $currencies,
			);

			return $this->responseView(
				'Brivium_Credits_ViewAdmin_Credits_WithDraw',
				'BRC_edit_user_credits',
				$viewParams
			);
		}
	}
	protected function _getCreditModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Credit');
	}

}
