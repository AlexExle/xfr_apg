<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_DailyReward_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 175;

 	public function getActionId()
 	{
 		return 'dailyReward';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_dailyReward';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_dailyReward_description';
 	}
}