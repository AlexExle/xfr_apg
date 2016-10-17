<?php

class Nobita_Teams_Model_Deferred extends Nobita_Teams_Model_Abstract
{
	const DEFERRED_CLASS = 'Nobita_Teams_Deferred_Deferred';

	protected $_contentTypesAction = array(
		'inactive_members' 	=> 'removeInactiveMembers',
		'inactive_reminder'	=> 'sendReminderToInactiveMembers',
	);

	public static function queue($contentId, $contentType, array $data, $triggerDate = null)
	{
		$model = Nobita_Teams_Container::getModel(__CLASS__);
		call_user_func_array(array($model, 'insertQueue'), func_get_args());
	}

	public function insertDefaultQueue()
	{
		$this->insertQueue(0, 'inactive_members', array());
		$this->insertQueue(0, 'inactive_reminder', array());
	}

	public function insertQueue($contentId, $contentType, $data, $triggerDate = null)
	{
		if ( is_null($triggerDate) )
		{
			$triggerDate = XenForo_Application::$time;
		}

		try
		{
			$this->_getDb()->query('
				INSERT INTO xf_team_deferred
					(content_type, content_id, data, trigger_date)
				VALUES
					(?, ?, ?, ?)
				ON DUPLICATE KEY UPDATE
					content_type = VALUES(content_type),
					content_id = VALUES(content_id),
					data = VALUES(data),
					trigger_date = VALUES(trigger_date)
			', array($contentType, $contentId, serialize($data), $triggerDate));
		}
		catch(Zend_Db_Exception $e) {}

	}

	public function run($targetRunTime)
	{
		$s = microtime(true);
		$db = $this->_getDb();

		do
		{
			$queue = $this->getQueue($targetRunTime ? 20 : 0);
			foreach($queue as $id => $record)
			{
				if (!$db->delete('xf_team_deferred', 'team_deferred_id = ' . $db->quote($id)))
				{
					continue;
				}

				if (in_array($record['content_type'], array_keys($this->_contentTypesAction)))
				{
					$method = $this->_contentTypesAction[$record['content_type']];
					if (method_exists($this, $method))
					{
						call_user_func_array(array($this, $method), array($record));
					}
				}

				if ($targetRunTime && microtime(true) - $s > $targetRunTime)
				{
					$queue = false;
					break;
				}
			}
		}
		while($queue);

		return $this->hasQueue();
	}

	public function hasQueue()
	{
		$res = $this->_getDb()->fetchOne('
			SELECT MIN(trigger_date)
			FROM xf_team_deferred
			WHERE trigger_date < ?
		', XenForo_Application::$time);

		return (boolean) $res;
	}

	public function getQueue($limit = 20)
	{
		return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT *
			FROM xf_team_deferred
			WHERE trigger_date < ?
		', $limit), 'team_deferred_id', XenForo_Application::$time);
	}

	protected function sendReminderToInactiveMembers()
	{
		$cutOffDate = Nobita_Teams_Option::get('reminderCutOffDate');
		$nextReminder = Nobita_Teams_Option::get('timeBetweenReminder');
		$nextReminderTime = $nextReminder*86400;

		$time = XenForo_Application::$time + 2*60;
		$nextRun = $time; // 1 hour
		$this->insertQueue(0, 'inactive_reminder', array(), $nextRun);

		if (empty($cutOffDate))
		{
			// Nothing to do for this state
			return true;
		}
		$cutOffDate = $time - $cutOffDate*86400;

		$db = $this->_getDb();

		$viewToday = $time - 2*3600;
		$db->query('UPDATE xf_team_member SET last_reminder_date = \'0\' WHERE last_view_date > ?', array($viewToday));

		$members = $db->fetchAll('
			SELECT *
			FROM xf_team_member
			WHERE last_view_date < ? AND member_state = ?
		', array($cutOffDate, 'accept'));

		if (empty($members))
		{
			return true;
		}

		foreach ($members as $member)
		{
			if (empty($nextReminderTime) && !empty($member['last_reminder_date']))
			{
				// members has receive an reminder before
				// the option only allow receive a reminder
				// so ignore this member
				continue;
			}

			$reminder = false;
			if (empty($member['last_reminder_date']))
			{
				$reminder = true;
			}
			else
			{
				$sentDate = $time - $member['last_reminder_date'];
				if ($sentDate > $nextReminderTime)
				{
					$reminder = true;
				}
			}

			if ($reminder)
			{
				$db->update('xf_team_member', array('last_reminder_date' => $time),
					'team_id = ' . $db->quote($member['team_id']) . ' AND user_id = ' . $db->quote($member['user_id'])
				);

				XenForo_Model_Alert::alert($member['user_id'],
					0, 'Guest', 'team_member', $member['team_id'], 'reminder'
				);
			}
		}
	}

	protected function removeInactiveMembers(array $queue)
	{
		$db = $this->_getDb();

		$teams = $db->fetchAll('
			SELECT team.team_id, team.user_id, profile.remove_inactive_date
			FROM xf_team AS team
				LEFT JOIN xf_team_profile AS profile ON (profile.team_id = team.team_id)
			WHERE profile.remove_inactive_date > 0
		');

		$currentTime = XenForo_Application::$time;
		$nextRun = $currentTime + 2 * 3600; // 2 hours

		$this->insertQueue(0, 'inactive_members', array(), $nextRun);

		if ($teams)
		{
			foreach ($teams as $team)
			{
				$cutOff = $currentTime - $team['remove_inactive_date'] * 86400;
				$members = $db->fetchAll('
					SELECT *
					FROM xf_team_member
					WHERE team_id = ?
						AND last_view_date < ?
						AND member_state = ?
				', array($team['team_id'], $cutOff, 'accept'));

				if ($members)
				{
					foreach($members as $member)
					{
						if ($member['user_id'] == $team['user_id'])
						{
							continue;
						}

						$memberDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Member');
						if ($memberDw->setExistingData($member))
						{
							$memberDw->delete();
						}
					}
				}
			}
		}
	}


}
