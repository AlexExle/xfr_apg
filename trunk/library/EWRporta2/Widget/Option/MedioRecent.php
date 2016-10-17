<?php

class EWRporta2_Widget_Option_MedioRecent
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
		$listsModel = XenForo_Model::create('EWRmedio_Model_Lists');

		$categories = $listsModel->getCategories();
		$options = array();
		
		foreach ($categories AS $category)
		{
			$options[] = array(
				'label' => $category['category_name'],
				'value' => $category['category_id'],
				'selected' => ($selectedCategory == $category['category_id'])
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