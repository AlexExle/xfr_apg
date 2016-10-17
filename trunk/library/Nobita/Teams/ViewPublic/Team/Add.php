<?php

class Nobita_Teams_ViewPublic_Team_Add extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$about = (isset($this->_params['team']['about']) ? $this->_params['team']['about'] : '');

		$this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'about', $about,
			array(
				'extraClass' => 'NoAutoComplete'
			)
		);

		foreach ($this->_params['customFields'] AS &$fields)
		{
			foreach ($fields AS &$field)
			{
				if ($field['field_type'] == 'bbcode')
				{
					$field['editorTemplateHtml'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
						$this, 'custom_fields[' . $field['field_id'] . ']',
						isset($field['field_value']) ? $field['field_value'] : '',
						array(
							'height' => '100px',
							'extraClass' => 'NoAttachment NoAutoComplete'
						)
					);
				}
			}
		}
		unset($fields, $field);
		foreach ($this->_params['parentTabsGrouped'] AS &$fields)
		{
			foreach ($fields AS &$field)
			{
				if ($field['field_type'] == 'bbcode')
				{
					$field['editorTemplateHtml'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
						$this, 'custom_fields[' . $field['field_id'] . ']',
						isset($field['field_value']) ? $field['field_value'] : '',
						array(
							'height' => '100px',
							'extraClass' => 'NoAttachment NoAutoComplete'
						)
					);
				}
			}
		}
	}
}