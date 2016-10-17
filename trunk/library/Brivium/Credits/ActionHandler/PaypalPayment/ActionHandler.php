<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_PaypalPayment_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_PayPalPayment';
	protected $_displayOrder = 20;

 	public function getActionId()
 	{
 		return 'paypalPayment';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_paypalPayment';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_paypalPayment_description';
 	}

	protected function _prepareEventEditParams(&$event, $viewParams = array())
	{
		$listPriceSteps = array();
		if(isset($event['extra_data']) && !empty($event['extra_data']['step_price'])){
			foreach($event['extra_data']['step_price'] AS $step=>$priceStep){
				$listPriceSteps[] = $priceStep;
			}
			$viewParams['listPriceSteps'] = $listPriceSteps;
		}
		$viewParams['nextCounter'] = count($listPriceSteps);
		return $viewParams;
	}

	protected function _verifyEvent($event, Brivium_Credits_DataWriter_Event $eventWriter)
	{
		$extraData = @unserialize($event['extra_data']);
		if(!empty($extraData['price_type']) && $extraData['price_type']=='step'){
			if(!empty($extraData['step_price'])){
				$listPriceStep = array();
				foreach($extraData['step_price'] AS $priceStep){
					if(!empty($priceStep['credit']) && $priceStep['credit'] > 0 && !empty($priceStep['name']) && !empty($priceStep['cost']) && $priceStep['cost'] > 0){
						$listPriceStep[$priceStep['cost']] = $priceStep;
					}
				}
				if(!$listPriceStep){
					$eventWriter->error(new XenForo_Phrase('BRC_missing_price_step'), 'extra_data');
				}
				ksort($listPriceStep);
				$extraData['step_price'] = $listPriceStep;
				$eventWriter->set('extra_data', $extraData);
			}else{
				$eventWriter->error(new XenForo_Phrase('BRC_missing_price_step'), 'extra_data');
			}
		}
		return true;
	}
}