<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ThreadGetWatched_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 242;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';
	protected $_extendedClasses = array(
		'load_class_controller' => array(
			'XenForo_ControllerPublic_Thread' 	=> 'Brivium_Credits_ActionHandler_ThreadGetWatched_ControllerPublic_Thread'
		),
		'load_class_datawriter' => array(
			'XenForo_DataWriter_ThreadWatch' 	=> 'Brivium_Credits_ActionHandler_ThreadGetWatched_DataWriter_ThreadWatch'
		),
	);

 	public function getActionId()
 	{
 		return 'threadGetWatched';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_threadGetWatched';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_threadGetWatched_description';
 	}
}