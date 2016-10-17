<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_CreResIntegration_ActionHandler_ResourcePurchased_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_addOnId = 'Brivium_CreResIntegration';
	protected $_addOnTitle = 'Brivium - Credits Resource Integration';

	protected $_displayOrder = 611;

	protected $_contentRoute = 'resources';
	protected $_contentIdKey = 'resource_id';

 	public function getActionId()
 	{
 		return 'ResourcePurchased';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_ResourcePurchased';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_ResourcePurchased_explain';
 	}
}