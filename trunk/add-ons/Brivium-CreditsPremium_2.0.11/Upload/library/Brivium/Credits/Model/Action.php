<?php

class Brivium_Credits_Model_Action extends XenForo_Model
{
	public function getActionsInAddOn($addOnId)
	{
		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();
		$groupedActions = $this->groupedActionsByAddOn();
		return isset($groupedActions[$addOnId])?$groupedActions[$addOnId]:array();
	}
	public function groupedActionsByAddOn()
	{
		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();
		$groupedActions = array();
		foreach($actions AS $action){
			if(empty($groupedActions[$action['addon_id']])){
				$groupedActions[$action['addon_id']] = array();
			}
			$groupedActions[$action['addon_id']][$action['action_id']] = $action;
		}
		return $groupedActions;
	}

	public function appendActionsAddOnXml(DOMElement $rootNode, $addOnId)
	{
		$actions = $this->getActionsInAddOn($addOnId);
		$this->appendActionsXml($rootNode,$actions);
	}

	public function appendActionsXml(DOMElement $rootNode,array $actions)
	{
		$document = $rootNode->ownerDocument;
		foreach ($actions AS $action)
		{
			$actionNode = $document->createElement('action');
			$actionNode->setAttribute('action_id', $action['action_id']);
			$actionNode->setAttribute('display_order', $action['display_order']);
			$actionNode->setAttribute('revert', isset($action['revert'])?$action['revert']:0);
			$actionNode->setAttribute('multiple_event', isset($action['allow_multiple_event'])?$action['allow_multiple_event']:0);
			$actionNode->setAttribute('allow_negative', isset($action['allow_negative'])?$action['allow_negative']:0);
			$actionNode->setAttribute('negative_handle', isset($action['negative_handle'])?$action['negative_handle']:'');

			if($action['edit_template']=='BRC_action_edit_default'){
				$action['edit_template'] = '';
			}
			$action['edit_template'] = str_replace('BRC_action_edit_template_', '', $action['edit_template']);
			XenForo_Helper_DevelopmentXml::createDomElements($actionNode, array(
				'addon_id' => $action['addon_id'],
				'template' => $action['edit_template'],
			));

			$titleNode = $document->createElement('title');
			$titleNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, new XenForo_Phrase($action['title'])));
			$actionNode->appendChild($titleNode);

			$revertedTitleNode = $document->createElement('reverted_title');
			$revertedTitleNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, ''));
			$actionNode->appendChild($revertedTitleNode);

			$explainNode = $document->createElement('explain');
			$explainNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, new XenForo_Phrase($action['description'])));
			$actionNode->appendChild($explainNode);

			$rootNode->appendChild($actionNode);
		}
	}
}