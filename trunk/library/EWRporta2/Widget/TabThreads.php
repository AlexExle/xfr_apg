<?php

class EWRporta2_Widget_TabThreads extends XenForo_Model
{
	public function getUncachedData($widget, &$options)
	{
		$options['tabthreads_source'] = $options['tabthreads_source1'];
	
		return $this->getTab($widget, $options);
	}

	public function getTab($widget, &$options)
	{
		$visitor = XenForo_Visitor::getInstance();

		$conditions = array(
			'deleted' => false,
			'moderated' => false,
			'find_new' => true,
			'not_discussion_type' => 'redirect',
			'last_post_date' => array('>', XenForo_Application::$time - 86400 * $options['tabthreads_cutoff']),
		);
		if ($options['tabthreads_source'][0] != 0)
		{
			$conditions['forum_ids'] = $options['tabthreads_source'];
		}

		$fetchOptions = array(
			'limit' => $options['tabthreads_limit'] * 2,
			'join' =>
				XenForo_Model_Thread::FETCH_FORUM |
				XenForo_Model_Thread::FETCH_FORUM_OPTIONS |
				XenForo_Model_Thread::FETCH_USER |
				XenForo_Model_Thread::FETCH_LAST_POST_AVATAR,
			'permissionCombinationId' => $visitor['permission_combination_id'],
			'postCountUserId' => $visitor['user_id'],
			'readUserId' => $visitor['user_id'],
			'watchUserId' => $visitor['user_id'],
			'order' => 'last_post_date',
			'forceThreadIndex' => 'last_post_date'
		);

		$threads = $this->getThreads($conditions, $fetchOptions);

		foreach ($threads AS $id => &$thread)
		{
			$thread['permissions'] = XenForo_Permission::unserializePermissions($thread['node_permission_cache']);
			
			if (!$this->getModelFromCache('XenForo_Model_Thread')->canViewThreadAndContainer($thread, $thread, $null, $thread['permissions']))
			{
				unset($threads[$id]);
			}

			$thread = $this->getModelFromCache('XenForo_Model_Thread')->prepareThread($thread, $thread, $thread['permissions']);
			$thread['canInlineMod'] = false;
			
			if (!empty($thread['lastPostInfo']['isIgnoring']) || $visitor->isIgnoring($thread['user_id']))
			{
				unset($threads[$id]);
			}
		}
		
		return array_slice($threads, 0, $options['tabthreads_limit'], true);
	}

	public function getThreads(array $conditions, array $fetchOptions = array())
	{
		$whereConditions = $this->getModelFromCache('XenForo_Model_Thread')->prepareThreadConditions($conditions, $fetchOptions);

		$sqlClauses = $this->getModelFromCache('XenForo_Model_Thread')->prepareThreadFetchOptions($fetchOptions);
		$limitOptions = $this->getModelFromCache('XenForo_Model_Thread')->prepareLimitFetchOptions($fetchOptions);

		if (!empty($conditions['forum_ids']))
		{
			$whereConditions .= ' AND thread.node_id IN ('.$this->_getDb()->quote($conditions['forum_ids']).')';
		}

		return $this->fetchAllKeyed($this->limitQueryResults('
				SELECT thread.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_thread AS thread
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
				' . $sqlClauses['orderClause'] . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'thread_id');
	}
}