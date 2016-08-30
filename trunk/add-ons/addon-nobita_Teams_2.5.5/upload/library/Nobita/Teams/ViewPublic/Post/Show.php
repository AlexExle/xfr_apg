<?php

class Nobita_Teams_ViewPublic_Post_Show extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		XenForo_Application::set('view', $this);

		$params = &$this->_params;

		$bbCodeBase = XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this));
		$commentBbCode = XenForo_BbCode_Formatter_Base::create('Nobita_Teams_BbCode_Formatter_Comment', array('view', $this));

		$bbCodeParser = XenForo_BbCode_Parser::create($bbCodeBase);
		$commentBbCodeParser = XenForo_BbCode_Parser::create($commentBbCode);

		$bbCodeOptions = array(
			'states' => array(
				'viewAttachments' => $this->_params['canViewAttachments']
			),
			'contentType' => 'team_post',
			'contentIdKey' => 'post_id'
		);

		$params['post']['messageHtml'] = XenForo_ViewPublic_Helper_Message::getBbCodeWrapper(
			$params['post'], $bbCodeParser, $bbCodeOptions
		);

		XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages($params['post']['comments'], $commentBbCodeParser, array(
			'contentType' => 'team_comment',
			'contentIdKey' => 'comment_id'
		));
		$params['contentHtml'] = $this->createTemplateObject('Team_newsfeed_item_post', $params);
	}
}
