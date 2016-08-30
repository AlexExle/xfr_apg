<?php

class Nobita_Teams_Install_223 implements Nobita_Teams_Install_Skeleton
{
	public function isUpdate($oldVersionId, $newVersionId)
	{
		return ($oldVersionId < 233) ? true : false;
	}

	public function doUpdatePermissions()
	{
		return array();
	}

	public function doUpdate(Zend_Db_Adapter_Abstract $db, $oldVersionId)
	{
		$this->_updateAssociateNodeIds223($oldVersionId);
		$this->_updateLastViewGroupDate223($oldVersionId);
		$this->_updateStickyMessageCount223($oldVersionId);

		$this->_updateDeferredUniqueyKey223();
	}

	protected static function _updateDeferredUniqueyKey223()
	{
		$db = XenForo_Application::get('db');

		try
		{
			$db->query('ALTER TABLE xf_team_deferred ADD UNIQUE (content_type)');
		}
		catch(Zend_Db_Exception $e) {}
	}

	protected function _updateLastViewGroupDate223($oldVersionId)
	{
		$db = XenForo_Application::get('db');

		try
		{
			$db->query("ALTER TABLE xf_team_member ADD COLUMN last_view_date int unsigned not null default 0");
		}
		catch(Zend_Db_Exception $e) {}

		if ($oldVersionId < 196)
		{
			try
			{
				$db->query('
					UPDATE xf_team_member
					SET last_view_date = join_date
					WHERE 1 = 1
				');
			}
			catch(Zend_Db_Exception $e) {}
		}
	}

	protected function _updateAssociateNodeIds223($oldVersionId)
	{
		$db = XenForo_Application::get('db');


		try
		{
			$nodeIds = $db->fetchPairs('SELECT team_category_id, discussion_node_id FROM xf_team_category');
		}
		catch(Zend_Db_Exception $e) {
			$nodeIds = false;
		}

		if ($nodeIds && $oldVersionId < 196)
		{
			$teamCatIds = array();
			foreach($nodeIds as $teamCatId => $forumNodeId)
			{
				if (!empty($forumNodeId))
				{
					$teamCatIds[] = $teamCatId;
				}
			}

			$teams = array();
			if ($teamCatIds)
			{
				$teams = $db->fetchAll('SELECT * FROM xf_team WHERE team_category_id IN (' . $db->quote($teamCatIds) . ')');
			}

			
		}

		try
		{
			$db->query("ALTER TABLE xf_team_category DROP COLUMN discussion_node_id");
		}
		catch(Zend_Db_Exception $e) {}
	}

	protected function _updateStickyMessageCount223($oldVersionId)
	{
		$db = XenForo_Application::get('db');

		try
		{
			$db->query("ALTER TABLE xf_team ADD COLUMN sticky_message_count int unsigned not null default 0");
		}
		catch(Zend_Db_Exception $e) {}

		if ($oldVersionId < 196)
		{
			$teamIds = $db->fetchCol('SELECT team_id FROM xf_team');
			foreach($teamIds as $teamId)
			{
				$count = $db->fetchOne('SELECT COUNT(*) FROM xf_team_post WHERE sticky = ? AND team_id = ?', array(1, $teamId));
				$db->query('
					UPDATE xf_team
					SET sticky_message_count = ?
					WHERE team_id = ?
				', array($count, $teamId));
			}
		}
	}


	public function doUninstall(Zend_Db_Adapter_Abstract $db)
	{
	}
}
