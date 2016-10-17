<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_UpdateFullProfile_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 110;
	protected $_extendedClasses = array(
		'load_class_controller' => array(
			'XenForo_ControllerPublic_Account' => 'Brivium_Credits_ActionHandler_UpdateFullProfile_ControllerPublic_Account'
		),
		'load_class_datawriter' => array(
			'XenForo_DataWriter_User' => 'Brivium_Credits_ActionHandler_UpdateFullProfile_DataWriter_User'
		),
	);

 	public function getActionId()
 	{
 		return 'updateFullProfile';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_updateFullProfile';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_updateFullProfile_description';
 	}
}