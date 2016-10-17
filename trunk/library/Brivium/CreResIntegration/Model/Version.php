<?php

class Brivium_CreResIntegration_Model_Version extends XFCP_Brivium_CreResIntegration_Model_Version
{
	public function logVersionDownload(array $version, $userId)
	{
		if(!isset($GLOBALS['BRCRI_CPRactionDownload'])){
			return parent::logVersionDownload($version, $userId);
		}
		$GLOBALS['BRCRI_CPRactionDownload'] = $version;
		return true;
	}
	/**
	 * @return Brivium_CreResIntegration_Model_Purchased
	 */
	protected function _getResourcePurchasedModel()
	{
		return $this->getModelFromCache('Brivium_CreResIntegration_Model_Purchased');
	}
}