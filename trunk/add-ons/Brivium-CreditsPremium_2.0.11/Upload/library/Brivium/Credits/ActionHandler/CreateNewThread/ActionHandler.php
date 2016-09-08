<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_CreateNewThread_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion_create';
	protected $_displayOrder = 210;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';

	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_Discussion_Thread' => 'Brivium_Credits_ActionHandler_CreateNewThread_DataWriter_Discussion_Thread'
		)
	);

 	public function getActionId()
 	{
 		return 'createNewThread';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_createNewThread';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_createNewThread_description';
 	}
}