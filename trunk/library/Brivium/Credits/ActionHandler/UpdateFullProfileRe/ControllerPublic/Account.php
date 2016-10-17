<?php

class Brivium_Credits_ActionHandler_UpdateFullProfileRe_ControllerPublic_Account extends XFCP_Brivium_Credits_ActionHandler_UpdateFullProfileRe_ControllerPublic_Account
{
	/**
	 * Save profile data
	 *
	 * @return XenForo_ControllerResponse_Redirect
	 */
	public function actionPersonalDetailsSave()
	{
		$GLOBALS['BRC_UpdateFullProfileRe_CPAccount'] = $this;
		return parent::actionPersonalDetailsSave();
	}

	public function actionAvatarUpload()
	{
		$GLOBALS['BRC_UpdateFullProfileRe_CPAccount'] = $this;
		return parent::actionAvatarUpload();
	}
}