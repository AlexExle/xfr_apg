<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ThreadSticky_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 260;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_Discussion_Thread' => 'Brivium_Credits_ActionHandler_ThreadSticky_DataWriter_Discussion_Thread'
		),
	);

 	public function getActionId()
 	{
 		return 'threadSticky';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_threadSticky';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_threadSticky_description';
 	}
}