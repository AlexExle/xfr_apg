<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_Transfer_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_transfer';
	protected $_displayOrder = 11;

 	public function getActionId()
 	{
 		return 'transfer';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_transfer';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_transfer_description';
 	}
}