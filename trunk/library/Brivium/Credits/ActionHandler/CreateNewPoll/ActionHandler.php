<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_CreateNewPoll_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 250;
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_Poll' => 'Brivium_Credits_ActionHandler_CreateNewPoll_DataWriter_Poll'
		),
	);

 	public function getActionId()
 	{
 		return 'createNewPoll';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_createNewPoll';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_createNewPoll_description';
 	}
}