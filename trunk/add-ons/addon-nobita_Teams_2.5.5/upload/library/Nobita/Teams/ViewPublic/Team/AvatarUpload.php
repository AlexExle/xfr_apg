<?php

class Nobita_Teams_ViewPublic_Team_AvatarUpload extends XenForo_ViewPublic_Base
{

	public function renderJson()
	{
		$this->_params['url'] = Nobita_Teams_Template_Helper_Core::getAvatarUrl(
			$this->_params['team'], true
		);

		$output = XenForo_Application::arrayFilterKeys($this->_params, array(
			'url', 'team_avatar_date', 'message', 'redirectUri'
		));

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}