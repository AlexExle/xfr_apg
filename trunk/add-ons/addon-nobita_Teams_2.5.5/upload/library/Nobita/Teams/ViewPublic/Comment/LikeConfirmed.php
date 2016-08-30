<?php

class Nobita_Teams_ViewPublic_Comment_LikeConfirmed extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$message = $this->_params['comment'];

		if(!empty($message['likeUsers']))
		{
			$params = array(
				'message' => $message,
				'likesUrl' => Nobita_Teams_Link::buildTeamLink('comments/likes', $message)
			);

			$output = $this->_renderer->getDefaultOutputArray(get_class($this), $params, 'likes_summary');
		}
		else
		{
			$output = array('templateHtml' => '', 'js' => '', 'css' => '');
		}

		$output += XenForo_ViewPublic_Helper_Like::getLikeViewParams($this->_params['liked']);
		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}
