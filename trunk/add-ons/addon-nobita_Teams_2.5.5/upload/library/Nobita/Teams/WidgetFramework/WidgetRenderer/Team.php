<?php

class Nobita_Teams_WidgetFramework_WidgetRenderer_Team extends WidgetFramework_WidgetRenderer
{
	public function extraPrepareTitle(array $widget)
	{
		if (empty($widget['title']))
		{
			if (empty($widget['options']['type']))
			{
				$widget['options']['type'] = 'new';
			}

			switch ($widget['options']['type'])
			{
				case 'suggested_groups':
					return new XenForo_Phrase('Teams_suggested_groups');
				case 'most_member':
					return new XenForo_Phrase('Teams_most_members');
				case 'new':
				default:
					return new XenForo_Phrase('Teams_new_teams');
			}
		}

		return parent::extraPrepareTitle($widget);
	}

	protected function _getConfiguration()
	{
		return array(
			'name' => '[Nobita] Social Groups (Teams): Groups',
			'options' => array(
				'limit' => XenForo_Input::UINT,
				'type' => XenForo_Input::STRING
			),
			'useCache' => true,
			'cacheSeconds' => 3600*2, // cache for 2 hours
		);
	}

	protected function _validateOptionValue($optionKey, &$optionValue)
	{
		switch ($optionKey)
		{
			case 'limit':
				if (empty($optionValue))
				{
					$optionValue = 5;
				}
				break;
			case 'type':
				if (empty($optionValue))
				{
					$optionValue = 'new';
				}
				break;
		}

		return parent::_validateOptionValue($optionKey, $optionValue);
	}

	protected function _getOptionsTemplate()
	{
		return 'Team_widget_options_groups';
	}

	protected function _getRenderTemplate(array $widget, $positionCode, array $params)
	{
		return 'Team_widget_suggestions';
	}

	protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $renderTemplateObject)
	{
		if (empty($widget['options']['limit']))
		{
			$widget['options']['limit'] = 5;
		}
		if (empty($widget['options']['type']))
		{
			$widget['options']['type'] = 'new';
		}

		$viewableCategories = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category')->getViewableCategories();
		$viewableCategoryIds = array_keys($viewableCategories);

		$conditions = array(
			'deleted' => false,
			'moderated' => false,
			'team_category_id' => $viewableCategoryIds
		);

		$fetchOptions = array(
			'join' => Nobita_Teams_Model_Team::FETCH_PROFILE
					| Nobita_Teams_Model_Team::FETCH_PRIVACY
					| Nobita_Teams_Model_Team::FETCH_CATEGORY,
			'limit' => $widget['options']['limit']*3
		);

		switch($widget['options']['type'])
		{
			case 'new':
				$fetchOptions['direction'] = 'desc';

				$teams = Nobita_Teams_Helper_Widget::getMostGroupsWidgetByType(
					Nobita_Teams_Helper_Widget::NEW_GROUPS, $conditions, $fetchOptions
				);
				break;
			case 'most_member':
				$fetchOptions['direction'] = 'desc';

				$teams = Nobita_Teams_Helper_Widget::getMostGroupsWidgetByType(
					Nobita_Teams_Helper_Widget::MOST_MEMBERS, $conditions, $fetchOptions
				);
				break;
			case 'suggested_groups':
				$teams = Nobita_Teams_Helper_Widget::getSuggestedGroupsWidget($widget['options']['limit']*3, $viewableCategoryIds);
				break;
			default:
				$teams = array();
		}

		if (count($teams) >  $widget['options']['limit'])
		{
			// too many teams (because we fetched 3 times as needed)
			$teams = array_slice($teams, 0, $widget['options']['limit'], true);
		}

		$renderTemplateObject->setParam('teams', $teams);
		return $renderTemplateObject->render();
	}

	public function useUserCache(array $widget)
	{
		if (!empty($widget['options']['as_guest']))
		{
			// using guest permission
			// there is no reason to use the user cache
			return false;
		}

		return parent::useUserCache($widget);
	}

}
