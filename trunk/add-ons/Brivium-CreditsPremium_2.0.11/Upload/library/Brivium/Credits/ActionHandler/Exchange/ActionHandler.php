<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_Exchange_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_exchange';
	protected $_displayOrder = 12;

 	public function getActionId()
 	{
 		return 'exchange';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_exchange';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_exchange_description';
 	}
}