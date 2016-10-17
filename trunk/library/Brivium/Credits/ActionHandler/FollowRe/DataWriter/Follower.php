<?php
class Brivium_Credits_ActionHandler_FollowRe_DataWriter_Follower extends XFCP_Brivium_Credits_ActionHandler_FollowRe_DataWriter_Follower
{
	protected function _postDelete()
	{
		$dataCredit = array(
			'user_action_id' 	=>	$this->get('follow_user_id'),
		);
		$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('followRe',$this->get('user_id'),$dataCredit);
		return parent::_postDelete();
	}
}