<?php

class Nobita_Teams_ViewPublic_Post_LikeConfirmed extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$message = $this->_params['post'];

		if (!empty($message['likeUsers']))
		{
			$params = array(
				'message' => $message,
				'likesUrl' => XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/posts/likes', $message)
			);

			$output = $this->_renderer->getDefaultOutputArray(get_class($this), $params, 'likes_summary');
		}
		else
		{
			$output = array('templateHtml' => '', 'js' => '', 'css' => '');
		}

		$output += XenForo_ViewPublic_Helper_Like::getLikeViewParams($this->_params['liked']);
		$output['like_state'] = $this->_params['liked'] ? 'fa fa-thumbs-o-down' : 'fa fa-thumbs-o-up';

		$output['like_count'] = $message['likes'];

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}