<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_Birthday_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_birthday';
	protected $_displayOrder = 30;

 	public function getActionId()
 	{
 		return 'birthday';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_birthday';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_birthday_description';
 	}
}