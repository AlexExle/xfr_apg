<?php

class Nobita_Teams_GroupStatsHandler_Comment extends Nobita_Teams_GroupStatsHandler_Abstract
{
    public function getStatsTypes()
    {
        return array(
            'comment_newsfeed' => new XenForo_Phrase('Teams_comments_on_newsfeed'),
            'comment_member' => new XenForo_Phrase('Teams_comments_on_member_wall'),
            'comment_staff' => new XenForo_Phrase('Teams_comments_on_staff_wall'), 
        );
    }

    public function getData($teamId, $startDate, $endDate)
    {
        $db = $this->_getDb();

        $extraJoin = 'LEFT JOIN xf_team_post AS post ON (post.post_id = comment.content_id AND comment.content_type = \'post\')';
        $commentsOnNewsFeed = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_team_comment AS comment', 'comment.comment_date', 'comment.team_id = ? AND post.share_privacy = ?', $extraJoin),
            array($startDate, $endDate, $teamId, 'public')
        );

        $commentsOnMember = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_team_comment AS comment', 'comment.comment_date', 'comment.team_id = ? AND post.share_privacy = ?', $extraJoin),
            array($startDate, $endDate, $teamId, 'member')
        );

        $commentsOnStaff = $db->fetchPairs(
            $this->_getBasicDataQuery('xf_team_comment AS comment', 'comment.comment_date', 'comment.team_id = ? AND post.share_privacy = ?', $extraJoin),
            array($startDate, $endDate, $teamId, 'staff')
        );

        return array(
            'comment_newsfeed' => $commentsOnNewsFeed,
            'comment_member' => $commentsOnMember,
            'comment_staff' => $commentsOnStaff,
        );
    }

}