<?php

class Nobita_Teams_ViewPublic_Team_UpdateRule extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		XenForo_Application::set('view', $this);
		$bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));

		$rules = (isset($this->_params['team']['rules']) ? $this->_params['team']['rules'] : '');

		$this->_params['rulesEditor'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'rules', $rules,
			array(
				'extraClass' => 'NoAutoComplete'
			)
		);

		$this->_params['team']['rulesHtml'] = new XenForo_BbCode_TextWrapper(
			$rules, $bbCodeParser
		);

	}
}