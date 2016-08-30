<?php

class Nobita_Teams_XenForo_ControllerPublic_SpamCleaner extends XFCP_Nobita_Teams_XenForo_ControllerPublic_SpamCleaner
{
	public function actionIndex()
	{
		// global this value. its must be check later.
		$GLOBALS['Nobita_Teams_XenForo_ControllerPublic_SpamCleaner'] = isset($_POST['action_teams']) ? $_POST['action_teams'] : false;

		return parent::actionIndex();
	}
}