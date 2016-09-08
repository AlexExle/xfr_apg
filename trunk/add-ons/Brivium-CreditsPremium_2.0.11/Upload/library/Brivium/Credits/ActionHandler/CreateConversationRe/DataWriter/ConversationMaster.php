<?php

class Brivium_Credits_ActionHandler_CreateConversationRe_DataWriter_ConversationMaster extends XFCP_Brivium_Credits_ActionHandler_CreateConversationRe_DataWriter_ConversationMaster
{
	protected function _postDelete()
	{
		$dataCredit = array(
			'content_id' 	=>	$this->get('conversation_id'),
			'content_type'	=>	'conversation',
		);
		$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('createConversationRe', $this->get('user_id'), $dataCredit);
		return parent::_postDelete();
	}
}