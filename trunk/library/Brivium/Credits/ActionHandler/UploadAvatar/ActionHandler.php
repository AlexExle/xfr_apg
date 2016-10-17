<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_UploadAvatar_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 120;
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_User' => 'Brivium_Credits_ActionHandler_UploadAvatar_DataWriter_User'
		),
	);

 	public function getActionId()
 	{
 		return 'uploadAvatar';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_uploadAvatar';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_uploadAvatar_description';
 	}
}