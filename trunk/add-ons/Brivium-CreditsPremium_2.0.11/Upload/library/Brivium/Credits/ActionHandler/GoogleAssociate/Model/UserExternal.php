<?php

class Brivium_Credits_ActionHandler_GoogleAssociate_Model_UserExternal extends XFCP_Brivium_Credits_ActionHandler_GoogleAssociate_Model_UserExternal
{
	public function updateExternalAuthAssociation($provider, $providerKey, $userId, array $extra = null)
	{
		if($provider=='google'){
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('googleAssociate',$userId);
		}
		return parent::updateExternalAuthAssociation($provider, $providerKey, $userId, $extra);
	}
}