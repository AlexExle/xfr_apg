<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ProfilePost_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 150;
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_DiscussionMessage_ProfilePost' => 'Brivium_Credits_ActionHandler_ProfilePost_DataWriter_DiscussionMessage_ProfilePost'
		),
	);

 	public function getActionId()
 	{
 		return 'profilePost';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_profilePost';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_profilePost_description';
 	}
}