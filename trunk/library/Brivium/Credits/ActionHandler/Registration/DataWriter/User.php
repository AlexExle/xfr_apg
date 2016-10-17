<?php
class Brivium_Credits_ActionHandler_Registration_DataWriter_User extends XFCP_Brivium_Credits_ActionHandler_Registration_DataWriter_User
{
	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		if($this->isInsert()){
			$userId = $this->get('user_id');
			$dataCredit = array(
				'ignore_permission' => true
			);
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('registration', $userId, $dataCredit);
		}
		return parent::_postSave();
	}
}