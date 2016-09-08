<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ReadThread_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 230;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';

	protected $_extendedClasses = array(
		'load_class_model' => array(
			'XenForo_Model_Thread' => 'Brivium_Credits_ActionHandler_ReadThread_Model_Thread'
		)
	);

 	public function getActionId()
 	{
 		return 'readThread';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_readThread';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_readThread_description';
 	}
}