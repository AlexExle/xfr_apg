<?php

class Nobita_Teams_XenGallery_Installer
{
	public static function install($db)
	{
		try
		{
			$db->query("ALTER TABLE xengallery_media ADD COLUMN social_group_id int unsigned not null default 0");
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$db->query("ALTER TABLE xengallery_media ADD INDEX social_group_id (social_group_id)");
		}
		catch(Zend_Db_Exception $e) {}
	}

	public static function uninstall($db)
	{
		try
		{
			$db->query("ALTER TABLE xengallery_media DROP COLUMN social_group_id");
		}
		catch(Zend_Db_Exception $e) {}
	}

}
