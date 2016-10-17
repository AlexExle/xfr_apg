<?php

class Brivium_Credits_ActionHandler_GoogleDisassociate_Model_UserExternal extends XFCP_Brivium_Credits_ActionHandler_GoogleDisassociate_Model_UserExternal
{
	public function deleteExternalAuthAssociationForUser($provider, $userId)
	{
		if($provider=='google'){
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('googleDisassociate',$userId);
		}
		return parent::deleteExternalAuthAssociationForUser($provider, $userId);
	}
}