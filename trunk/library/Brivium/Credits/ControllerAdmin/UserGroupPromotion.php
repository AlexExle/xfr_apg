<?php

class Brivium_Credits_ControllerAdmin_UserGroupPromotion extends XFCP_Brivium_Credits_ControllerAdmin_UserGroupPromotion
{
	protected function _getPromotionAddEditResponse(array $promotion)
	{
		$response = parent::_getPromotionAddEditResponse($promotion);
		if(!empty($response->params)){
			$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
			if($currencies){
				$response->params['brcCurrencies'] = $currencies;
			}
		}
		return $response;
	}
}