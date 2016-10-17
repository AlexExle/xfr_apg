<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_CreResIntegration_ActionHandler_ResourceGetPurchased_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_ResourceGetPurchased';
	protected $_addOnId = 'Brivium_CreResIntegration';
	protected $_addOnTitle = 'Brivium - Credits Resource Integration';

	protected $_displayOrder = 612;

	protected $_contentRoute = 'resources';
	protected $_contentIdKey = 'resource_id';

 	public function getActionId()
 	{
 		return 'ResourceGetPurchased';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_ResourceGetPurchased';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_ResourceGetPurchased_explain';
 	}
}