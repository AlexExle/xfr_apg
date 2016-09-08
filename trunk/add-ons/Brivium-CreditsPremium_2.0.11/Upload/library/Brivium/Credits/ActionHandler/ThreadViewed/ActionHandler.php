<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ThreadViewed_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 231;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';
	protected $_extendedClasses = array(
		'load_class_model' => array(
			'XenForo_Model_Thread' => 'Brivium_Credits_ActionHandler_ThreadViewed_Model_Thread'
		)
	);

 	public function getActionId()
 	{
 		return 'threadViewed';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_threadViewed';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_threadViewed_description';
 	}
}