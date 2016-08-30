<?php

class Nobita_Teams_ViewPublic_Comment_Insert extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create(
			'Nobita_Teams_BbCode_Formatter_Comment', 
			array('view' => $this)
		));

		$this->_params['comment']['messageHtml'] = XenForo_ViewPublic_Helper_Message::getBbCodeWrapper(
			$this->_params['comment'], $bbCodeParser
		);
	}
}