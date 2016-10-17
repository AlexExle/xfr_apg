<?php

class Brivium_Credits_Payment_PayPal_ViewPublic_Payment_GetAmount extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$output = array();
		if (isset($this->_params['creditReceive']))
		{
			$amountPhrase = XenForo_Application::get('brcCurrencies')->currencyFormat($this->_params['creditReceive'], false, $this->_params['currency']['currency_id']);
			$output['amountMessage'] = new XenForo_Phrase('BRCP_paypal_you_will_receive_x', array('amount'=>$amountPhrase));
			$output['transactionTitle'] = new XenForo_Phrase('BRCP_paypal_buy_x_from_y', array('amount'=>$amountPhrase,'boardTitle'=>XenForo_Application::get("options")->boardTitle));
			$output['custom'] = $this->_params['custom'];
		}

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}