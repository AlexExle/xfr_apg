<?php

class Nobita_Teams_Model_CategoryWatch extends Nobita_Teams_Model_Abstract
{
	/**
	 * Gets a user's watch record for the specified category ID.
	 *
	 * @param integer $userId
	 * @param integer $categoryId
	 *
	 * @return array|bool
	 */
	public function getUserCategoryWatchByCategoryId($userId, $categoryId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_team_category_watch
			WHERE user_id = ?
				AND team_category_id = ?
		', array($userId, $categoryId));
	}

	/**
	 * Get the watch records for a user, across many category IDs.
	 *
	 * @param integer $userId
	 * @param array $categoryIds
	 *
	 * @return array Format: [team_category_id] => watch info
	 */
	public function getUserCategoryWatchByCategoryIds($userId, array $categoryIds)
	{
		if (!$categoryIds)
		{
			return array();
		}

		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_team_category_watch
			WHERE user_id = ?
				AND team_category_id IN (' . $this->_getDb()->quote($categoryIds) . ')
		', 'team_category_id', $userId);
	}

	/**
	 * @param integer $userId
	 *
	 * @return array
	 */
	public function getUserCategoryWatchByUser($userId)
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_team_category_watch
			WHERE user_id = ?
		', 'team_category_id', $userId);
	}

	/**
	 * Get a list of all users watching a category. Includes permissions for the category.
	 *
	 * @param integer $categoryId
	 *
	 * @return array Format: [user_id] => info
	 */
	public function getUsersWatchingCategory(array $category)
	{
		$notificationLimit = "AND category_watch.notify_on = ('team')";

		$breadcrumb = unserialize($category['category_breadcrumb']);
		$categoryIds = array_keys($breadcrumb);
		$categoryIds[] = $category['team_category_id'];

		return $this->fetchAllKeyed('
			SELECT user.*,
				user_option.*,
				user_profile.*,
				category_watch.team_category_id AS watch_category_id,
				category_watch.notify_on,
				category_watch.send_alert,
				category_watch.send_email,
				permission_combination.cache_value AS global_permission_cache
			FROM xf_team_category_watch AS category_watch
			INNER JOIN xf_user AS user ON
				(user.user_id = category_watch.user_id AND user.user_state = \'valid\' AND user.is_banned = 0)
			INNER JOIN xf_user_option AS user_option ON
				(user_option.user_id = user.user_id)
			INNER JOIN xf_user_profile AS user_profile ON
				(user_profile.user_id = user.user_id)
			INNER JOIN xf_permission_combination AS permission_combination ON
				(permission_combination.permission_combination_id = user.permission_combination_id)
			WHERE category_watch.team_category_id IN (' . $this->_getDb()->quote($categoryIds) . ')
				AND (category_watch.include_children <> 0 OR category_watch.team_category_id = ?)
				AND (category_watch.send_alert <> 0 OR category_watch.send_email <> 0)
				' . $notificationLimit . '
		', 'user_id', array($category['team_category_id']));
	}

	protected static $_preventDoubleNotify = array();

	/**
	 * Send a notification to the users watching the team.
	 *
	 * @param array $team Info about the team the update is in
	 * @param array $noAlerts List of user ids to NOT alert (but still send email)
	 * @param array $noEmail List of user ids to not send an email
	 *
	 * @return array Empty or keys: alerted: user ids alerted, emailed: user ids emailed
	 */
	public function sendNotificationToWatchUsers(array $team, array $noAlerts = array(), array $noEmail = array())
	{
		if ($team['team_state'] != 'visible'
			|| $team['privacy_state'] == 'secret')
		{
			return array();
		}

		$teamModel = $this->_getTeamModel();

		$userModel = Nobita_Teams_Container::getModel('XenForo_Model_User');

		if (XenForo_Application::get('options')->emailWatchedThreadIncludeMessage)
		{
			$parseBbCode = true;
			$emailTemplate = 'Team_watched_team_category_messagetext';
		}
		else
		{
			$parseBbCode = false;
			$emailTemplate = 'Team_watched_team_category';
		}

		$teamUser = $userModel->getUserById($team['user_id']);
		if (!$teamUser)
		{
			$teamUser = $userModel->getVisitingGuestUser();
		}

		if (!empty($team['category_breadcrumb']))
		{
			$category = $team;
		}
		else
		{
			$category = $this->_getCategoryModel()->getCategoryById($team['team_category_id']);
			if (!$category)
			{
				return array();
			}
		}

		$alerted = array();
		$emailed = array();

		$users = $this->getUsersWatchingCategory($category);
		foreach ($users AS $user)
		{
			if ($user['user_id'] == $team['user_id'])
			{
				continue;
			}

			$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);

			if (!$teamModel->canViewTeamAndContainer($team, $category, $null, $user))
			{
				continue;
			}

			if ($user['send_email'] && !in_array($user['user_id'], $noEmail)
				&& $user['email'] && $user['user_state'] == 'valid'
			)
			{
				if (!isset($team['messageText']) && $parseBbCode)
				{
					$bbCodeParserText = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Text'));

					$bbCodeParserHtml = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('HtmlEmail'));
					$team['aboutHtml'] = new XenForo_BbCode_TextWrapper($team['about'], $bbCodeParserHtml);
				}

				if (!isset($team['titleCensored']))
				{
					$team['titleCensored'] = XenForo_Helper_String::censorString($team['title']);
				}

				$user['email_confirm_key'] = $userModel->getUserEmailConfirmKey($user);

				$mail = XenForo_Mail::create($emailTemplate, array(
					'team' => $team,
					'category' => $category,
					'teamUser' => $teamUser,
					'receiver' => $user
				), $user['language_id']);
				$mail->enableAllLanguagePreCache();
				$mail->queue($user['email'], $user['username']);

				$emailed[] = $user['user_id'];
				$noEmail[] = $user['user_id'];
			}

			if ($user['send_alert'] && !in_array($user['user_id'], $noAlerts))
			{
				XenForo_Model_Alert::alert(
					$user['user_id'],
					$team['user_id'],
					$team['username'],
					'team',
					$team['team_id'],
					'insert'
				);

				$alerted[] = $user['user_id'];
				$noAlerts[] = $user['user_id'];
			}
		}

		return array(
			'emailed' => $emailed,
			'alerted' => $alerted
		);
	}

	/**
	 * Sets the category watch state as requested. An empty state will delete any watch record.
	 *
	 * @param integer $userId
	 * @param integer $categoryId
	 * @param string|null $notifyOn If "delete", watch record is removed
	 * @param boolean|null $sendAlert
	 * @param boolean|null $sendEmail
	 * @param boolean|null $includeChildren
	 *
	 * @return boolean
	 */
	public function setCategoryWatchState($userId, $categoryId, $notifyOn = null, $sendAlert = null, $sendEmail = null, $includeChildren = null)
	{
		if (!$userId)
		{
			return false;
		}

		$categoryWatch = $this->getUserCategoryWatchByCategoryId($userId, $categoryId);

		if ($notifyOn === 'delete')
		{
			if ($categoryWatch)
			{
				$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_CategoryWatch');
				$dw->setExistingData($categoryWatch, true);
				$dw->delete();
			}
			return true;
		}

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_CategoryWatch');
		if ($categoryWatch)
		{
			$dw->setExistingData($categoryWatch, true);
		}
		else
		{
			$dw->set('user_id', $userId);
			$dw->set('team_category_id', $categoryId);
		}
		if ($notifyOn !== null)
		{
			$dw->set('notify_on', $notifyOn);
		}
		if ($sendAlert !== null)
		{
			$dw->set('send_alert', $sendAlert ? 1 : 0);
		}
		if ($sendEmail !== null)
		{
			$dw->set('send_email', $sendEmail ? 1 : 0);
		}
		if ($includeChildren !== null)
		{
			$dw->set('include_children', $includeChildren ? 1 : 0);
		}
		$dw->save();
		return true;
	}

	public function setCategoryWatchStateForAll($userId, $state)
	{
		$userId = intval($userId);
		if (!$userId)
		{
			return false;
		}

		$db = $this->_getDb();

		switch ($state)
		{
			case 'watch_email':
				return $db->update('xf_team_category_watch',
					array('send_email' => 1),
					"user_id = " . $db->quote($userId)
				);

			case 'watch_no_email':
				return $db->update('xf_team_category_watch',
					array('send_email' => 0),
					"user_id = " . $db->quote($userId)
				);

			case 'watch_alert':
				return $db->update('xf_team_category_watch',
					array('send_alert' => 1),
					"user_id = " . $db->quote($userId)
				);

			case 'watch_no_alert':
				return $db->update('xf_team_category_watch',
					array('send_alert' => 0),
					"user_id = " . $db->quote($userId)
				);

			case 'watch_include_children':
				return $db->update('xf_team_category_watch',
					array('include_children' => 1),
					"user_id = " . $db->quote($userId)
				);

			case 'watch_no_include_children':
				return $db->update('xf_team_category_watch',
					array('include_children' => 0),
					"user_id = " . $db->quote($userId)
				);

			case '':
				return $db->delete('xf_team_category_watch', "user_id = " . $db->quote($userId));

			default:
				return false;
		}
	}

	/**
	 * @return Nobita_Teams_Model_Team
	 */
	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}

	/**
	 * @return Nobita_Teams_Model_Category
	 */
	protected function _getCategoryModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category');
	}

	/**
	 * @return XenForo_Model_Alert
	 */
	protected function _getAlertModel()
	{
		return Nobita_Teams_Container::getModel('XenForo_Model_Alert');
	}
}
