<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_UpdateFullProfileRe_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 111;
	protected $_extendedClasses = array(
		'load_class_controller' => array(
			'XenForo_ControllerPublic_Account' => 'Brivium_Credits_ActionHandler_UpdateFullProfileRe_ControllerPublic_Account'
		),
		'load_class_datawriter' => array(
			'XenForo_DataWriter_User' => 'Brivium_Credits_ActionHandler_UpdateFullProfileRe_DataWriter_User'
		),
	);

 	public function getActionId()
 	{
 		return 'updateFullProfileRe';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_updateFullProfileRe';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_updateFullProfileRe_description';
 	}
}