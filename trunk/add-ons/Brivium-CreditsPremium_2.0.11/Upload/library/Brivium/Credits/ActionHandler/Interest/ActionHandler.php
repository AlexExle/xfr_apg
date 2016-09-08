<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_Interest_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_interest';
	protected $_displayOrder = 50;

 	public function getActionId()
 	{
 		return 'interest';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_interest';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_interest_description';
 	}

	public function prepareTriggerData($event, &$user, $currency, &$triggerData, &$errorString)
	{
		if($user[$currency['column']]>0){
			$triggerData['multiplier'] = $user[$currency['column']];
		}else{
			$errorString = new XenForo_Phrase('BRC_not_valid_amount');
			return false;
		}
	}
}