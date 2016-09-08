<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ThreadGetWatchedRe_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 243;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';
	protected $_extendedClasses = array(
		'load_class_controller' => array(
			'XenForo_ControllerPublic_Thread' => 'Brivium_Credits_ActionHandler_ThreadGetWatchedRe_ControllerPublic_Thread'
		),
		'load_class_datawriter' => array(
			'XenForo_DataWriter_ThreadWatch' => 'Brivium_Credits_ActionHandler_ThreadGetWatchedRe_DataWriter_ThreadWatch'
		),
	);

 	public function getActionId()
 	{
 		return 'threadGetWatchedRe';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_threadGetWatchedRe';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_threadGetWatchedRe_description';
 	}
}