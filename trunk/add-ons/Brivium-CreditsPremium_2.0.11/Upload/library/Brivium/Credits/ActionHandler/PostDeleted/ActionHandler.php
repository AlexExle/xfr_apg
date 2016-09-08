<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_PostDeleted_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion_create';
	protected $_displayOrder = 311;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';

	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_DiscussionMessage_Post' => 'Brivium_Credits_ActionHandler_PostDeleted_DataWriter_DiscussionMessage_Post'
		)
	);

 	public function getActionId()
 	{
 		return 'postDeleted';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_postDeleted';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_postDeleted_description';
 	}
}