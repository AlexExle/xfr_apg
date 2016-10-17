<?php

class Nobita_Teams_ViewPublic_Event_View extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		XenForo_Application::set('view', $this);
		
		$bbCodeOptions = array(
			'states' => array(
				'viewAttachments' => $this->_params['canViewAttachments']
			),
			'contentType' => 'team_event',
			'contentIdKey' => 'event_id'
		);

		$formatterBase = XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this));
		$bbCodeParser = XenForo_BbCode_Parser::create($formatterBase);

		$commentBbCode = XenForo_BbCode_Formatter_Base::create('Nobita_Teams_BbCode_Formatter_Comment');
		$commentParser = XenForo_BbCode_Parser::create($commentBbCode);

		$params =& $this->_params;
		$this->_params['event']['message'] = $this->_params['event']['event_description'];

		$this->_params['event']['descriptionHtml'] = XenForo_ViewPublic_Helper_Message::getBbCodeWrapper(
			$this->_params['event'], $bbCodeParser, $bbCodeOptions
		);
		XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages($this->_params['comments'], $commentParser);

		// Simple comment form
		$this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'message', '',
			array(
				'extraClass' => 'NoAutoComplete',
				'height' => '60px',
				'json' => array('buttonConfig' => $commentBbCode->getWysiwygButtons())
			)
		);

	}
}