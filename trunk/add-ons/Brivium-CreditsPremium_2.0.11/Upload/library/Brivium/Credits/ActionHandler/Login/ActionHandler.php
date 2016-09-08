<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_Login_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 10;
	protected $_extendedClasses = array(
		'load_class_controller' => array(
			'XenForo_ControllerPublic_Login' => 'Brivium_Credits_ActionHandler_Login_ControllerPublic_Login'
		)
	);

 	public function getActionId()
 	{
 		return 'login';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_login';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_login_description';
 	}
}