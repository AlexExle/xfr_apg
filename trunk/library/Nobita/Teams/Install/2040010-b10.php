<?php

class Nobita_Teams_Install_2040010 implements Nobita_Teams_Install_Skeleton
{
	public function isUpdate($oldVersionId, $newVersionId)
	{
		// Version 2.4.1 build 15
		return ($oldVersionId < 2040115) ? true : false;
	}

	public function doUpdate(Zend_Db_Adapter_Abstract $db, $oldVersionId)
	{
		$db->delete('xf_team_news_feed', 'content_type = \'member\'');

		try
		{
			$posts = $db->fetchAll('
				SELECT post_id, team_id, post_date
				FROM xf_team_post
				WHERE share_privacy = \'public\'
			');
		}
		catch(Zend_Db_Exception $e) {
			$posts = null;
		}

		if ($posts)
		{
			foreach($posts as $post)
			{
				Nobita_Teams_Container::getModel('Nobita_Teams_Model_NewsFeed')->publish(
					$post['team_id'], $post['post_id'], 'post', array(), $post['post_date']
				);
			}
		}

		try
		{
			$db->query("ALTER TABLE xf_team_category ADD COLUMN allow_enable_disable_tabs TINYINT(1) unsigned not null default 0");
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$db->query("ALTER TABLE xf_user DROP INDEX team_count");
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$db->query("ALTER TABLE xf_user CHANGE team_count manage_team_count int unsigned not null default 0");
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$db->query("ALTER TABLE xf_thread ADD INDEX team_id (team_id)");
		}
		catch(Zend_Db_Exception $e) {}
	}

	public function doUninstall(Zend_Db_Adapter_Abstract $db)
	{
	}

	public function doUpdatePermissions()
	{
		return array();
	}
}