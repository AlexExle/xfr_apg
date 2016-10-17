<?php

class Brivium_Credits_ControllerAdmin_User extends XFCP_Brivium_Credits_ControllerAdmin_User
{
	public function actionSave() {
		$GLOBALS['BRC_ControllerAdmin_User'] = $this;
		return parent::actionSave();
	}
	protected function _getUserAddEditResponse(array $user)
	{
		$response = parent::_getUserAddEditResponse($user);
		if (XenForo_Visitor::getInstance()->hasAdminPermission('BRC_editUserCredits'))
		{
			$response->params['currencies'] = $this->getModelFromCache('Brivium_Credits_Model_Currency')->getAllCurrencies();
		}
		return $response;
	}
}