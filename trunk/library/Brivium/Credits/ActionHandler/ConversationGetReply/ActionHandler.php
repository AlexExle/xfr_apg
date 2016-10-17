<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ConversationGetReply_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 164;
	protected $_contentRoute = 'conversations';
	protected $_contentIdKey = 'conversation_id';

	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_ConversationMaster' => 'Brivium_Credits_ActionHandler_ConversationGetReply_DataWriter_ConversationMaster',
			'XenForo_DataWriter_ConversationMessage' => 'Brivium_Credits_ActionHandler_ConversationGetReply_DataWriter_ConversationMessage'
		)
	);

 	public function getActionId()
 	{
 		return 'conversationGetReply';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_conversationGetReply';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_conversationGetReply_description';
 	}
}