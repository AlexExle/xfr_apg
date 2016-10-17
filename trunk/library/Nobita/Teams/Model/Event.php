<?php

class Nobita_Teams_Model_Event extends Nobita_Teams_Model_Abstract
{
	/**
	 * $public show to all visitor
	 * $admin show to admin only
	 * $member show to member only (member + admin)
	 */
	const EVENT_PUBLIC = 'public';
	const EVENT_MODERATOR = 'admin';
	const EVENT_MEMBER = 'member';

	const FETCH_TEAM = 0x01;
	const FETCH_USER = 0x02;

	const FETCH_BBCODE_CACHE = 0x40;

	protected $_languages = null;
	protected $_timezones = null;

	public function getEventDataFromArray(array $data)
	{
		$collected = array();
		$prefix = 'event_';

		foreach(array_keys($data) as $dataKey) {
			if(strpos($dataKey, $prefix) === 0) {
				$eventKey = substr($dataKey, strlen($prefix));

				$collected[$eventKey] = $data[$dataKey];
			}
		}

		return $collected;
	}

	public function getEventById($eventId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareEventFetchOptions($fetchOptions);
		return $this->_getDb()->fetchRow('
			SELECT event.*
				' . $joinOptions['selectFields'] . '
			FROM xf_team_event AS event
				' . $joinOptions['joinTables'] . '
			WHERE event.event_id = ?
		', array($eventId));
	}

	public function getEventsByIds(array $eventIds, array $fetchOptions = array())
	{
		if (empty($eventIds))
		{
			return array();
		}

		$joinOptions = $this->prepareEventFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT event.*
				' . $joinOptions['selectFields'] . '
			FROM xf_team_event AS event
				' . $joinOptions['joinTables'] . '
			WHERE event.event_id IN (' . $this->_getDb()->quote($eventIds) . ')
		', 'event_id');
	}

	public function prepareEventFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_TEAM)
			{
				$selectFields .=',' . $this->_getTeamModel()->getSimpleTeamColumns();
				$joinTables .='
					LEFT JOIN xf_team AS team ON (team.team_id = event.team_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_USER)
			{
				// Fixed the bug: http://nobita.me/threads/612/
				$selectFields .=',
					user.user_id,IF(user.username IS NULL, event.username, user.username) as username, user.avatar_date,user.gender,user.gravatar';
				$joinTables .='
					LEFT JOIN xf_user AS user ON (user.user_id = event.user_id)';
			}

			if (XenForo_Application::getOptions()->cacheBbCodeTree && $fetchOptions['join'] & self::FETCH_BBCODE_CACHE)
			{
				$selectFields .= ',
					bb_code_parse_cache.parse_tree AS message_parsed, bb_code_parse_cache.cache_version AS message_cache_version';
				$joinTables .= '
					LEFT JOIN xf_bb_code_parse_cache AS bb_code_parse_cache ON
						(bb_code_parse_cache.content_type = \'team_event\' AND bb_code_parse_cache.content_id = event.event_id)';
			}

			if (isset($fetchOptions['likeUserId']))
			{
				if (empty($fetchOptions['likeUserId']))
				{
					$selectFields .= ',0 as like_date';
				}
				else
				{
					$selectFields .= ',
						liked_content.like_date';
					$joinTables .= '
						LEFT JOIN xf_liked_content AS liked_content
						ON (liked_content.content_type = \'team_event\'
							AND liked_content.content_id = event.event_id
							AND liked_content.like_user_id = ' . $this->_getDb()->quote($fetchOptions['likeUserId']) . ')';
				}
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables
		);
	}

	public function prepareEventConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		if (!empty($conditions['event_type']))
		{
			if (is_array($conditions['event_type']))
			{
				$sqlConditions[] = 'event.event_type IN (' . $db->quote($conditions['event_type']) . ')';
			}
			else
			{
				$sqlConditions[] = 'event.event_type = ' . $db->quote($conditions['event_type']);
			}
		}

		if (!empty($conditions['team_id']))
		{
			if (is_array($conditions['team_id']))
			{
				$sqlConditions[] = 'event.team_id IN (' . $db->quote($conditions['team_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'event.team_id = ' . $db->quote($conditions['team_id']);
			}
		}

		if (!empty($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				$sqlConditions[] = 'event.user_id IN (' . $db->quote($conditions['user_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'event.user_id = ' . $db->quote($conditions['user_id']);
			}
		}

		if (!empty($conditions['begin_date']) && is_array($conditions['begin_date']))
		{
			$sqlConditions[] = $this->getCutOffCondition("event.begin_date", $conditions['begin_date']);
		}

		if (!empty($conditions['begin_date_gt']))
		{
			$sqlConditions[] = 'event.begin_date > ' . $db->quote($conditions['begin_date_gt']);
		}

		if (!empty($conditions['begin_date_gt_or_lt']) && is_array($conditions['begin_date_gt_or_lt']))
		{
			$sqlConditions[] = '(event.begin_date > ' . $db->quote($conditions['begin_date_gt_or_lt'][0]) . ' OR event.begin_date < ' . $db->quote($conditions['begin_date_gt_or_lt'][1]) . ')';
		}

		if (isset($conditions['end_date_lt']))
		{
			$sqlConditions[] = 'event.end_date < ' . $db->quote($conditions['end_date_lt']);
		}

		if (!empty($conditions['end_date_lt_or_equal']) && is_array($conditions['end_date_lt_or_equal']))
		{
			// should be array('number to less than', 'number to equal')
			$sqlConditions[] = '(event.end_date < ' . $db->quote($conditions['end_date_lt_or_equal'][0]) . ' OR event.end_date = ' . $db->quote($conditions['end_date_lt_or_equal'][1]) . ')';
		}

		if (isset($conditions['event_today']))
		{
			$dt = new DateTime('@'.XenForo_Application::$time);
			$dt->setTimezone(XenForo_Locale::getDefaultTimeZone());
			$begin = $dt->setTime(0, 0, 0)->format('U');
			$end = $dt->setTime(23, 59, 59)->format('U');

			$sqlConditions[] = sprintf('(event.begin_date BETWEEN %1$1s AND %2$s) OR (event.end_date BETWEEN %1$1s AND %2$s)',
				$db->quote($begin),
				$db->quote($end)
			);
		}

		if (!empty($conditions['event_upcoming']) && is_array($conditions['event_upcoming']))
		{
			$sqlConditions[] = sprintf(
				'event.begin_date > %s OR event.end_date > %s',
				$db->quote($conditions['event_upcoming'][0]), $db->quote($conditions['event_upcoming'][1])
			);
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function getEventsTeam($teamId, array $conditions, array $fetchOptions)
	{
		$conditions = array_merge(array(
			'team_id' => $teamId
		), $conditions);

		return $this->getEvents($conditions, $fetchOptions);
	}

	public function getEvents(array $conditions, array $fetchOptions)
	{
		$joinOptions = $this->prepareEventFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$whereClause = $this->prepareEventConditions($conditions, $fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT event.*
					' . $joinOptions['selectFields'] . '
				FROM xf_team_event AS event
					' . $joinOptions['joinTables'] . '
				WHERE ' . $whereClause . '
				ORDER BY event.begin_date
			', $limitOptions['limit'], $limitOptions['offset']
		), 'event_id');
	}

	public function getAllEvents($teamId, array $conditions, array $fetchOptions)
	{
		return $this->getEventsTeam($teamId, $conditions, $fetchOptions);
	}

	public function getEventIdsByUser($userId, $teamId)
	{
		return $this->_getDb()->fetchCol('
			SELECT event_id
			FROM xf_team_event
			WHERE user_id = ?
				AND team_id = ?
		', array($userId, $teamId));
	}

	public function countEvents($teamId, array $conditions)
	{
		$fetchOptions = array();
		$whereClause = $this->prepareEventConditions($conditions, $fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_team_event AS event
			WHERE ' . $whereClause . '
				AND event.team_id = ?
		', $teamId);
	}

	public function getEventIdsInRange($start, $limit)
	{
		$db = $this->_getDb();
		return $db->fetchCol($db->limit('
			SELECT event_id
			FROM xf_team_event
			WHERE event_id > ?
		', $limit), array($start));
	}

	public function prepareEvent(array $event, array $team, array $category, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$event = $this->prepareEventTypePhrase($event);

		$teamDataColumns = Nobita_Teams_Model_Team::$teamDataColumns;
		foreach ($teamDataColumns as $dataColumn => $column)
		{
			if (array_key_exists($column, $event))
			{
				$event['teamInfo'][$dataColumn] = $event[$column];
			}
		}

		if($this->_timezones === null) {
			$this->_timezones = XenForo_Helper_TimeZone::getTimeZones();
		}
		$timeZones = $this->_timezones;
		$timeZone = isset($timeZones[$event['timezone']]) ? $timeZones[$event['timezone']] : null;

		$event['timeZoneString'] = $timeZone;

		if ($this->_languages === null)
		{
			$this->_languages = $this->_getDataRegistryModel()->get('languages');
		}

		$format = 'F j, Y g:i A';
		if (is_array($this->_languages) AND isset($this->_languages[$viewingUser['language_id']]))
		{
			$language = $this->_languages[$viewingUser['language_id']];
			$format = $language['date_format'] . ' ' . $language['time_format'];
		}

		$autoConvert = Nobita_Teams_Option::get('eventConvertTime');

		if($autoConvert && $timeZone)
		{
			$eventOffset = new DateTime('now', new DateTimeZone($event['timezone']));
			$offset = $eventOffset->format('Z');

			$event['beginDateTime'] = XenForo_Locale::date($event['begin_date'], 'H:i', null, 'UTC');

			$event['begin_date'] = $event['begin_date'] - $offset;
			$event['beginDate'] = XenForo_Locale::date($event['begin_date'], $format);

			if($event['end_date'])
			{
				$event['endDateTime'] = XenForo_Locale::date($event['end_date'], 'H:i', null, 'UTC');

				$event['end_date'] = $event['end_date'] - $offset;
				$event['endDate'] = XenForo_Locale::date($event['end_date'], $format);
			}
		}
		else
		{
			$event['beginDate'] = XenForo_Locale::date($event['begin_date'], $format, null, 'UTC');
			$event['beginDateTime'] = XenForo_Locale::date($event['begin_date'], 'H:i', null, 'UTC');

			if ($timeZone)
			{
				$event['beginDateFull'] = $event['beginDate']. ' ' . $timeZone->render();
			}

			if ($event['end_date'])
			{
				$event['endDateTime'] = XenForo_Locale::date($event['end_date'], 'H:i', null, 'UTC');
				$event['endDate'] = XenForo_Locale::date($event['end_date'], $format, null, 'UTC');

				if ($timeZone)
				{
					$event['endDateFull'] = $event['endDate'] . ' ' . $timeZone->render();
				}
			}
		}

		if ($team)
		{
			$event['canEditEvent'] = $this->canEditEvent($event, $team, $category, $null, $viewingUser);
			$event['canDeleteEvent'] = $this->canDeleteEvent($event, $team, $category, $null, $viewingUser);
			$event['canComment'] = $this->canCommentOnEvent($event, $team, $category, $null, $viewingUser);
			$event['canLikeEvent'] = $this->canLikeEvent($event, $team, $category, $null, $viewingUser);
		}
		else
		{
			$event['canEditEvent'] = false;
			$event['canDeleteEvent'] = false;
			$event['canComment'] = false;
			$event['canLikeEvent'] = false;
		}

		$event['event_title'] = XenForo_Helper_String::censorString($event['event_title']);
		$event['titleCensored'] = true;
		$event['likeUsers'] = unserialize($event['like_users']);

		$event['canReport'] = Nobita_Teams_Container::getModel('XenForo_Model_User')->canReportContent();

		$event['tagsList'] = unserialize($event['tags']);

		return $event;
	}

	// public function addCommentsToEvents(array $events)
	// {
	// 	$commentIdMap = array();
	// 	$commentModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');

	// 	foreach($events as &$event)
	// 	{
	// 		$latestCommentIds = json_decode($event['latest_comment_ids'], true);
	// 		arsort($latestCommentIds);

	// 		foreach($latestCommentIds as $commentId)
	// 		{
	// 			$commentIdMap[$commentId] = $event['event_id'];
	// 		}

	// 		$event['comments'] = array();
	// 	}

	// 	if($commentIdMap)
	// 	{
	// 		$comments = $commentModel->getCommentsByIds(array_keys($commentIdMap), array(
	// 			'join' => Nobita_Teams_Model_Comment::FETCH_USER
	// 					| Nobita_Teams_Model_Comment::FETCH_TEAM
	// 					| Nobita_Teams_Model_Comment::FETCH_BBCODE_CACHE,
	// 			'likeUserId' => XenForo_Visitor::getUserId(),
	// 			'order' => 'recent_comment',
	// 		));

	// 		foreach($commentIdMap as $commentId => $eventId)
	// 		{
	// 			$comment = $comments[$commentId];
	// 			$events[$eventId]['comments'][$commentId] = $commentModel->prepareComment($comment, $comment, $comment);
	// 		}
	// 	}

	// 	return $events;
	// }

	public function getEventCalendarEntries(array $events)
	{
		$entries = array();

		// save the current time zone of XF
		$oldTimezone = XenForo_Locale::getDefaultTimeZone();

		$i = 0;
		foreach ($events as $eventId => $event)
		{
			$i++;

			//$currentDate = XenForo_Locale::date($event['begin_date'], 'Y-m-d H:i:s', null, $event['timezone']);
			// bugs report: http://nobita.me/threads/531/
			$currentDate = Date('Y-m-d H:i:s', $event['begin_date']);

			if ($event['end_date'])
			{
				//$nextDate = XenForo_Locale::date($event['end_date'], 'Y-m-d H:i:s');
				$nextDate = Date('Y-m-d H:i:s', $event['end_date']);
			}
			else
			{
				$nextDate = Date('Y-m-d', strtotime('+1 day', strtotime($currentDate)));
				$nextDate .= ' 00:00:00';
			}

			$entries[] = array(
				'id' => $eventId,
				'title' =>  XenForo_Helper_String::wholeWordTrim($event['event_title'], 25),
				'start' => "$currentDate",
				'url' => XenForo_Link::convertUriToAbsoluteUri(XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/events', $event), true),
				'allDay' => false,

				'end' => "$nextDate",
				'className' => 'eventEntry'
			);
		}

		return $entries;
	}

	public function prepareEventTypePhrase(array $event)
	{
		switch($event['event_type'])
		{
			case self::EVENT_PUBLIC:
				$event['eventTypePhrase'] = new XenForo_Phrase('Teams_event_public');
				break;
			case self::EVENT_MEMBER:
				$event['eventTypePhrase'] = new XenForo_Phrase('Teams_event_member');
				break;
			case self::EVENT_MODERATOR:
				$event['eventTypePhrase'] = new XenForo_Phrase('Teams_event_admin');
				break;
			default: break; // Nothing to do!
		}

		return $event;
	}

	public function getPermissionBasedFetchConditions(array $team, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);

		$conditions = array();
		if (empty($memberRecord))
		{
			// guest! So display event public only.
			$conditions['event_type'] = self::EVENT_PUBLIC;
		}
		else // member? but still need an condition!
		{
			if ($this->isTeamMemberAwaitingConfirm($team['team_id'], $viewingUser))
			{
				// hmm? ask to join and wait to confirm? public only!
				$conditions['event_type'] = self::EVENT_PUBLIC;
			}
			else
			{
				if ($this->isTeamAdmin($team['team_id'], $viewingUser))
				{
					// admin of team? Show all events!
					$conditions['event_type'] = array(self::EVENT_PUBLIC, self::EVENT_MEMBER, self::EVENT_MODERATOR);
				}
				else
				{
					// member? show event public & member.
					$conditions['event_type'] = array(self::EVENT_PUBLIC, self::EVENT_MEMBER);
				}
			}
		}

		return $conditions;
	}

	public function prepareEventTypesOnCreateOrEdit(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$this->isTeamMember($team['team_id'], $viewingUser))
		{
			return false;
		}

		$types = array(
			self::EVENT_MEMBER => true,
			self::EVENT_MODERATOR => true,
			self::EVENT_PUBLIC => true
		);

		if ($this->isTeamAdmin($team['team_id'], $viewingUser))
		{
			return $types;
		}

		if (!empty($team['allow_member_event']))
		{
			// allow member can create?
			unset($types[self::EVENT_MODERATOR]);
			return $types;
		}

		return false;
	}

	public function canEditTags(array $event = null, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		if (!XenForo_Application::getOptions()->enableTagging)
		{
			return false;
		}

		$this->standardizeViewingUserReference($viewingUser);

		if ($event)
		{
			if ($event['user_id'] == $viewingUser['user_id'])
			{
				return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'manageOwnTagEvent');
			}

			return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'manageAnyTagEvent');
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'manageOwnTagEvent')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'manageAnyTagEvent'))
		{
			return true;
		}

		return false;
	}

	public function canViewEvent(array $event, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$this->_getTeamModel()->canViewTeamAndContainer($team, $category, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editEventAny')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteEventAny'))
		{
			return true;
		}

		switch ($event['event_type'])
		{
			case self::EVENT_PUBLIC:
				return true; // everyone can view!
			case self::EVENT_MEMBER:
				return $this->isTeamMember($team['team_id'], $viewingUser);
			case self::EVENT_MODERATOR:
				return $this->isTeamAdmin($team['team_id'], $viewingUser);
			default: return false;
		}

		return false;
	}

	public function canViewEventAndContainer(array $event, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		if (!$this->_getTeamModel()->canViewTeamAndContainer($team, $category, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		return $this->canViewEvent($event, $team, $category, $errorPhraseKey, $viewingUser);
	}

	public function canViewAttachmentOnEvent(array $event, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editEventAny')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteEventAny'))
		{
			return true;
		}

		return $this->_getTeamModel()->canViewAttachments($team, $category, $errorPhraseKey, $viewingUser);
	}

	public function getAndMergeAttachmentsIntoEvent(array $event)
	{
		$attachmentModel = Nobita_Teams_Container::getModel('XenForo_Model_Attachment');

		$attachments = $attachmentModel->getAttachmentsByContentIds('team_event', array($event['event_id']));
		foreach ($attachments AS $attachment)
		{
			$event['attachments'][$attachment['attachment_id']] = $attachmentModel->prepareAttachment($attachment);
		}

		return $event;
	}

	public function canAddEvent(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$this->_getTeamModel()->canViewTeamAndContainer($team, $category, $errorPhraseKey))
		{
			return false;
		}

		if ($this->isTeamAdmin($team['team_id'], $viewingUser))
		{
			/* by default allow admin can create new event. */
			return true;
		}

		if (!empty($team['allow_member_event']) && $this->isTeamMember($team['team_id'], $viewingUser))
		{
			/* if team allow member so must be check. */
			return true;
		}

		return false;
	}

	public function canEditEvent(array $event, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($this->isTeamOwner($team, $viewingUser))
		{
			return true; // owner & admin of team can edit any.
		}

		if ($event['user_id'] == $viewingUser['user_id'])
		{
			return true; // owner event can edit
		}

		if(XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editEventAny'))
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'editEventAny');
	}

	public function canDeleteEvent(array $event, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($this->isTeamOwner($team, $viewingUser))
		{
			return true; // owner & admin of team can edit any.
		}

		if ($event['user_id'] == $viewingUser['user_id'])
		{
			return true; // owner event can edit
		}

		if(XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteEventAny'))
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'deleteEventAny');
	}

	public function canLikeEvent(array $event, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($viewingUser['user_id'] == $event['user_id'])
		{
			return false;
		}

		return true;
	}


	public function canCommentOnEvent(array $event, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'] || $event['team_id'] != $team['team_id'])
		{
			return false;
		}

		if ($this->isTeamOwner($team, $viewingUser))
		{
			return true;
		}

		if ($this->isTeamAdmin($team['team_id'], $viewingUser))
		{
			return true;
		}

		if ($event['allow_member_comment'] && $this->isTeamMember($team['team_id'], $viewingUser))
		{
			return true;
		}

		return false;
	}

	public function deleteEvent($eventId)
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Event');
		if ($dw->setExistingData($eventId))
		{
			$dw->delete();
		}
	}

	public function sendAlertWhenNewEventCreated(array $event, array $team)
	{
		$memberModel = $this->_getMemberModel();
		$teamModel = $this->_getTeamModel();

		$categoryModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category');
		$category = $categoryModel->getCategoryById($team['team_category_id']);

		if (!$category)
		{
			$category = array();
		}

		$activeLimitOption = XenForo_Application::getOptions()->watchAlertActiveOnly;
		if (!empty($activeLimitOption['enabled']))
		{
			$activeLimit = XenForo_Application::$time - 86400 * $activeLimitOption['days'];
		}
		else
		{
			$activeLimit = 0;
		}

		$conditions = array(
			'alert' => 1,
			'member_state' => 'accept'
		);
		if ($activeLimit)
		{
			$conditions['last_view_date'] = array('>=', $activeLimit);
		}

		$users = $memberModel->getAllMembersInTeam($event['team_id'], $conditions, array(
			'join' => Nobita_Teams_Model_Member::FETCH_USER
					 | Nobita_Teams_Model_Member::FETCH_USER_PERMISSIONS
		));

		if (empty($users))
		{
			return true;
		}

		foreach ($users as $user)
		{
			$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
			if (Nobita_Teams_Container::getModel('XenForo_Model_User')->isUserIgnored($user, $event['user_id']))
			{
				continue;
			}

			if ($user['user_id'] == $event['user_id'])
			{
				continue;
			}

			if (empty($user['send_alert']))
			{
				// i dont want to get alert
				// ignore me
				continue;
			}

			if (!$teamModel->canViewTeamAndContainer($team, $category, $null, $user))
			{
				continue;
			}

			if (!$this->canViewEvent($event, $team, $category, $null, $user))
			{
				continue;
			}

			XenForo_Model_Alert::alert($user['user_id'],
				$event['user_id'], $event['username'],
				'team_event', $event['event_id'],
				'publish'
			);
		}
	}

	public function batchUpdateLikeUser($oldUserId, $newUserId, $oldUsername, $newUsername)
	{
		$db = $this->_getDb();

		$oldUserId = $db->quote($oldUserId);
		$newUserId = $db->quote($newUserId);

		// note that xf_liked_content should have already been updated with $newUserId

		$db->query('
			UPDATE xf_team_event
			SET like_users = REPLACE(like_users, ' .
				$db->quote('i:' . $oldUserId . ';s:8:"username";s:' . strlen($oldUsername) . ':"' . $oldUsername . '";') . ', ' .
				$db->quote('i:' . $newUserId . ';s:8:"username";s:' . strlen($newUsername) . ':"' . $newUsername . '";') . ')
			WHERE event_id IN (
				SELECT content_id FROM xf_liked_content
				WHERE content_type = \'team_event\'
				AND like_user_id = ' . $newUserId . '
			)
		');
	}
}
