<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_TwitterAssociate_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 413;
	protected $_extendedClasses = array(
		'load_class_model' => array(
			'XenForo_Model_UserExternal' => 'Brivium_Credits_ActionHandler_TwitterAssociate_Model_UserExternal'
		)
	);

 	public function getActionId()
 	{
 		return 'twitterAssociate';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_twitterAssociate';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_twitterAssociate_description';
 	}
}