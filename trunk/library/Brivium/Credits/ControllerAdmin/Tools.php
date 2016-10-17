<?php

class Brivium_Credits_ControllerAdmin_Tools extends XFCP_Brivium_Credits_ControllerAdmin_Tools
{
	public function actionRebuild()
	{
		$response = parent::actionRebuild();
		$response->params['currencies'] = XenForo_Application::get('brcCurrencies')->getCurrencies();
		$response->params['type'] = 'merge';
		$response->params['importBdBank'] = XenForo_Model::create('XenForo_Model_AddOn')->getAddOnVersion('bdbank');
		return $response;
	}

}