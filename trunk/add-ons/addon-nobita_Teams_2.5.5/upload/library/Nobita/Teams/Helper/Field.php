<?php

class Nobita_Teams_Helper_Field
{
	/**
	 * @var XenForo_View
	 */
	protected $_view;

	/**
	 * @var XenForo_BbCode_Parser
	 */
	protected $_bbCodeParser;

	/**
	 * @var array
	 */
	protected $_fields;

	/**
	 * @var array
	 */
	protected $_team;

	protected function __construct(XenForo_View $view, array $fields, array $team)
	{
		$this->_view = $view;
		$this->_fields = $fields;

		$this->_team = $team;

		$this->_bbCodeParser = XenForo_BbCode_Parser::create(
			XenForo_BbCode_Formatter_Base::create('Base', array('view' => $view))
		);
	}

	public function render()
	{
		if (empty($this->_fields))
		{
			return $this->_fields;
		}

		$newFields = array();
		foreach ($this->_fields as $fieldPosition => $fields)
		{
			foreach ($fields as $fieldId => $field)
			{
				if (!$field['hasValue'] && $fieldId != 'rules')
				{
					// ignore tab has empty values
					// but not the rules tab
					continue;
				}

				$newFields[$fieldPosition][$fieldId] = $field;
			}
		}
		$this->_fields = $newFields;

		unset($fieldPosition, $fields, $fieldId, $field);

		foreach ($this->_fields as $fieldPosition => &$fields)
		{
			$fields = $this->_prepareFields($fields);
		}

		return $this->_fields;
	}

	protected function _prepareFields(array $fields)
	{
		foreach ($fields as $fieldId => &$field)
		{
			$field = $this->_prepareField($field);
		}

		return $fields;
	}

	protected function _prepareField(array $field)
	{
		if ($field['field_type'] == 'bbcode')
		{
			$field['fieldValueHtml'] = new XenForo_BbCode_TextWrapper($field['field_value'], $this->_bbCodeParser);
		}
		else
		{
			$field['fieldValueHtml'] = Nobita_Teams_ViewPublic_Helper_Team::getTeamFieldValueHtml(
				$this->_team, $field, $field['field_value']
			);
		}

		return $field;
	}

	public static function renderCustomFieldsForView(XenForo_View $view, array $fields, array $team)
	{
		$class  = XenForo_Application::resolveDynamicClass(__CLASS__);
		$class = new $class($view, $fields, $team);

		return $class->render();
	}

}