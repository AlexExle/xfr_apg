<?php

class Brivium_Credits_ActionHandler_FacebookAssociate_Model_UserExternal extends XFCP_Brivium_Credits_ActionHandler_FacebookAssociate_Model_UserExternal
{
	public function updateExternalAuthAssociation($provider, $providerKey, $userId, array $extra = null)
	{
		if($provider=='facebook'){
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('facebookAssociate', $userId);
		}
		return parent::updateExternalAuthAssociation($provider, $providerKey, $userId, $extra);
	}
}