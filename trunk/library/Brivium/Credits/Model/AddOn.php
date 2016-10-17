<?php

class Brivium_Credits_Model_AddOn extends XFCP_Brivium_Credits_Model_AddOn
{
	public function getAddOnXml(array $addOn)
	{
		$document = parent::getAddOnXml($addOn);
		if ($addOn['addon_id'] != 'Brivium_Credits') {
			$rootNode = $document->documentElement;
			$addOnId = $addOn['addon_id'];
			$dataNode = $rootNode->appendChild($document->createElement('brc_actions'));
			$this->getModelFromCache('Brivium_Credits_Model_Action')->appendActionsAddOnXml($dataNode, $addOnId);
		}
		return $document;
	}
}