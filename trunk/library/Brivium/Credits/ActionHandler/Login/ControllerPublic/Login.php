<?php

class Brivium_Credits_ActionHandler_Login_ControllerPublic_Login extends XFCP_Brivium_Credits_ActionHandler_Login_ControllerPublic_Login
{
	public function actionLogin()
	{
		$response = parent::actionLogin();
		if($userId = XenForo_Visitor::getUserId()){
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('login', $userId);
		}
		return $response;
	}
}