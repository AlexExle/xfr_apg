<?php

class Brivium_Credits_ControllerPublic_Account extends XFCP_Brivium_Credits_ControllerPublic_Account
{
	public function actionAlertPreferences()
	{
		$response =  parent::actionAlertPreferences();
		if ($this->getModelFromCache('Brivium_Credits_Model_Credit')->canUseCredits($error))
		{
			if(isset($response->subView->params)){
				$actionObj = XenForo_Application::get('brcActionHandler');
				$actions = $actionObj->getActions();
				$alertActions = array();
				foreach($actions AS $action){
					$action['checked'] = true;
					if(isset($response->subView->params['alertOptOuts']['credits_'.$action['action_id']])){
						$action['checked'] = false;
					}
					$alertActions['credits_'.$action['action_id']] = $action;
				}
				$response->subView->params['actions'] = $alertActions;
			}
		}
		return $response;
	}
}