<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_Steal_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 14;

 	public function getActionId()
 	{
 		return 'steal';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_steal';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_steal_description';
 	}
}