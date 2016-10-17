<?php

class EWRporta2_Widget_Option_ResourceTop
{
	public static function renderCategorySelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$preparedOption['formatParams'] = self::getCategoryOptions(
			$preparedOption['option_value'],
			sprintf('(%s)', new XenForo_Phrase('unspecified'))
		);

		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal(
			'option_list_option_select', $view, $fieldPrefix, $preparedOption, $canEdit
		);
	}
	
	public static function getCategoryOptions($selectedCategory, $unspecifiedPhrase = false)
	{
		$listsModel = XenForo_Model::create('XenResource_Model_Category');

		$categories = $listsModel->getAllCategories();
		$options = array();
		
		foreach ($categories AS $category)
		{
			$options[] = array(
				'label' => $category['category_title'],
				'value' => $category['resource_category_id'],
				'selected' => ($selectedCategory == $category['resource_category_id'])
			);
		}

		if ($unspecifiedPhrase)
		{
			$options = array_merge(array(array
			(
				'label' => $unspecifiedPhrase,
				'value' => 0,
				'selected' => ($selectedCategory == 0)
			)), $options);
		}

		return $options;
	}
}