<?php

class Brivium_Credits_Option_ActionActive
{
	/**
	 * Renders the currency chooser option as a <select>.
	 *
	 * @param XenForo_View $view View object
	 * @param string $fieldPrefix Prefix for the HTML form field name
	 * @param array $preparedOption Prepared option info
	 * @param boolean $canEdit True if an "edit" link should appear
	 *
	 * @return XenForo_Template_Abstract Template object
	 */
	public static function renderSelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$preparedOption['inputClass'] = 'autoSize';

		return self::_render('BRC_option_list_option_actionActive', $view, $fieldPrefix, $preparedOption, $canEdit);
	}

	/**
	 * Renders the currency chooser option.
	 *
	 * @param string Name of template to render
	 * @param XenForo_View $view View object
	 * @param string $fieldPrefix Prefix for the HTML form field name
	 * @param array $preparedOption Prepared option info
	 * @param boolean $canEdit True if an "edit" link should appear
	 *
	 * @return XenForo_Template_Abstract Template object
	 */
	protected static function _render($templateName, XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();

		$formatParams = array();
		if(!$preparedOption['option_value']){
			$preparedOption['option_value'] = array();
		}
		foreach ($actions AS $action)
		{
			$formatParams[$action['action_id']] = array(
				'value' => $action['action_id'],
				'label' => $action['title'],
				'selected' => (in_array($action['action_id'], $preparedOption['option_value'])),
			);
		}
		$preparedOption['formatParams'] = $formatParams;
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal(
			$templateName, $view, $fieldPrefix, $preparedOption, $canEdit
		);
	}
}