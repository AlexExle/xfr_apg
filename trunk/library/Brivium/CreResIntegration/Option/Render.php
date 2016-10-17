<?php

/**
 * Helper for choosing what happens by default to spam threads.
 *
 * @package XenForo_Options
 */
abstract class Brivium_CreResIntegration_Option_Render
{
	public static function renderOption(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$value = $preparedOption['option_value'];
		$seleted = 0;
		if($value)$seleted = -1;
		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption' => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));
		$categoryModel = XenForo_Model::create('XenResource_Model_Category');
		$categories = $categoryModel->prepareCategories($categoryModel->getViewableCategories());
		
		$options = array();
		$options[0] = array(
			'value' => 0,
			'label' => sprintf('(%s)', new XenForo_Phrase('unspecified')),
			'selected' => false,
			'depth' => 0,
		);
		foreach ($categories AS $categoryId => $category)
		{
			$category['depth'] += 1;

			$options[$categoryId] = array(
				'value' => $categoryId,
				'label' => $category['category_title'],
				'depth' => $category['depth']
			);
		}
		return $view->createTemplateObject('BRCRI_option_template_categorySelector', array(
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption' => $preparedOption,
			'formatParams' => $options,
			'editLink' => $editLink
		));
	}
}