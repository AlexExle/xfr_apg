<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_Salary_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_displayOrder = 178;

 	public function getActionId()
 	{
 		return 'salary';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_salary';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_salary_description';
 	}
}