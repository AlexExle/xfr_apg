<?php

class Brivium_Credits_ActionHandler_ProfilePost_DataWriter_DiscussionMessage_ProfilePost extends XFCP_Brivium_Credits_ActionHandler_ProfilePost_DataWriter_DiscussionMessage_ProfilePost
{
	protected function _messagePostSave()
	{
		if ($this->isInsert() && !$this->isStatus())
		{
			$dataCredit = array(
				'extraData'	=>	array('user_id'=>$this->get('profile_user_id'))
			);
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('profilePost', $this->get('user_id'), $dataCredit);
		}
		return parent::_messagePostSave();
	}
}