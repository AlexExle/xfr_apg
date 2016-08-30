<?php

class Nobita_Teams_Deferred_Migrate extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
	{
		$data = array_merge(array(
			'position' => 0,
			'batch' => 100
		), $data);
		$data['batch'] = max(1, $data['batch']);

        /* @var $teamModel Nobita_Teams_Model_Team */
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		$teamIds = $teamModel->getTeamIdsInRange($data['position'], $data['batch']);
		if (sizeof($teamIds) == 0)
		{
			return true;
		}

        $db = XenForo_Application::getDb();
        foreach($teamIds as $teamId)
        {
            $data['position'] = $teamId;

            $forumNames = $db->fetchPairs('
                SELECT node.node_id, node.title
                FROM xf_thread AS thread
                    INNER JOIN xf_node AS node ON (node.node_id = thread.node_id)
                WHERE thread.team_id = ?
            ', $teamId);

            if(!empty($forumNames))
            {
                $this->_migrate($teamId, $forumNames);
            }

            $this->_migrateNewsFeed($teamId);
            $this->_migrateEventData($teamId);
            $this->_updateThreadPostCounter($teamId);
        }

        XenForo_Application::defer('Forum', array(), 'forum');

        $rbPhrase = new XenForo_Phrase('rebuilding');
		$typePhrase = new XenForo_Phrase('Teams_handler_phrase_key_teams');
		$status = sprintf('%s... %s (%s)', $rbPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

        return $data;
    }

    public function _updateThreadPostCounter($teamId)
    {
        $db = XenForo_Application::getDb();

        $counter = $db->fetchRow('
            SELECT COUNT(*) AS total_thread, SUM(reply_count) AS total_post
            FROM xf_thread
            WHERE team_id = ?
        ', $teamId);

        $db->query("
            UPDATE xf_team
            SET thread_count = ?,
                thread_post_count = ?
            WHERE team_id = ?
        ", array($counter['total_thread'], $counter['total_thread'] + $counter['total_post'], $teamId));
    }

    protected function _migrateEventData($teamId)
    {
        $db = XenForo_Application::getDb();
        $events = $db->fetchCol('
            SELECT event_id
            FROM xf_team_event
            WHERE team_id = ?
        ', array($teamId));

        if(empty($events))
        {
            return;
        }

        $commentModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');

        foreach($events as $eventId)
        {
            $latestCommentIds = $commentModel->getLatestCommentIdsForContent($eventId, 'event', array(
                'limit' => 5
            ));
            $totalComments = $commentModel->countComments(array(
                'event_id' => $eventId
            ));

            $updates = $db->fetchRow('
                SELECT MIN(comment_date) AS first_comment_date,
                    MAX(comment_date) AS last_comment_date
                FROM xf_team_comment
                WHERE content_id = ? AND content_type = ?
            ', array($eventId, Nobita_Teams_Model_Comment::CONTENT_TYPE_EVENT));

            $firstCommentDate = isset($updates['first_comment_date']) ? $updates['first_comment_date'] : 0;
            $lastCommentDate = isset($updates['last_comment_date']) ? $updates['last_comment_date'] : 0;

            $db->query('
                UPDATE xf_team_event
                SET comment_count = ?,
                    latest_comment_ids = ?,
                    first_comment_date = ?,
                    last_comment_date = ?
                WHERE event_id = ?
            ', array($totalComments, json_encode($latestCommentIds), $firstCommentDate, $lastCommentDate, $eventId));
        }
    }

    protected function _migrateNewsFeed($teamId)
    {
        $db = XenForo_Application::getDb();

        $posts = $db->fetchAll('
            SELECT post_id, post_date, last_comment_date
            FROM xf_team_post
            WHERE team_id = ?
        ', array($teamId));

        if(empty($posts))
        {
            return;
        }

        $commentModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');

        foreach($posts as $post)
        {
            $imported = $db->fetchRow('
                SELECT *
                FROM xf_team_news_feed
                WHERE team_id = ?
                    AND content_type = ?
                    AND content_id = ?
            ', array($teamId, 'post', $post['post_id']));

            $latestCommentIds = $commentModel->getLatestCommentIdsForContent($post['post_id'], 'post', array(
                'limit' => 5
            ));

            $updates = $db->fetchRow('
                SELECT MIN(comment_date) AS first_comment_date,
                    MAX(comment_date) AS last_comment_date
                FROM xf_team_comment
                WHERE content_id = ? AND content_type = ?
            ', array($post['post_id'], Nobita_Teams_Model_Comment::CONTENT_TYPE_POST));

            $firstCommentDate = isset($updates['first_comment_date']) ? $updates['first_comment_date'] : 0;
            $lastCommentDate = isset($updates['last_comment_date']) ? $updates['last_comment_date'] : 0;

            $db->query("
                UPDATE xf_team_post
                SET latest_comment_ids = ?,
                    first_comment_date = ?,
                    last_comment_date = ?
                WHERE post_id = ?
            ", array(json_encode($latestCommentIds), $firstCommentDate, $lastCommentDate, $post['post_id']));

            if(!empty($imported))
            {
                continue;
            }

            $db->query("
                INSERT INTO xf_team_news_feed
                    (team_id, content_type, content_id, event_date, extra_data)
                VALUES
                    (?, ?, ?, ?, ?)
            ", array(
                $teamId, 'post', $post['post_id'], max($post['post_date'], $post['last_comment_date']), '[]'
            ));
        }
    }

    protected function _migrate($teamId, array $forumNames)
    {
        $db = XenForo_Application::getDb();
        $created = array();

        foreach($forumNames as $oldNodeId => $name)
        {
            if(empty($name))
            {
                continue;
            }

            if(!isset($created[$name]))
            {
                $exist = $db->fetchRow('
                    SELECT node.*,forum.*
                    FROM xf_forum AS forum
                        INNER JOIN xf_node AS node ON (node.node_id = forum.node_id)
                    WHERE node.title = ? AND forum.team_id = ?
                ', array($name, $teamId));

                if($exist)
                {
                    $created[$name] = $exist;
                }
                else
                {
                    $dw = XenForo_DataWriter::create('XenForo_DataWriter_Forum');
                    $dw->bulkSet(array(
                        'title' => $name,
                        'team_id' => $teamId,
                        'node_type_id' => Nobita_Teams_Listener::NODE_TYPE_ID
                    ));
                    $dw->save();

                    $created[$name] = $dw->getMergedData();
                }
            }

            $forum = $created[$name];
            $db->query('
                UPDATE xf_thread
                SET node_id = ?
                WHERE node_id = ? AND team_id = ?
            ', array($forum['node_id'], $oldNodeId, $teamId));
        }
    }
}
