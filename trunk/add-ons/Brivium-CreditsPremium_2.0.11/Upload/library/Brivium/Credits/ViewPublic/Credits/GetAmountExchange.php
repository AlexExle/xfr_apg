<?php

class Brivium_Credits_ViewPublic_Credits_GetAmountExchange extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$output = array();
		if ($this->_params['amount'])
		{
			$output['amountMessage'] = new XenForo_Phrase('BRC_you_will_receive_x', array('amount'=>$this->_params['amount']));
		}
		if ($this->_params['loseAmount'])
		{
			$output['loseAmountMessage'] = new XenForo_Phrase('BRC_you_will_lose_x', array('amount'=>$this->_params['loseAmount']));
		}

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}