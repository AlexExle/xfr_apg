<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_UpdateStatus_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 130;
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_DiscussionMessage_ProfilePost' => 'Brivium_Credits_ActionHandler_UpdateStatus_DataWriter_DiscussionMessage_ProfilePost'
		),
	);

 	public function getActionId()
 	{
 		return 'updateStatus';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_updateStatus';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_updateStatus_description';
 	}
}