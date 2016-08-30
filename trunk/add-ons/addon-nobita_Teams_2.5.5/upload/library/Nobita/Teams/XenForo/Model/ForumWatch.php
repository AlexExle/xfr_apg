<?php

class Nobita_Teams_XenForo_Model_ForumWatch extends XFCP_Nobita_Teams_XenForo_Model_ForumWatch
{
	protected $groupThread = array();

	public function groups_setThreadData(array $thread)
	{
		$this->groupThread = $thread;
	}

	public function hasGroupThread()
	{
		if ( empty($this->groupThread) )
		{
			return false;
		}

		if (array_key_exists('team_id', $this->groupThread) AND ! empty($this->groupThread['team_id']))
		{
			return true;
		}

		return false;
	}

	public function SocialGroups_getUsersWatchingForum($groupId, $nodeId)
	{
		$activeLimitOption = XenForo_Application::getOptions()->watchAlertActiveOnly;
		if (!empty($activeLimitOption['enabled']))
		{
			$activeLimit = XenForo_Application::$time - 86400 * $activeLimitOption['days'];
		}
		else
		{
			$activeLimit = 0;
		}

		$users = $this->fetchAllKeyed('
			SELECT team_member.*,
				user.*,
				user_option.*,
				user_profile.*,
				permission.cache_value AS node_permission_cache,
				permission_combination.cache_value AS global_permission_cache
			FROM xf_team_member AS team_member
				INNER JOIN xf_user AS user ON
					(user.user_id = team_member.user_id AND user.user_state = \'valid\' AND user.is_banned = 0)
				INNER JOIN xf_user_option AS user_option ON
					(user_option.user_id = user.user_id)
				INNER JOIN xf_user_profile AS user_profile ON
					(user_profile.user_id = user.user_id)
				LEFT JOIN xf_permission_cache_content AS permission
					ON (permission.permission_combination_id = user.permission_combination_id
						AND permission.content_type = \'node\'
						AND permission.content_id = ?)
				LEFT JOIN xf_permission_combination AS permission_combination ON
					(permission_combination.permission_combination_id = user.permission_combination_id)
			WHERE team_member.team_id = ?
				AND team_member.member_state = \'accept\'
				AND (team_member.send_alert <> 0 OR team_member.send_email <> 0)
				AND team_member.last_view_date >= ?
		', 'user_id', array($nodeId, $groupId, $activeLimit));

		foreach ($users as &$user)
		{
			$user['notify_on'] = $user['alert'];
			$user['read_date'] = 0;
		}

		return $users;
	}

	public function getUsersWatchingForum($nodeId, $threadId, $isReply = false)
	{
		$users = parent::getUsersWatchingForum($nodeId, $threadId, $isReply);

		if ($this->hasGroupThread())
		{
			$members = $this->SocialGroups_getUsersWatchingForum($this->groupThread['team_id'], $nodeId);

			$userIds = array_keys($users);
			foreach($members as &$member)
			{
				if (in_array($member['user_id'], $userIds))
				{
					continue;
				}

				$users[$member['user_id']] = $member;
			}
		}

		return $users;
	}

}