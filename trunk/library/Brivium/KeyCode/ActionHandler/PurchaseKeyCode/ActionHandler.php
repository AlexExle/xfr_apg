<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_KeyCode_ActionHandler_PurchaseKeyCode_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_addOnId = 'Brivium_KeyCode';
	protected $_addOnTitle = 'Brivium - Key Code';

	protected $_displayOrder = 810;

	protected $_contentRoute = 'key-code';
	protected $_contentIdKey = 'code_id';

 	public function getActionId()
 	{
 		return 'BRKC_PurchaseKeyCode';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_BRKC_PurchaseKeyCode';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_BRKC_PurchaseKeyCode_explain';
 	}
}