<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ThreadDeleted_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion_delete';
	protected $_displayOrder = 211;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_Discussion_Thread' => 'Brivium_Credits_ActionHandler_ThreadDeleted_DataWriter_Discussion_Thread'
		)
	);

 	public function getActionId()
 	{
 		return 'threadDeleted';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_threadDeleted';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_threadDeleted_description';
 	}
}