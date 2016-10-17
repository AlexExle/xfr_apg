<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_PaypalPaymentRe_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_PayPalPayment';
	protected $_displayOrder = 21;

 	public function getActionId()
 	{
 		return 'paypalPaymentRe';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_paypalPaymentRe';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_paypalPaymentRe_description';
 	}
}