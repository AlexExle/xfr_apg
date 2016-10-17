<?php

class Nobita_Teams_Install_280 implements Nobita_Teams_Install_Skeleton 
{
	public function isUpdate($oldVersionId, $newVersionId)
	{
		return ($oldVersionId < 280) ? true : false;
	}

	public function doUpdate(Zend_Db_Adapter_Abstract $db, $oldVersionId)
	{
		try
		{
			$db->query("ALTER TABLE xf_team_category ADD COLUMN min_tags int unsigned not null default 0");
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$db->query("ALTER TABLE xf_team ADD COLUMN tags blob not null");
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$db->query("ALTER TABLE xf_team_event ADD COLUMN tags blob not null");
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$db->query("ALTER TABLE xf_team DROP COLUMN cover_crop_details");
		}
		catch(Zend_Db_Exception $e) {}

		// Create newsFeed table

		$this->_copyCoversAndAvatars($db);
		$this->_migrateNewsFeed($db);

		try
		{
			$db->query("ALTER TABLE xf_team_post DROP COLUMN content_id");
		}
		catch(Zend_Db_Exception $e) {}
		try
		{
			$db->query("ALTER TABLE xf_team_post DROP COLUMN content_type");
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$db->query("DROP INDEX content_type_id ON xf_team_post");
		}
		catch(Zend_Db_Exception $e) {}
	}

	protected function _migrateNewsFeed($db)
	{
		$posts = $db->fetchAll('
			SELECT *
			FROM xf_team_post
		');

		$newsFeedModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_NewsFeed');
		$postIds = array();

		foreach($posts as $post)
		{
			$publishable = false;

			if (empty($post['content_type']))
			{
				$publishable = ($post['share_privacy'] == 'public') ? true : false;
			}
			else
			{
				if ($post['content_type'] == 'post')
				{
					$publishable = ($post['share_privacy'] == 'public') ? true : false;
				}
				else
				{
					$postIds[] = $post['post_id'];
					$publishable = ($post['content_type'] != 'member') ? true : false;
				}
			}

			if ($publishable)
			{
				$contentId = empty($post['content_id']) ? $post['post_id'] : $post['content_id'];
				$contentType = empty($post['content_type']) ? 'post' : $post['content_type'];

				$newsFeedModel->publish($post['team_id'], $contentId, $contentType);
			}
		}

		if (! empty($postIds))
		{
			$db->delete('xf_team_post', 'post_id IN (' . $db->quote($postIds) . ')');
		}

	}

	protected function _copyCoversAndAvatars($db)
	{
		$teams = $db->fetchAll('
			SELECT team_id, cover_date
			FROM xf_team
			WHERE cover_date > 0
		');

		if (count($teams) > 0)
		{
			$externalData = XenForo_Helper_File::getExternalDataPath();
			$coverModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Cover');

			foreach($teams as $team)
			{
				$grouped = floor($team['team_id'] / 1000);
				
				$source = sprintf('%s/nobita/teams/covers/%d/%d.jpg', $externalData, $grouped, $team['team_id']);
				$crop = sprintf('%s/nobita/teams/covers/%d/%d_%d_crop.jpg', 
					$externalData, $grouped, $team['team_id'], $team['cover_date']
				);

				$newSource = $coverModel->getCoverPath($team['team_id'], true);
				$newCrop = $coverModel->getCoverPath($team['team_id']);

				$directory = dirname($newCrop);

				if (file_exists($source) && file_exists($crop) 
					&& XenForo_Helper_File::createDirectory($directory, true) && is_writable($directory))
				{
					@copy($source, $newSource);
					@copy($crop, $newCrop);

					@unlink($source);
					@unlink($crop);
				}
			}
		}
	}

	public function doUpdatePermissions()
	{
		return array(
			'join' 			=> array('Teams', 'join', 'forum', 'postThread', false),
			'manageOwnTag' 	=> array('Teams', 'manageOwnTag', 'forum', 'tagOwnThread', false),
			'manageAnyTag' 	=> array('Teams', 'manageAnyTag', 'forum', 'manageAnyTag', true),

			'manageOwnTagEvent' => array('Teams', 'manageOwnTagEvent', 'forum', 'tagOwnThread', false),
			'manageAnyTagEvent' => array('Teams', 'manageAnyTagEvent', 'forum', 'manageAnyTag', true),
		);
	}

	public function doUninstall(Zend_Db_Adapter_Abstract $db)
	{
	}

}