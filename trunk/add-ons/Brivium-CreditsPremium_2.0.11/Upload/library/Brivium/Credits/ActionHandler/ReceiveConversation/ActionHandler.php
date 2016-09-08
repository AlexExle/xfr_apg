<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ReceiveConversation_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 162;
	protected $_contentRoute = 'conversations';
	protected $_contentIdKey = 'conversation_id';
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_ConversationMaster' => 'Brivium_Credits_ActionHandler_ReceiveConversation_DataWriter_ConversationMaster',
		)
	);

 	public function getActionId()
 	{
 		return 'receiveConversation';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_receiveConversation';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_receiveConversation_description';
 	}
}