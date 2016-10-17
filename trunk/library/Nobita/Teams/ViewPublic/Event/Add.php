<?php
class Nobita_Teams_ViewPublic_Event_Add extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		XenForo_Application::set('view', $this);

		$this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'description', isset($this->_params['event']['event_description']) ? $this->_params['event']['event_description'] : '',
			array(
				'extraClass' => 'NoAutoComplete'
			)
		);
	}
}