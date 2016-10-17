<?php

class Brivium_KeyCode_EventListeners_Listener extends Brivium_BriviumHelper_EventListeners
{
	public static function navigationTabs(&$extraTabs, $selectedTabId)
	{
		$extraTabs['BRKC_keyCode'] = array(
			'title' => new XenForo_Phrase('BRKC_key_code'),
			'href' => XenForo_Link::buildPublicLink('full:key-code'),
			'position' => 'middle',
			'linksTemplate' => 'BRKC_key_code_tab_links'
		);
	}

	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
    {
		switch ($hookName) {
			case 'BRC_navigation_tabs_credits':
				$ourTemplate = $template->create('BRKC_navigation_tabs_credits', $template->getParams());
				$contents .= $ourTemplate->render();
				break;
		}
    }

	public static function brcActionHandler(array &$actions)
	{
		$actions['BRKC_PurchaseKeyCode'] = 'Brivium_KeyCode_ActionHandler_PurchaseKeyCode_ActionHandler';
	}
}