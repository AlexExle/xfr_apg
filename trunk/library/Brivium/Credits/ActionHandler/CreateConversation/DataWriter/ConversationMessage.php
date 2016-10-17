<?php

class Brivium_Credits_ActionHandler_CreateConversation_DataWriter_ConversationMessage extends XFCP_Brivium_Credits_ActionHandler_CreateConversation_DataWriter_ConversationMessage
{
	protected function _postSave()
	{
		if ($this->isInsert() && !$this->getOption(self::OPTION_UPDATE_CONVERSATION))
		{
			$dataCredit = array(
				'content_id' 	=>	$this->get('conversation_id'),
				'content_type'	=>	'conversation',
			);
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('createConversation', $this->get('user_id'), $dataCredit);
		}
		return parent::_postSave();
	}
}