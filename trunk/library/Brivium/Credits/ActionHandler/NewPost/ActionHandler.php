<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_NewPost_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion_create';
	protected $_displayOrder = 310;
	protected $_contentRoute = 'posts';
	protected $_contentIdKey = 'post_id';

	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_DiscussionMessage_Post' => 'Brivium_Credits_ActionHandler_NewPost_DataWriter_DiscussionMessage_Post'
		)
	);

 	public function getActionId()
 	{
 		return 'newPost';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_newPost';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_newPost_description';
 	}
}