<?php

class Brivium_Credits_ActionHandler_FacebookDisassociate_Model_UserExternal extends XFCP_Brivium_Credits_ActionHandler_FacebookDisassociate_Model_UserExternal
{
	public function deleteExternalAuthAssociationForUser($provider, $userId)
	{
		if($provider=='facebook'){
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('facebookDisassociate',$userId);
		}
		return parent::deleteExternalAuthAssociationForUser($provider, $userId);
	}
}