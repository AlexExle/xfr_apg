<?php

class Nobita_Teams_Install_2030602 implements Nobita_Teams_Install_Skeleton
{
	public function isUpdate($oldVersionId, $newVersionId)
	{
		return ($oldVersionId < 2030602) ? true : false;
	}

	public function doUpdate(Zend_Db_Adapter_Abstract $db, $oldVersionId)
	{
		// Add Index to table xf_team_news_feed
		try
		{
			$db->query("ALTER TABLE xf_team_news_feed ADD KEY team_id_content_id (team_id, content_id),
					ADD KEY content_type_content_id (content_type, content_id)");
		}
		catch(Zend_Db_Exception $e) {}

		// Remove duplicate contents
		$duplicateRes = array();
		try
		{
			$duplicateRes = $db->fetchAll("
				SELECT *
				FROM xf_team_news_feed
				GROUP BY content_id
				HAVING COUNT(content_id) > 1
			");

		}
		catch(Zend_Db_Exception $e) {}

		foreach($duplicateRes as $res)
		{
			$dupIds = $db->fetchCol("
				SELECT news_feed_id
				FROM xf_team_news_feed
				WHERE content_id = ? AND team_id = ? AND news_feed_id <> ?
			", array($res['content_id'], $res['team_id'], $res['news_feed_id']));

			if (empty($dupIds))
			{
				continue;
			}

			$db->delete('xf_team_news_feed', 'news_feed_id IN (' . $db->quote($dupIds) . ')');
		}
	}

	public function doUpdatePermissions()
	{
		return array();
	}

	public function doUninstall(Zend_Db_Adapter_Abstract $db)
	{
	}
}
