<?php

class Brivium_Credits_Payment_PayPal_ControllerPublic_Payment extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS, 'credits/buy-credit'
		);
	}

	public function actionCreditPurchase()
	{
		$viewParams = array();

		return $this->responseView(
			'Brivium_Credits_Payment_PayPal_ViewPublic_Payment',
			'BRCP_paypal_successfull_transaction',
			$viewParams
		);
	}

	protected function _checkCsrf($action)
	{
		if($action == 'CreditPurchase'){
			return;
		}
		return parent::_checkCsrf($action);
	}

	public function actionPriceOptions()
	{
		$options = XenForo_Application::get('options');
		$amount = $this->_input->filterSingle('amount', XenForo_Input::UNUM);

		$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);
		if(!$currencyId){
			$currencyId = $this->_input->filterSingle('event_id', XenForo_Input::UINT);
		}
		$currencyObj = XenForo_Application::get('brcCurrencies');

		list($event, $currency) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('paypalPayment',$currencyId);

		$minPaid = 0;
		if(isset($event['extra_data'], $event['extra_data']['price_type']) && !empty($event['extra_data']['fee']) && $event['extra_data']['price_type']!='step'){
			$minPaid += $event['extra_data']['fee'];
		}
		$options = XenForo_Application::get('options');
		if($options->BRCP_creditPurchaseNumber['min'] > 0 && $minPaid < $options->BRCP_creditPurchaseNumber['min']){
			$minPaid = $options->BRCP_creditPurchaseNumber['min'];
		}
		$viewParams = array(
			'currency' => $currency,
			'event' => $event,
			'minPaid' => $minPaid,
		);

		return $this->responseView(
			'Brivium_Credits_Payment_PayPal_ViewPublic_Payment_PriceOptions',
			'BRCP_price_options',
			$viewParams
		);
	}

	public function actionGetForumAmount()
	{
		$moneyPaid = $this->_input->filterSingle('amount', XenForo_Input::UNUM);
		$moneyPaidString = $this->_input->filterSingle('amount', XenForo_Input::STRING);

		$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);
		if(!$currencyId){
			$currencyId = $this->_input->filterSingle('event_id', XenForo_Input::UINT);
		}
		$xfToken = $this->_input->filterSingle('xfToken', XenForo_Input::STRING);
		$visitor = XenForo_Visitor::getInstance()->toArray();

		$currencyObj = XenForo_Application::get('brcCurrencies');

		list($event, $currency) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('paypalPayment',$currencyId);

		if(isset($event['extra_data'], $event['extra_data']['price_type'], $event['extra_data']['step_price']) && $event['extra_data']['price_type']=='step'){
			if(!empty($event['extra_data']['step_price'][$moneyPaid])){
				$creditReceive = $event['extra_data']['step_price'][$moneyPaid]['credit'];
			}elseif(!empty($event['extra_data']['step_price'][$moneyPaidString])){
				$creditReceive = $event['extra_data']['step_price'][$moneyPaidString]['credit'];
			}else{
				$creditReceive = 0;
			}
		}else{
			$options = XenForo_Application::get('options');
			if($options->BRCP_creditPurchaseNumber['max'] > 0 && $moneyPaid > $options->BRCP_creditPurchaseNumber['max']){
				$moneyPaid = $options->BRCP_creditPurchaseNumber['max'];
			}
			if(!empty($event['extra_data']['fee'])){
				$moneyPaid = $moneyPaid - $event['extra_data']['fee'];
			}
			if(!empty($event['extra_data']['tax'])){
				$moneyPaid = $moneyPaid * (100 - $event['extra_data']['tax'])/100;
			}
			$creditReceive = $event['multiplier']>0?round($moneyPaid/$event['multiplier'], $currency['decimal_place']):$moneyPaid;
		}
		if($creditReceive > 0){
			$creditReceive += $event['amount'];
		}

		$custom = $visitor['user_id'] .','.$currencyId.','.'paypal'.','.'token'.','.$xfToken;
		$viewParams = array(
			'creditReceive' => $creditReceive,
			'currency' => $currency,
			'event' => $event,
			'custom' => $custom,
		);

		return $this->responseView(
			'Brivium_Credits_Payment_PayPal_ViewPublic_Payment_GetAmount',
			'',
			$viewParams
		);
	}

	protected function _preDispatch($action)
	{
		$this->_assertRegistrationRequired();
		if (!$this->_getCreditModel()->canUseCredits($error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}
	/**
	 * Gets the action model.
	 *
	 * @return Brivium_Credits_Model_Credit
	 */
	protected function _getCreditModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Credit');
	}

	protected function _getCreditHelper()
	{
		return $this->getHelper('Brivium_Credits_ControllerHelper_Credit');
	}
}