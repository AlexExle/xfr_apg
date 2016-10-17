<?php

class Brivium_Credits_ActionHandler_GetProfilePost_DataWriter_DiscussionMessage_ProfilePost extends XFCP_Brivium_Credits_ActionHandler_GetProfilePost_DataWriter_DiscussionMessage_ProfilePost
{
	protected function _messagePostSave()
	{
		if ($this->isInsert() && !$this->isStatus())
		{
			$dataCredit = array(
				'user_action_id' 	=>	$this->get('user_id'),
				'content_id' 		=>	$this->get('profile_post_id'),
				'content_type'		=>	'profile_post',
				'extraData' 		=>	array('user_id'=>$this->get('user_id'))
			);
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('getProfilePost', $this->get('profile_user_id'), $dataCredit);
		}
		return parent::_messagePostSave();
	}
}