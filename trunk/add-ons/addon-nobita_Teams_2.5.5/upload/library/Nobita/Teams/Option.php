<?php

abstract class Nobita_Teams_Option
{
	protected static $_caches = array();
	public static function get($key, $subKey = null)
	{
		$cacheKey = $key.$subKey;
		if (array_key_exists($cacheKey, self::$_caches))
		{
			return self::$_caches[$cacheKey];
		}

		self::$_caches[$cacheKey] = XenForo_Application::getOptions()->get(sprintf('Teams_%s', $key), $subKey);
		return Nobita_Teams_Option::get($key, $subKey);
	}

	public static function getTabsSupported($explain = true)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Tab')->getAllTabs($explain);
	}

	public static function getTimeMap()
	{
		$map = array();
		$timeformat = self::get('timeformat');

		switch(intval($timeformat))
		{
			case 12:
				for ($i = 0; $i < 24; $i++)
				{
					if ($i < 12)
					{
						if (0 === $i)
						{
							$map[$i . ':00'] = '12:00 AM';
							$map[$i . ':30'] = '12:30 AM';
						}
						else
						{
							$map[$i . ':00'] = $i . ':00 AM';
							$map[$i . ':30'] = $i . ':30 AM';
						}
					}
					else
					{

						if (12 == $i)
						{
							$map[$i . ':00'] = $i . ':00 PM';
							$map[$i . ':30'] = $i . ':30 PM';
						}
						else
						{
							$map[$i . ':00'] = ($i - 12) . ':00 PM';
							$map[$i . ':30'] = ($i - 12) . ':30 PM';
						}
					}
				}
				break;
			case 24:
				for ($i = 0; $i < 24; $i++)
				{
					$map[$i . ':00'] = $i . ':00';
					$map[$i . ':30'] = $i . ':30';
				}
				break;
		}

		return $map;
	}

	public static function XenMedia_renderOption(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		if (!Nobita_Teams_AddOnChecker::getInstance()->exists('XenGallery'))
		{
			$preparedOption['formatParams'] = array(
				array(
					'value' => 0,
					'label' => '(' . new XenForo_Phrase('unspecified') . ')',
					'selected' =>  true,
					'depth' => 0
				)
			);
		}
		else
		{
			$categoryModel = Nobita_Teams_Container::getModel('XenGallery_Model_Category');
			$categories = $categoryModel->getAllCategories();

			$options[0] = array(
				'value' => 0,
				'label' => '(' . new XenForo_Phrase('unspecified') . ')',
				'selected' =>  true,
				'depth' => 0
			);
			foreach($categories as $category)
			{
				$options[$category['category_id']] = array(
					'value' => $category['category_id'],
					'label' => $category['category_title'],
					'selected' => false,
					'depth' => $category['depth']
				);
			}

			$preparedOption['formatParams'] = $options;
		}

		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal(
			'option_list_option_select', $view, $fieldPrefix, $preparedOption, $canEdit
		);
	}

	public static function renderRulesOption(XenForo_View $view, $fieldPrefix, $preparedOption, $canEdit)
	{
		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption' => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		$editor = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$view, 'default_rules', $preparedOption['option_value'], array('extraClass' => 'NoAutoComplete')
		);

		return $view->createTemplateObject('Team_option_rules', array(
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption' => $preparedOption,
			'formatParams' => $preparedOption['formatParams'],
			'editLink' => $editLink,

			'editor' => $editor
		));
	}


}
