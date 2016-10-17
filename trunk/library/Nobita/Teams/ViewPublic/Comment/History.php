<?php

class Nobita_Teams_ViewPublic_Comment_History extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$commentBbCode = XenForo_BbCode_Formatter_Base::create('Nobita_Teams_BbCode_Formatter_Comment', array(
			'view', $this
		));
		$commentBbCodeParser = XenForo_BbCode_Parser::create($commentBbCode);

		$bbCodeOptions = array(
			'contentType' => 'team_comment',
			'contentIdKey' => 'comment_id'
		);

		XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages($this->_params['comments'], $commentBbCodeParser);
	}

	public function renderJson()
	{
		$output = $this->_renderer->getDefaultOutputArray(get_class($this), $this->_params, $this->_templateName);

		if($this->_params['commentsUnshown'])
		{
			$output['commentsUnshown'] = $this->_params['commentsUnshown'];
		}

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}