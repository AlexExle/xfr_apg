<?php
class Brivium_Credits_ActionHandler_Follow_DataWriter_Follower extends XFCP_Brivium_Credits_ActionHandler_Follow_DataWriter_Follower
{
	/**
	* Post-save handler.
	*/
	protected function _postSave()
	{
		$dataCredit = array(
			'user_action_id' 	=>	$this->get('follow_user_id'),
		);
		$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('follow', $this->get('user_id'), $dataCredit);
		return parent::_postSave();
	}
}