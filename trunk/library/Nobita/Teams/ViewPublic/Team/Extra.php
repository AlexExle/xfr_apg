<?php

class Nobita_Teams_ViewPublic_Team_Extra extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		XenForo_Application::set('view', $this);

		$bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));
		
		$this->_params['team']['aboutHtml'] = new XenForo_BbCode_TextWrapper(
			$this->_params['team']['about'], $bbCodeParser
		);

		$this->_params['customFieldsGrouped'] = Nobita_Teams_Helper_Field::renderCustomFieldsForView(
			$this, $this->_params['customFieldsGrouped'], $this->_params['team']
		);

		$this->_params['parentTabsGrouped'] = Nobita_Teams_Helper_Field::renderCustomFieldsForView(
			$this, $this->_params['parentTabsGrouped'], $this->_params['team']
		);

		$this->_params['team']['rulesHtml'] = new XenForo_BbCode_TextWrapper(
			(isset($this->_params['team']['rules']) ? $this->_params['team']['rules'] : ''), $bbCodeParser
		);
	}

}