<?php

class EWRporta2_Widget_Option_Affiliates
{
	public static function renderOption(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$values = $preparedOption['option_value'];

		$choices = array();
		if (!empty($values))
		{
			foreach ($values AS $value)
			{
				$choices[] = $value;
			}
		}

		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption' => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		return $view->createTemplateObject('EWRwidget_Affiliates_Option', array(
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption' => $preparedOption,
			'formatParams' => $preparedOption['formatParams'],
			'editLink' => $editLink,
			'choices' => $choices,
			'nextCounter' => count($choices),
		));
	}

	public static function verifyOption(array &$options, XenForo_DataWriter $dw, $fieldName)
	{
		foreach ($options AS $key => &$option)
		{
			if (empty($option['title']) || empty($option['link']) || empty($option['image']))
			{
				unset($options[$key]);
			}
		}

		return true;
	}
}