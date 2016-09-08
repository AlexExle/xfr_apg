<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_FacebookAssociate_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 410;
	protected $_extendedClasses = array(
		'load_class_model' => array(
			'XenForo_Model_UserExternal' => 'Brivium_Credits_ActionHandler_FacebookAssociate_Model_UserExternal'
		)
	);

 	public function getActionId()
 	{
 		return 'facebookAssociate';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_facebookAssociate';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_facebookAssociate_description';
 	}
}