<?php

class Brivium_Credits_ActionHandler_GetFollowerRe_DataWriter_Follower extends XFCP_Brivium_Credits_ActionHandler_GetFollowerRe_DataWriter_Follower
{
	protected function _postDelete()
	{
		$dataCredit = array(
			'user_action_id' 	=>	$this->get('user_id'),
		);
		$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('getFollowerRe', $this->get('follow_user_id'), $dataCredit);
		return parent::_postDelete();
	}
}