<?php

class EWRporta2_Widget_Option_RandomQuote
{
	public static function renderOption(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$values = $preparedOption['option_value'];

		$quotes = array();
		if (!empty($values))
		{
			foreach ($values AS $value)
			{
				$quotes[] = $value;
			}
		}

		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption' => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		return $view->createTemplateObject('EWRwidget_RandomQuote_Option', array(
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption' => $preparedOption,
			'formatParams' => $preparedOption['formatParams'],
			'editLink' => $editLink,
			'quotes' => $quotes,
			'nextCounter' => count($quotes),
		));
	}

	public static function verifyOption(array &$options, XenForo_DataWriter $dw, $fieldName)
	{
		foreach ($options AS $key => &$option)
		{
			if (empty($option['quote']))
			{
				unset($options[$key]);
			}
		}

		return true;
	}
}