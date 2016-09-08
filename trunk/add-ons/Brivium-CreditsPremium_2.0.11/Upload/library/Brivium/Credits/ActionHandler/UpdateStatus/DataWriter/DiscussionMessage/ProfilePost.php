<?php

class Brivium_Credits_ActionHandler_UpdateStatus_DataWriter_DiscussionMessage_ProfilePost extends XFCP_Brivium_Credits_ActionHandler_UpdateStatus_DataWriter_DiscussionMessage_ProfilePost
{
	protected function _messagePostSave()
	{
		if ($this->isInsert() && $this->isStatus())
		{
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('updateStatus', $this->get('user_id'));
		}
		return parent::_messagePostSave();
	}
}