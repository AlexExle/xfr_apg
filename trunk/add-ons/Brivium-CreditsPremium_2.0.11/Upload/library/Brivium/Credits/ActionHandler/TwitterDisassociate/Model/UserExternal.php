<?php

class Brivium_Credits_ActionHandler_TwitterDisassociate_Model_UserExternal extends XFCP_Brivium_Credits_ActionHandler_TwitterDisassociate_Model_UserExternal
{
	public function deleteExternalAuthAssociationForUser($provider, $userId)
	{
		if($provider=='twitter'){
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('twitterDisassociate',$userId);
		}
		return parent::deleteExternalAuthAssociationForUser($provider, $userId);
	}
}