<?php

class Nobita_Teams_Model_Field extends Nobita_Teams_Model_Abstract
{
	/**
	 * Gets a custom team field by ID.
	 *
	 * @param string $fieldId
	 *
	 * @return array|false
	 */
	public function getTeamFieldById($fieldId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_team_field
			WHERE field_id = ?
		', $fieldId);
	}

	public function getTeamFieldsInCategories(array $categoryIds)
	{
		if (!$categoryIds)
		{
			return array();
		}

		$db = $this->_getDb();

		return $db->fetchAll("
			SELECT field.*, field_category.team_category_id
			FROM xf_team_field AS field
			INNER JOIN xf_team_field_category AS field_category ON
				(field.field_id = field_category.field_id)
			WHERE field_category.team_category_id IN (" . $db->quote($categoryIds) . ")
			ORDER BY field.display_order
		");
	}

	public function getFieldIdsInCategory($categoryId)
	{
		return $this->_getDb()->fetchPairs("
			SELECT field_id, field_id
			FROM xf_team_field_category
			WHERE team_category_id = ?
		", $categoryId);
	}

	public function getTeamFieldsForEdit($categoryId, $teamId = 0)
	{
		$fetchOptions = array(
			'categoryId' => $categoryId,
			'valueTeamId' => $teamId
		);
		return $this->getTeamFields(array(), $fetchOptions);
	}

	/**
	 * Gets custom team fields that match the specified criteria.
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array [field id] => info
	 */
	public function getTeamFields(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause = $this->prepareTeamFieldConditions($conditions, $fetchOptions);
		$joinOptions = $this->prepareTeamFieldFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT team_field.*
				' . $joinOptions['selectFields'] . '
			FROM xf_team_field AS team_field
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause . '
			ORDER BY team_field.display_group, team_field.display_order
		', 'field_id');
	}

	/**
	 * Prepares a set of conditions to select fields against.
	 *
	 * @param array $conditions List of conditions.
	 * @param array $fetchOptions The fetch options that have been provided. May be edited if criteria requires.
	 *
	 * @return string Criteria as SQL for where clause
	 */
	public function prepareTeamFieldConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		return $this->getConditionsForClause($sqlConditions);
	}

	/**
	 * Prepares join-related fetch options.
	 *
	 * @param array $fetchOptions
	 *
	 * @return array Containing 'selectFields' and 'joinTables' keys.
	 */
	public function prepareTeamFieldFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		$db = $this->_getDb();

		if (!empty($fetchOptions['categoryId']))
		{
			$joinTables .= '
				INNER JOIN xf_team_field_category AS field_category ON
					(field_category.field_id = team_field.field_id AND field_category.team_category_id = ' . $db->quote($fetchOptions['categoryId']) . ')';
		}

		if (!empty($fetchOptions['valueTeamId']))
		{
			$selectFields .= ',
				field_value.field_value';
			$joinTables .= '
				LEFT JOIN xf_team_field_value AS field_value ON
					(field_value.field_id = team_field.field_id AND field_value.team_id = ' . $db->quote($fetchOptions['valueTeamId']) . ')';
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}

	/**
	 * Groups team fields by their display group.
	 *
	 * @param array $fields
	 *
	 * @return array [display group][key] => info
	 */
	public function groupTeamFields(array $fields)
	{
		$return = array();

		foreach ($fields AS $fieldId => $field)
		{
			$return[$field['display_group']][$fieldId] = $field;
		}

		return $return;
	}

	/**
	 * Prepares a team field for display.
	 *
	 * @param array $field
	 * @param boolean $getFieldChoices If true, gets the choice options for this field (as phrases)
	 * @param mixed $fieldValue If not null, the value for the field; if null, pulled from field_value
	 * @param boolean $valueSaved If true, considers the value passed to be saved; should be false on registration
	 *
	 * @return array Prepared field
	 */
	public function prepareTeamField(array $field, $getFieldChoices = false, $fieldValue = null, $valueSaved = true)
	{
		$field['isMultiChoice'] = ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect');
		$field['isChoice'] = ($field['isMultiChoice'] || $field['field_type'] == 'radio' || $field['field_type'] == 'select');

		if ($fieldValue === null && isset($field['field_value']))
		{
			$fieldValue = $field['field_value'];
		}
		if ($field['isMultiChoice'])
		{
			if (is_string($fieldValue))
			{
				$fieldValue = @unserialize($fieldValue);
			}
			else if (!is_array($fieldValue))
			{
				$fieldValue = array();
			}
		}
		$field['field_value'] = $fieldValue;

		$field['title'] = new XenForo_Phrase($this->getTeamFieldTitlePhraseName($field['field_id']));
		$field['description'] = new XenForo_Phrase($this->getTeamFieldDescriptionPhraseName($field['field_id']));

		$field['hasValue'] = $valueSaved && ((is_string($fieldValue) && $fieldValue !== '') || (!is_string($fieldValue) && $fieldValue));

		if ($getFieldChoices)
		{
			$field['fieldChoices'] = $this->getTeamFieldChoices($field['field_id'], $field['field_choices']);
		}

		return $field;
	}

	/**
	 * Prepares a list of team fields for display.
	 *
	 * @param array $fields
	 * @param boolean $getFieldChoices If true, gets the choice options for these fields (as phrases)
	 * @param array $fieldValues List of values for the specified fields; if skipped, pulled from field_value in array
	 * @param boolean $valueSaved If true, considers the value passed to be saved; should be false on registration
	 *
	 * @return array
	 */
	public function prepareTeamFields(array $fields, $getFieldChoices = false, array $fieldValues = array(), $valueSaved = true)
	{
		foreach ($fields AS &$field)
		{
			$value = isset($fieldValues[$field['field_id']]) ? $fieldValues[$field['field_id']] : null;
			$field = $this->prepareTeamField($field, $getFieldChoices, $value, $valueSaved);
		}

		return $fields;
	}

	/**
	 * Gets the field choices for the given field.
	 *
	 * @param string $fieldId
	 * @param string|array $choices Serialized string or array of choices; key is choide ID
	 * @param boolean $master If true, gets the master phrase values; otherwise, phrases
	 *
	 * @return array Choices
	 */
	public function getTeamFieldChoices($fieldId, $choices, $master = false)
	{
		if (!is_array($choices))
		{
			$choices = ($choices ? @unserialize($choices) : array());
		}

		if (!$master)
		{
			foreach ($choices AS $value => &$text)
			{
				$text = new XenForo_Phrase($this->getTeamFieldChoicePhraseName($fieldId, $value));
			}
		}

		return $choices;
	}

	/**
	 * Verifies that the value for the specified field is valid.
	 *
	 * @param array $field
	 * @param mixed $value
	 * @param mixed $error Returned error message
	 *
	 * @return boolean
	 */
	public function verifyTeamFieldValue(array $field, &$value, &$error = '')
	{
		$error = false;

		switch ($field['field_type'])
		{
			case 'textbox':
				$value = preg_replace('/\r?\n/', ' ', strval($value));
				// break missing intentionally

			case 'textarea':
			case 'bbcode':
				$value = trim(strval($value));

				if ($field['field_type'] == 'bbcode')
				{
					$value = XenForo_Helper_String::autoLinkBbCode($value);
				}

				if ($field['max_length'] && utf8_strlen($value) > $field['max_length'])
				{
					$error = new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', array('count' => $field['max_length']));
					return false;
				}

				$matched = true;

				if ($value !== '')
				{
					switch ($field['match_type'])
					{
						case 'number':
							$matched = preg_match('/^[0-9]+(\.[0-9]+)?$/', $value);
							break;

						case 'alphanumeric':
							$matched = preg_match('/^[a-z0-9_]+$/i', $value);
							break;

						case 'email':
							$matched = Zend_Validate::is($value, 'EmailAddress');
							break;

						case 'url':
							if ($value === 'http://')
							{
								$value = '';
								break;
							}
							if (substr(strtolower($value), 0, 4) == 'www.')
							{
								$value = 'http://' . $value;
							}
							$matched = Zend_Uri::check($value);
							break;

						case 'regex':
							$matched = preg_match('#' . str_replace('#', '\#', $field['match_regex']) . '#sU', $value);
							break;

						case 'callback':
							$matched = call_user_func_array(
								array($field['match_callback_class'], $field['match_callback_method']),
								array($field, &$value, &$error)
							);

						default:
							// no matching
					}
				}

				if (!$matched)
				{
					if (!$error)
					{
						$error = new XenForo_Phrase('please_enter_value_that_matches_required_format');
					}
					return false;
				}
				break;

			case 'radio':
			case 'select':
				$choices = unserialize($field['field_choices']);
				$value = strval($value);

				if (!isset($choices[$value]))
				{
					$value = '';
				}
				break;

			case 'checkbox':
			case 'multiselect':
				$choices = unserialize($field['field_choices']);
				if (!is_array($value))
				{
					$value = array();
				}

				$newValue = array();

				foreach ($value AS $key => $choice)
				{
					$choice = strval($choice);
					if (isset($choices[$choice]))
					{
						$newValue[$choice] = $choice;
					}
				}

				$value = $newValue;
				break;
		}

		return true;
	}

	/**
	 * Gets the possible team field groups. Used to display in form in ACP.
	 *
	 * @return array [group] => keys: value, label, hint (optional)
	 */
	public function getTeamFieldGroups()
	{
		return array(
			'extra_tab' => array(
				'value' => 'extra_tab',
				'label' => new XenForo_Phrase('Teams_extra_information_tab')
			),
			'new_tab' => array(
				'value' => 'new_tab',
				'label' => new XenForo_Phrase('Teams_own_tab')
			),
			'parent_tab' => array(
				'value' => 'parent_tab',
				'label' => new XenForo_Phrase('Teams_parent_tab'),
				'inputclass' => 'Disabler'
			)
		);
	}

	/**
	 * Gets the possible team field types.
	 *
	 * @return array [type] => keys: value, label, hint (optional)
	 */
	public function getTeamFieldTypes()
	{
		return array(
			'textbox' => array(
				'value' => 'textbox',
				'label' => new XenForo_Phrase('single_line_text_box')
			),
			'textarea' => array(
				'value' => 'textarea',
				'label' => new XenForo_Phrase('multi_line_text_box')
			),
			'bbcode' => array(
				'value' => 'bbcode',
				'label' => new XenForo_Phrase('Teams_rich_text_box'),
			),
			'select' => array(
				'value' => 'select',
				'label' => new XenForo_Phrase('drop_down_selection')
			),
			'radio' => array(
				'value' => 'radio',
				'label' => new XenForo_Phrase('radio_buttons')
			),
			'checkbox' => array(
				'value' => 'checkbox',
				'label' => new XenForo_Phrase('check_boxes')
			),
			'multiselect' => array(
				'value' => 'multiselect',
				'label' => new XenForo_Phrase('multiple_choice_drop_down_selection')
			)
		);
	}

	/**
	 * Maps team fields to their high level type "group". Field types can be changed only
	 * within the group.
	 *
	 * @return array [field type] => type group
	 */
	public function getTeamFieldTypeMap()
	{
		return array(
			'textbox' => 'text',
			'textarea' => 'text',
			'bbcode' => 'text',
			'radio' => 'single',
			'select' => 'single',
			'checkbox' => 'multiple',
			'multiselect' => 'multiple'
		);
	}

	/**
	 * Gets the field's title phrase name.
	 *
	 * @param string $fieldId
	 *
	 * @return string
	 */
	public function getTeamFieldTitlePhraseName($fieldId)
	{
		return 'Teams_team_field_' . $fieldId;
	}

	/**
	 * Gets the field's description phrase name.
	 *
	 * @param string $fieldId
	 *
	 * @return string
	 */
	public function getTeamFieldDescriptionPhraseName($fieldId)
	{
		return 'Teams_team_field_' . $fieldId . '_desc';
	}

	/**
	 * Gets a field choices's phrase name.
	 *
	 * @param string $fieldId
	 * @param string $choice
	 *
	 * @return string
	 */
	public function getTeamFieldChoicePhraseName($fieldId, $choice)
	{
		return 'Teams_team_field_' . $fieldId . '_choice_' . $choice;
	}

	/**
	 * Gets a field's master title phrase text.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getTeamFieldMasterTitlePhraseValue($id)
	{
		$phraseName = $this->getTeamFieldTitlePhraseName($id);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	/**
	 * Gets a field's master description phrase text.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getTeamFieldMasterDescriptionPhraseValue($id)
	{
		$phraseName = $this->getTeamFieldDescriptionPhraseName($id);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	public function getCategoryAssociationsByField($fieldId)
	{
		return $this->_getDb()->fetchCol('
			SELECT team_category_id
			FROM xf_team_field_category
			WHERE field_id = ?
		', $fieldId);
	}

	public function rebuildFieldCategoryAssociationCache($categoryIds)
	{
		if (!is_array($categoryIds))
		{
			$categoryIds = array($categoryIds);
		}
		if (!$categoryIds)
		{
			return;
		}

		$db = $this->_getDb();

		$newCache = array();

		foreach ($this->getTeamFieldsInCategories($categoryIds) AS $field)
		{
			$newCache[$field['team_category_id']][$field['display_group']][$field['field_id']] = $field['field_id'];
		}

		XenForo_Db::beginTransaction($db);

		foreach ($categoryIds AS $categoryId)
		{
			$update = (isset($newCache[$categoryId]) ? serialize($newCache[$categoryId]) : '');

			$db->update('xf_team_category', array(
				'field_cache' => $update
			), 'team_category_id = ' . $db->quote($categoryId));
		}

		XenForo_Db::commit($db);
	}

	/**
	 * Gets the team field values for the given team.
	 *
	 * @param integer $teamId
	 *
	 * @return array [field id] => value (may be string or array)
	 */
	public function getTeamFieldValues($teamId)
	{
		$fields = $this->_getDb()->fetchAll('
			SELECT v.*, field.field_type
			FROM xf_team_field_value AS v
			INNER JOIN xf_team_field AS field ON (field.field_id = v.field_id)
			WHERE v.team_id = ?
		', $teamId);

		$values = array();
		foreach ($fields AS $field)
		{
			if ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect')
			{
				$values[$field['field_id']] = @unserialize($field['field_value']);
			}
			else
			{
				$values[$field['field_id']] = $field['field_value'];
			}
		}

		return $values;
	}

	public function getTeamFieldCache()
	{
		if (XenForo_Application::isRegistered('teamFieldsInfo'))
		{
			return XenForo_Application::get('teamFieldsInfo');
		}

		$info = $this->_getDataRegistryModel()->get('teamFieldsInfo');
		if (!is_array($info))
		{
			$info = $this->rebuildTeamFieldCache();
		}
		XenForo_Application::set('teamFieldsInfo', $info);

		return $info;
	}

	/**
	 * Rebuilds the cache of team field info for front-end display
	 *
	 * @return array
	 */
	public function rebuildTeamFieldCache()
	{
		$cache = array();
		foreach ($this->getTeamFields() AS $fieldId => $field)
		{
			$cache[$fieldId] = XenForo_Application::arrayFilterKeys($field, array(
				'field_id',
				'field_type',
				'display_group',
			));

			foreach (array('display_template') AS $optionalField)
			{
				if (!empty($field[$optionalField]))
				{
					$cache[$fieldId][$optionalField] = $field[$optionalField];
				}
			}
		}

		$this->_getDataRegistryModel()->set('teamFieldsInfo', $cache);
		return $cache;
	}

	/**
	 * @return XenForo_Model_Phrase
	 */
	protected function _getPhraseModel()
	{
		return Nobita_Teams_Container::getModel('XenForo_Model_Phrase');
	}
}
