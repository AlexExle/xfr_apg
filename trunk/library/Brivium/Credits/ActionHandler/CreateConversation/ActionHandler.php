<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_CreateConversation_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 160;
	protected $_contentRoute = 'conversations';
	protected $_contentIdKey = 'conversation_id';

	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_ConversationMessage' => 'Brivium_Credits_ActionHandler_CreateConversation_DataWriter_ConversationMessage'
		)
	);

 	public function getActionId()
 	{
 		return 'createConversation';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_createConversation';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_createConversation_description';
 	}
}