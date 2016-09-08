<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_GetProfilePost_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 151;
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_DiscussionMessage_ProfilePost' => 'Brivium_Credits_ActionHandler_GetProfilePost_DataWriter_DiscussionMessage_ProfilePost'
		),
	);

 	public function getActionId()
 	{
 		return 'getProfilePost';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_getProfilePost';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_getProfilePost_description';
 	}
}