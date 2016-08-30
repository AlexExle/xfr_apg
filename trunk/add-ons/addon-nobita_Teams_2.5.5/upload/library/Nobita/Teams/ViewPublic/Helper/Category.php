<?php

class Nobita_Teams_ViewPublic_Helper_Category
{
	private function __construct() {}

	public static function renderCategoryTree(XenForo_View $view, $parentCategoryId, array $categoriesGrouped, $level = 1, $selectedCategoryId = 0)
	{
		$renderedCategories = array();

		if (! empty($categoriesGrouped[$parentCategoryId]))
		{
			$nextLevel = $level + 1;

			foreach($categoriesGrouped[$parentCategoryId] as $key => $category)
			{
				$renderedChildren = self::renderCategoryTree(
					$view, $category['team_category_id'], $categoriesGrouped, $nextLevel, $selectedCategoryId
				);
				
				if ($category['team_category_id'] == $selectedCategoryId)
				{
					$category['selected'] = true;
				}

				$renderedCategories[$key] = self::renderCategoryHtml($view, $category, $renderedChildren, $level);
			}
		}

		return $renderedCategories;
	}

	public static function renderCategoryTreeFromDisplayArray(XenForo_View $view, array $categoryList, $level = 1)
	{
		if ($categoryList)
		{
			return self::renderCategoryTree(
				$view, 
				$categoryList['parentCategoryId'], 
				$categoryList['categoriesGrouped'], 
				$level, 
				isset($categoryList['selectedCategoryId']) ? $categoryList['selectedCategoryId'] : 0
			);
		}
		else
		{
			return array();
		}
	}

	public static function renderCategoryHtml(XenForo_View $view, array $category, array $renderedChildren, $level)
	{
		$templateLevel = ($level <= 2 ? $level : 'n');

		$openCategoryIds = XenForo_Helper_Cookie::getCookie('group_collapseCatIds');
		$openCategoryIds = array_map('intval', explode(',', $openCategoryIds));

		if (isset($openCategoryIds[0]) && $openCategoryIds[0] === 0)
		{
			unset($openCategoryIds[0]);
		}

		$openCategoryIds = array_values($openCategoryIds);
		$hasOpenCategory = false;
		if (!Nobita_Teams_Option::get('collapseCategoriesOnLoad'))
		{
			$hasOpenCategory = true;
		}
		else
		{
			$hasOpenCategory = in_array($category['team_category_id'], $openCategoryIds);
		}

		return $view->createTemplateObject('Team_category_level_' . $templateLevel, array(
			'level' => $level,
			'category' => $category,
			'renderedChildren' => $renderedChildren,
			'openCategoryIds' => $openCategoryIds,
			'hasOpenCategory' => $hasOpenCategory
		));
	}
}