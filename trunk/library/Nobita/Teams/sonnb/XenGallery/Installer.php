<?php

class Nobita_Teams_sonnb_XenGallery_Installer
{
	public static function install($db = null, $oldAddOnData, $newAddOnData)
	{
		if (!$db)
		{
			$db = XenForo_Application::get('db');
		}

		try
		{
			$db->query("ALTER TABLE sonnb_xengallery_album ADD COLUMN team_id int unsigned not null default 0");
		}
		catch (Zend_Db_Exception $e) {}

		try
		{
			$db->query("ALTER TABLE sonnb_xengallery_album ADD INDEX team_id (team_id)");
		}
		catch(Zend_Db_Exception $e) {}
	}

	public static function uninstall($db = null)
	{
		if (!$db)
		{
			$db = XenForo_Application::get('db');
		}

		try
		{
			$db->query("ALTER TABLE sonnb_xengallery_album DROP COLUMN team_id");
		}
		catch (Zend_Db_Exception $e) {}
	}
}
