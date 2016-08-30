<?php

class Nobita_Teams_CronEntry_Thread
{
    public static function runMinutely()
    {
        $cacheKey = Nobita_Teams_Listener::DATA_REG_THREADS;
        $dataRegistryModel = Nobita_Teams_Container::getModel('XenForo_Model_DataRegistry');

        // Build Recent & Trending Threads

		$recentThreadsLimit = Nobita_Teams_Option::get('recentThreadsLimit');
		$trendingThreadsLimit = Nobita_Teams_Option::get('trendingThreadsLimit');

		$threadModel = $dataRegistryModel->getModelFromCache('XenForo_Model_Thread');

		$recentThreads = array();
		$trendingThreads = array();

		if (!empty($recentThreadsLimit))
		{
			$recentThreads = $threadModel->getThreads(array(
				'moderated' => false,
				'deleted' => false,
				'has_team_id' => true
			), array(
				'join' => XenForo_Model_Thread::FETCH_AVATAR | XenForo_Model_Thread::FETCH_FORUM,
				'limit' => $recentThreadsLimit*2,
				'order' => 'last_post_date',
				'direction' => 'desc',
                Nobita_Teams_Listener::THREAD_FETCHOPTIONS_JOIN_TEAM => true,
			));
		}

		if (!empty($trendingThreadsLimit))
		{
			$trendingThreads = $threadModel->getThreads(array(
				'moderated' => false,
				'deleted' => false,
				'has_team_id' => true
			), array(
				'join' => XenForo_Model_Thread::FETCH_AVATAR | XenForo_Model_Thread::FETCH_FORUM,
				'limit' => $recentThreadsLimit*2,
				'order' => 'view_count',
				'direction' => 'desc',
                Nobita_Teams_Listener::THREAD_FETCHOPTIONS_JOIN_TEAM => true,
			));
		}

        $dataRegistryModel->set($cacheKey, array(
            'recent' => $recentThreads,
            'trending' => $trendingThreads
        ));
    }
}
