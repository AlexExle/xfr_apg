<?php

class Nobita_Teams_ViewPublic_Helper_Team
{
	public static function getTeamFieldTitle($fieldId)
	{
		return new XenForo_Phrase("Teams_team_field_$fieldId");
	}

	/**
	 * Gets the HTML value of the team field.
	 *
	 * @param array $field
	 * @param mixed $value Value of the field; if null, pulls from field_value in field
	 */
	public static function getTeamFieldValueHtml(array $team, $field, $value = null)
	{

		if (!is_array($field))
		{
			$fields = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Field')->getTeamFieldCache();
			if (!isset($fields[$field]))
			{
				return '';
			}

			$field = $fields[$field];
		}

		if (!XenForo_Application::isRegistered('view'))
		{
			return 'No view registered';
		}

		if ($value === null && isset($field['field_value']))
		{
			$value = $field['field_value'];
		}

		if ($value === '' || $value === null)
		{
			return '';
		}

		$multiChoice = false;
		$choice = '';
		$view = XenForo_Application::get('view');

		switch ($field['field_type'])
		{
			case 'radio':
			case 'select':
				$choice = $value;
				$value = new XenForo_Phrase("Teams_team_field_$field[field_id]_choice_$value");
				$value->setPhraseNameOnInvalid(false);
				$valueRaw = $value;
				break;

			case 'checkbox':
			case 'multiselect':
				$multiChoice = true;
				if (!is_array($value) || count($value) == 0)
				{
					return '';
				}

				$newValues = array();
				foreach ($value AS $id => $choice)
				{
					$phrase = new XenForo_Phrase("Teams_team_field_$field[field_id]_choice_$choice");
					$phrase->setPhraseNameOnInvalid(false);

					$newValues[$choice] = $phrase;
				}
				$value = $newValues;
				$valueRaw = $value;
				break;

			case 'bbcode':
				$valueRaw = htmlspecialchars(XenForo_Helper_String::censorString($value));

				$bbCodeParser = new XenForo_BbCode_Parser(
					XenForo_BbCode_Formatter_Base::create('Base', array('view' => $view))
				);
				$value = $bbCodeParser->render($value, array(
					'noFollowDefault' => true
				));
				break;

			case 'textbox':
			case 'textarea':
			default:
				$valueRaw = htmlspecialchars(XenForo_Helper_String::censorString($value));
				$value = XenForo_Template_Helper_Core::callHelper('bodytext', array($value));
		}

		if (!empty($field['display_template']))
		{
			if ($multiChoice && is_array($value))
			{
				foreach ($value AS $choice => &$thisValue)
				{
					$thisValue = strtr($field['display_template'], array(
						'{$fieldId}' => $field['field_id'],
						'{$value}' => $thisValue,
						'{$valueRaw}' => $thisValue,
						'{$valueUrl}' => urlencode($thisValue),
						'{$choice}' => $choice,
					));
				}
			}
			else
			{
				$value = strtr($field['display_template'], array(
					'{$fieldId}' => $field['field_id'],
					'{$value}' => $value,
					'{$valueRaw}' => $valueRaw,
					'{$valueUrl}' => urlencode($value),
					'{$choice}' => $choice,
				));
			}
		}

		if (is_array($value))
		{
			if (empty($value))
			{
				return '';
			}
			return '<ul class="plainList"><li>' . implode('</li><li>', $value) . '</li></ul>';
		}

		return $value;
	}

	/**
	 * Add team field HTML keys to the given list of fields.
	 *
	 * @param XenForo_View $view
	 * @param array $fields
	 * @param array $values Field values; pulls from field_value in fields if not specified here
	 */
	public static function addTeamFieldsValueHtml(XenForo_View $view, array $fields, array $values = array())
	{
		foreach ($fields AS &$field)
		{
			$field['fieldValueHtml'] = self::getTeamFieldValueHtml(
				$field,
				isset($values[$field['field_id']]) ? $values[$field['field_id']] : null
			);
		}

		return $fields;
	}
}