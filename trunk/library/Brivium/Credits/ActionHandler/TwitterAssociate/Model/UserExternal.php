<?php

class Brivium_Credits_ActionHandler_TwitterAssociate_Model_UserExternal extends XFCP_Brivium_Credits_ActionHandler_TwitterAssociate_Model_UserExternal
{
	public function updateExternalAuthAssociation($provider, $providerKey, $userId, array $extra = null)
	{
		if($provider=='twitter'){
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('twitterAssociate',$userId);
		}
		return parent::updateExternalAuthAssociation($provider, $providerKey, $userId, $extra);
	}
}