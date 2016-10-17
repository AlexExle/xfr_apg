<?php

class Brivium_Credits_ViewPublic_Credits_GetAmountWithdraw extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$output = array();
		if ($this->_params['amount'])
		{
			$output['amountMessage'] = new XenForo_Phrase('BRCP_paypal_you_will_receive_x', array('amount'=>$this->_params['amount']));
		}

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}