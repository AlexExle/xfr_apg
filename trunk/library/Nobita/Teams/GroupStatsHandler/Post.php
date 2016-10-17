<?php

class Nobita_Teams_GroupStatsHandler_Post extends Nobita_Teams_GroupStatsHandler_Abstract
{
    public function getData($teamId, $startDate, $endDate)
    {
        $db = $this->_getDb();

        $extraJoin = 'LEFT JOIN xf_thread AS thread ON (thread.thread_id = post.post_id)';
        $posts = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_post AS post', 'post.post_date', 'post.message_state = ? AND thread.team_id = ?', $extraJoin),
            array($startDate, $endDate, 'visible', $teamId)
        );

        $extraJoin = 'LEFT JOIN xf_post AS post ON (post.post_id = liked_content.content_id)
                    LEFT JOIN xf_thread AS thread ON (thread.thread_id = post.thread_id)';
        $postLikes = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_liked_content AS liked_content', 'liked_content.like_date', 'liked_content.content_type = ? AND thread.team_id = ?', $extraJoin),
            array($startDate, $endDate, 'post', $teamId)
        );

        return array(
            'post' => $posts,
            'post_like' => $postLikes,
        );
    }

    public function getStatsTypes()
    {
        return array(
            'post' => new XenForo_Phrase('posts'),
            'post_like' => new XenForo_Phrase('post_likes'),
        );
    }
}