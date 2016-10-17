<?php

class Brivium_Credits_ActionHandler_UploadAvatar_DataWriter_User extends XFCP_Brivium_Credits_ActionHandler_UploadAvatar_DataWriter_User
{

	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		$userId = $this->get('user_id');
		if($this->isChanged('avatar_date') && $this->get('avatar_date')){
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('uploadAvatar',$userId);
		}
		return parent::_postSave();
	}
}