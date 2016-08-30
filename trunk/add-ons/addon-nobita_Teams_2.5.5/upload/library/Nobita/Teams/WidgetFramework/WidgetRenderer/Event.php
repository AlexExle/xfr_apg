<?php

class Nobita_Teams_WidgetFramework_WidgetRenderer_Event extends WidgetFramework_WidgetRenderer
{
	public function extraPrepareTitle(array $widget)
	{
		if (empty($widget['title']))
		{
			if (empty($widget['options']['type']))
			{
				$widget['options']['type'] = 'today';
			}

			switch ($widget['options']['type'])
			{
				case 'upcoming':
					return new XenForo_Phrase('Teams_events_upcoming');
				case 'today':
				default:
					return new XenForo_Phrase('Teams_events_today');
			}
		}

		return parent::extraPrepareTitle($widget);
	}

	protected function _getConfiguration()
	{
		return array(
			'name' => '[Nobita] Social Groups (Teams): Events',
			'options' => array(
				'limit' => XenForo_Input::UINT,
				'type' => XenForo_Input::STRING
			),
			'useCache' => true,
			'cacheSeconds' => 300
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
					$optionValue = 'today';
				}
				break;
		}

		return parent::_validateOptionValue($optionKey, $optionValue);
	}

	protected function _getOptionsTemplate()
	{
		return 'Team_widget_options_events';
	}

	protected function _getRenderTemplate(array $widget, $positionCode, array $params)
	{
		return 'Team_widget_events';
	}

	protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $renderTemplateObject)
	{
		if (empty($widget['options']['limit']))
		{
			$widget['options']['limit'] = 5;
		}

		$type = $widget['options']['type'];
		$method = sprintf('getEvents%sWidget', ucfirst($type));

		$class = XenForo_Application::resolveDynamicClass('Nobita_Teams_Helper_Event');
		if (method_exists($class, $method))
		{
			$events = call_user_func_array(array($class, $method), array($widget['options']['limit']));
		}
		else
		{
			$events = array();
		}

		$renderTemplateObject->setParam('events', $events);
		return $renderTemplateObject->render();
	}



}
