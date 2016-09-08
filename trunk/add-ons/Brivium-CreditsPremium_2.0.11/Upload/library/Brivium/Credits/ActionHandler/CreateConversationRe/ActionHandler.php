<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_CreateConversationRe_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 161;
	protected $_contentRoute = 'conversations';
	protected $_contentIdKey = 'conversation_id';

	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_ConversationMaster' => 'Brivium_Credits_ActionHandler_CreateConversationRe_DataWriter_ConversationMaster'
		)
	);

 	public function getActionId()
 	{
 		return 'createConversationRe';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_createConversationRe';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_createConversationRe_description';
 	}
}