<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_Withdraw_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_displayOrder = 13;

 	public function getActionId()
 	{
 		return 'withdraw';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_withdraw';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_withdraw_description';
 	}
}