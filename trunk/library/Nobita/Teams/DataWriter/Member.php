<?php

class Nobita_Teams_DataWriter_Member extends XenForo_DataWriter
{
	/**
	 * This option allow bypass verification of import mode.
	 *
	 * @var boolean
	 */
	const BYPASS_USER_VERIFICATION = 'bypassUserVerification';

	/**
	 * The array contain team information
	 *
	 * @var array
	 */
	const EXTRA_TEAM_DATA = 'extraTeamData';

	protected function _getFields()
	{
		return array(
			'xf_team_member' => array(
				'user_id' 					=> array('type' => self::TYPE_UINT, 'required' => true),
				'team_id' 					=> array('type' => self::TYPE_UINT, 'required' => true),
				'username' 					=> array('type' => self::TYPE_STRING, 'maxLength' => 50, 'default' => ''),

				'member_state' 				=> array('type' => self::TYPE_BINARY,
					'allowedValues' 		=> array('request', 'accept'), 'default' => 'accept', 'maxLength' => 25),
				'member_role_id' 			=> array('type' => self::TYPE_BINARY, 'maxLength' => 25, 'required' => true),

				'join_date' 				=> array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'alert' 					=> array('type' => self::TYPE_BOOLEAN, 'default' => 1),

				'action' 					=> array('type' => self::TYPE_BINARY,
					'allowedValues' => array('add', 'approval', 'promote', 'invite', ''),
					'default' => '',
					'maxLength' => 25
				),
				'action_user_id' 			=> array('type' => self::TYPE_UINT, 'default' => 0),
				'action_username' 			=> array('type' => self::TYPE_STRING, 'maxLength' => 50, 'default' => ''),

				// 1.1.2
				'req_message' 				=> array('type' => self::TYPE_STRING, 'default' => ''),

				// 1.2
				'send_alert' 				=> array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'send_email' 				=> array('type' => self::TYPE_BOOLEAN, 'default' => 0),

				// 2.2.3
				'last_view_date'			=> array('type' => self::TYPE_UINT, 'default' => 0),
				'last_reminder_date'		=> array('type' => self::TYPE_UINT, 'default' => 0)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!is_array($data))
		{
			return false;
		}

		$userID = false;
		$teamID = false;
		if (isset($data['user_id']) && isset($data['team_id']))
		{
			$userID = $data['user_id'];
			$teamID = $data['team_id'];
		}
		else if (isset($data[0]) && isset($data[1]))
		{
			$userID = $data[0];
			$teamID = $data[1];
		}
		else
		{
			return false;
		}

		return array('xf_team_member' => $this->_getMemberModel()->getRecordByKeys($userID, $teamID));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('user_id', 'team_id') as $field)
		{
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	protected function _preSave()
	{
		if(!$this->_getTeamData())
		{
			$this->error(new XenForo_Phrase('Teams_requested_team_not_found'));
			return false;
		}

		if ($this->isChanged('req_message'))
		{
			$reqMessage = $this->get('req_message');
			$maxLength = 140;

			$reqMessage = preg_replace('/\r?\n/', ' ', $reqMessage);

			if (utf8_strlen($reqMessage) > $maxLength)
			{
				$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters', array('count' => $maxLength)));
			}

			$this->set('req_message', $reqMessage);
		}

		if ($this->get('user_id') AND ! $this->getExtraData(self::BYPASS_USER_VERIFICATION))
		{
			$user = $this->_getUserModel()->getUserById($this->get('user_id'));

			if ($user)
			{
				$this->set('username', $user['username']);
			}
			else
			{
				$this->set('user_id', 0);
			}
		}

		if ($this->get('action_user_id'))
		{
			$user = $this->_getUserModel()->getUserById($this->get('action_user_id'));
			if ($user)
			{
				if ($user['username'] != $this->get('action_username'))
				{
					$this->set('action_username', $user['username']);
				}
			}
		}

		$lastSeenDate = 0;
		if ($this->isInsert())
		{
			if ($this->get('member_state') == 'accept')
			{
				if ($this->get('action') != 'add')
				{
					$lastSeenDate = XenForo_Application::$time;
				}
			}
			elseif ($this->get('member_state') == 'request')
			{
				if ($this->get('action') != 'invite')
				{
					$lastSeenDate = XenForo_Application::$time;
				}
			}
		}

		if ($lastSeenDate)
		{
			$this->set('last_view_date', $lastSeenDate);
		}
	}

	protected function _postSave()
	{
		$teamId = $this->get('team_id');
		$member = $this->getMergedData();

		$teamWriter = $this->_getTeamDwForUpdate();
		if($this->isInsert() || $this->isChanged('member_state'))
		{
			if ($this->get('member_state') == 'accept')
			{
				$teamWriter->updateMemberCountInTeam(1);

				if ($this->getExisting('member_state') == 'request')
				{
					if ($this->getExisting('action') == 'invite')
					{
						$teamWriter->updateMemberInviteCount(null);
					}
					elseif ($this->getExisting('action') != 'invite')
					{
						$teamWriter->updateMemberRequestCount(null);
					}
				}
			}
			elseif ($this->get('member_state') == 'request')
			{
				if ($this->get('action') == 'invite')
				{
					$teamWriter->updateMemberInviteCount(null);
				}
				elseif ($this->get('action') != 'invite')
				{
					$teamWriter->updateMemberRequestCount(null);
				}
			}
		}

		if($this->isChanged('member_role_id'))
		{
			$teamWriter->rebuildStaffList();
		}
		$teamWriter->save();

		// send alert when admin accepted an request
		if ($this->get('member_state') == 'accept'
			&& $this->getExisting('member_state') == 'request'
			&& $this->get('action') != 'invite'
		)
		{
			$this->_getMemberModel()->sendAlertsToTeamManagersOnAction($member, 'accept');
		}

		// send alerts to admins when someone ask to join their groups
		if ($this->get('member_state') == 'request'
			&& $this->get('action') != 'invite'
		)
		{
			$this->_getMemberModel()->sendAlertsToTeamManagersOnAction($member, 'request');
		}

		// send an alert invite to user
		if ($this->get('member_state') == 'request'
			&& $this->get('action') == 'invite'
		)
		{
			$this->_getMemberModel()->sendAlertsToTeamManagersOnAction($member, 'invite');
		}

		$db = $this->_db;

		$updateActivity = false;
		if ($this->isUpdate() && $this->isChanged('member_role_id'))
		{
			$updateActivity = true;
		}

		if ($this->isInsert() && $this->get('member_state') == 'accept')
		{
			$updateActivity = true;
		}

		if ($updateActivity)
		{
			$db->update('xf_team', array('last_updated' => XenForo_Application::$time),
				'team_id = ' . $db->quote($this->get('team_id'))
			);
		}
	}

	protected function _preDelete()
	{
		$team = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getTeamById($this->get('team_id'));
		if($team && $team['user_id'] == $this->get('user_id')) {
			$this->error(new XenForo_Phrase('Teams_you_cannot_remove_the_owner_of_team'));
			return false;
		}
	}

	protected function _postDelete()
	{
		$updateDw = $this->_getTeamDwForUpdate();
		if ($updateDw)
		{
			if ($this->get('member_state') == 'request' && $this->get('action') != 'invite')
			{
				$updateDw->updateMemberRequestCount(-1);
			}

			if ($this->get('member_state') == 'request' && $this->get('action') == 'invite')
			{
				$updateDw->updateMemberInviteCount(-1);
			}

			if ($this->get('member_state') == 'accept')
			{
				$updateDw->updateMemberCountInTeam(-1);
			}

			$updateDw->rebuildStaffList();
			$updateDw->save();
		}

		$db = $this->_db;

		// delete all alerts sent to user
		$this->_db->query('
			DELETE FROM xf_user_alert
			WHERE alerted_user_id = ?
				AND content_type = ?
				AND content_id = ?
		', array($this->get('user_id'), 'team_member', $this->get('team_id')));

		// delete all alerts which users made in group
		$this->_db->query('
			DELETE FROM xf_user_alert
			WHERE user_id = ?
				AND content_type = ?
				AND content_id = ?
		', array($this->get('user_id'), 'team_member', $this->get('team_id')));

		Nobita_Teams_Container::getModel('Nobita_Teams_Model_NewsFeed')->delete($this->get('team_id'), 0, 'member');
	}

	protected function _getTeamDwForUpdate()
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
		if ($dw->setExistingData($this->get('team_id')))
		{
			return $dw;
		}
		else
		{
			return false;
		}
	}

	protected function _getTeamData()
	{
		if(!$this->getExtraData(self::EXTRA_TEAM_DATA)) {
			$this->setExtraData(self::EXTRA_TEAM_DATA, $this->_getTeamModel()->getFullTeamById($this->get('team_id')));
		}

		return $this->getExtraData(self::EXTRA_TEAM_DATA);
	}

	protected function _getUserModel()
	{
		return Nobita_Teams_Container::getModel('XenForo_Model_User');
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}

	protected function _getMemberModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');
	}
}
