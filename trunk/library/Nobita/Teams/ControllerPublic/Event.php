<?php

class Nobita_Teams_ControllerPublic_Event extends Nobita_Teams_ControllerPublic_Abstract
{
	/**
	 * The list of event filter which supported
	 *
	 * @var array
	 */
	protected $_eventTypes = array('today', 'past', 'upcoming', 'user');

	public function actionIndex()
	{
		if ($eventId = $this->_input->filterSingle('event_id', XenForo_Input::UINT))
		{
			return $this->responseReroute(__CLASS__, 'view');
		}

		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();
		$this->_request->setParam('team_id', $team['team_id']);

		$this->_assertViewEventTab($team, $category);

		$type = $this->_input->filterSingle('type', XenForo_Input::STRING);
		if (empty($type))
		{
			$type = 'upcoming';
		}

		if (!in_array($type, $this->_eventTypes))
		{
			return $this->responseError(new XenForo_Phrase('requested_page_not_found'), 404);
		}

		$eventModel = $this->_getEventModel();
		$filterTab = $this->_input->filterSingle('filter_tab', XenForo_Input::STRING);
		if  (empty($filterTab))
		{
			$filterTab = Nobita_Teams_Option::get('eventLayout');
		}

		list($events, $totalEvents, $page, $perPage) = $this->_getEventsBaseType($type, $team, $filterTab);

		$this->canonicalizePageNumber(
			$page, $perPage, $totalEvents, TEAM_ROUTE_PREFIX . '/events', $team
		);
		$this->canonicalizeRequestUrl(Nobita_Teams_Link::buildTeamLink('events', $team));

		foreach ($events as $eventId => &$event)
		{
			if (!$eventModel->canViewEvent($event, $team, $category, $null))
			{
				unset($events[$eventId]); // remove all events which invalid to visitor!
				continue;
			}

			$event = $eventModel->prepareEvent($event, $team, $category);
		}

		if ($filterTab == 'calendar')
		{
			$time = XenForo_Application::$time;

			$dt = new DateTime('@' . $time);
			$dt->setTimeZone(XenForo_Locale::getDefaultTimeZone());
			$dt->setTime(0, 0, 0);

			$beginDay = $dt->format('U');
			$endDay = $dt->setTime(23, 59, 59)->format('U');

			$eventsToday = $eventModel->getEventsTeam($team['team_id'],
				array(
					'event_today' => true
				), array(
				'limit' => 5*3,
				'join' => Nobita_Teams_Model_Event::FETCH_USER
			));

			foreach ($eventsToday as $eTodayId => $eToday)
			{
				if (!$eventModel->canViewEvent($eToday, $team, $category, $null))
				{
					unset($eventsToday[$eTodayId]);
				}
			}

			$eventsToday = array_slice($eventsToday, 0, 5, true);
		}
		else
		{
			$eventsToday = array();
		}

		$viewParams = array(
			'team' => $team,
			'category' => $category,
			'events' => $events,
			'type' => $type,
			'filterTab' => $filterTab,

			'page' => $page,
			'perPage' => $perPage,
			'totalEvents' => $totalEvents,

			'pageRoute' => Nobita_Teams_Option::get('routePrefix').'/events',
			'pageParams' => array('team_id' => $team['team_id'], 'type' => $type, 'filter_tab' => 'list'),

			'switchLinkList' => Nobita_Teams_Link::buildTeamLink('events', $team, array(
				'type' => 'upcoming',
				'filter_tab' => 'list')
			),
			'switchLinkCalendar' => Nobita_Teams_Link::buildTeamLink('events', $team, array(
				'filter_tab' => 'calendar')
			),

			'eventsToday' => $eventsToday
		);

		return $this->_getTeamHelper()->getTeamViewWrapper('events', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Event_List', 'Team_event_list', $viewParams)
		);
	}

	public function actionCalendar()
	{
		$this->_assertPostOnly();

		$teamId = $this->_input->filterSingle('team_id', XenForo_Input::UINT);
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable($teamId);

		$this->_assertViewEventTab($team, $category);

		$filter = $this->_input->filter(array(
			'start' => XenForo_Input::STRING,
			'end' => XenForo_Input::STRING
		));

		$start = new DateTime($filter['start']);
		$start->setTimeZone(XenForo_Locale::getDefaultTimeZone());
		$start->setTime(0, 0, 0);

		$startstamp = $start->format('U');

		$end = new DateTime($filter['end']);
		$end->setTimeZone(XenForo_Locale::getDefaultTimeZone());
		$end->setTime(23, 59, 59);

		$endstamp = $end->format('U');

		$eventModel = $this->_getEventModel();

		$fetchOptions = array(
			'join' => Nobita_Teams_Model_Event::FETCH_TEAM
					| Nobita_Teams_Model_Event::FETCH_USER
		);

		$conditions = array(
			'begin_date' => array('>', $startstamp),
			'end_date_lt_or_equal' => array($endstamp, 0)
		);

		$events = $eventModel->getAllEvents($team['team_id'], $conditions, $fetchOptions);
		$events = $eventModel->getEventCalendarEntries($events);

		$this->_routeMatch->setResponseType('json');
		return $this->responseView('Nobita_Teams_ViewPublic_Event_Calendar', '', array('events' => $events));
	}

	protected function _getEventsBaseType($type, array $team, $filterTab)
	{
		$eventModel = $this->_getEventModel();

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage = 20;

		$conditions = array();
		$fetchOptions = array(
			'join' => Nobita_Teams_Model_Event::FETCH_TEAM | Nobita_Teams_Model_Event::FETCH_USER,
			'page' => $page,
			'perPage' => $perPage
		);

		$conditions += $eventModel->getPermissionBasedFetchConditions($team);

		if ($filterTab == 'calendar')
		{
			// saving query
			return array(array(), 0, 1, 1);
		}

		$time = XenForo_Application::$time;

		$dt = new DateTime('@' . $time);
		$dt->setTimeZone(XenForo_Locale::getDefaultTimeZone());
		$dt->setTime(0, 0, 0);

		$beginDay = $dt->format('U');
		$endDay = $dt->setTime(23, 59, 59)->format('U');

		switch ($type)
		{
			case 'today':
				$conditions = array_merge($conditions, array(
					'event_today' => true
				));
				break;
			case 'past':
				$conditions = array_merge($conditions, array(
					'begin_date' => array("<", $time),
					'end_date_lt_or_equal' => array($time, 0)
				));
				break;
			case 'user':
				$conditions = array_merge($conditions, array(
					'user_id' => XenForo_Visitor::getUserId()
				));
				break;
			case 'upcoming':
			default:
				$conditions = array_merge($conditions, array(
					'event_upcoming' => array($time, $time, $time)
				));
				break;
		}

		$events = $eventModel->getEventsTeam($team['team_id'], $conditions, $fetchOptions);
		$totalEvents = $eventModel->countEvents($team['team_id'], $conditions);

		return array($events, $totalEvents, $page, $perPage);
	}

	public function actionView()
	{
		$eventFetchOptions = array(
			'join' => Nobita_Teams_Model_Event::FETCH_BBCODE_CACHE
		);

		list($event, $team, $category) = $this->_getTeamHelper()->assertEventValidAndViewable(null, $eventFetchOptions);

		$this->_assertViewEventTab($team, $category);

		$commentModel = $this->_getCommentModel();

		$conditions = array(
			'event_id' => $event['event_id']
		);

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage = Nobita_Teams_Option::get('messagesPerPage');

		$fetchOptions = array(
			'join' => Nobita_Teams_Model_Comment::FETCH_USER
					  | Nobita_Teams_Model_Comment::FETCH_TEAM
					  | Nobita_Teams_Model_Comment::FETCH_BBCODE_CACHE,
			'page' => $page,
			'perPage' => $perPage,
			'order' => 'recent_comment',
			'likeUserId' => XenForo_Visitor::getUserId()
		);

		$comments = $commentModel->getComments($conditions, $fetchOptions);
		
		foreach ($comments as $commentId => &$comment)
		{
			$comment['eventData'] = $event;
			if(!$commentModel->canViewComment($comment, $team, $category))
			{
				unset($comments[$commentId]);
				continue;
			}
			$comment = $commentModel->prepareComment($comment, $event, $team, $category);
		}

		$ignoredNames = $this->_getIgnoredContentUserNames($comments);
		$totalComments = $commentModel->countComments($conditions);

		$this->canonicalizePageNumber($page, $perPage, $totalComments, TEAM_ROUTE_PREFIX . '/events', $event);
		$this->canonicalizeRequestUrl(Nobita_Teams_Link::buildTeamLink('events', $event));

		$event = $this->_getEventModel()->getAndMergeAttachmentsIntoEvent($event);

		$images = array();
		if(!empty($event['attachments']))
		{
			foreach($event['attachments'] as $attachment)
			{
				if(!empty($attachment['thumbnail_width']) || !empty($attachment['thumbnail_height']))
				{
					$images[$attachment['attachment_id']] = $attachment;
				}
			}
		}

		$viewParams = array(
			'event' => $event,
			'team' => $team,
			'category' => $category,

			'comments' => $comments,
			'totalComments' => $totalComments,
			'ignoredNames' => $ignoredNames,

			'page' => $page,
			'perPage' => $perPage,
			'canViewAttachments' => $this->_getEventModel()->canViewAttachmentOnEvent($event, $team, $category),
			'useOwnMetaProperty' => true,
			'images' => $images,
		);

		return $this->_getTeamHelper()->getTeamViewWrapper('events', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Event_View', 'Team_event_view', $viewParams)
		);
	}

	public function actionAdd()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getEventModel()->canAddEvent($team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$this->_request->setParam('team_id', $team['team_id']);
		$event = array(
			'event_id' => 0,
			'event_type' => 'public',
			'allow_member_comment' => 1,
			'begin_date' => XenForo_Application::$time,
			'end_date' => 0
		);
		return $this->_getEventEditOrResponse($event, $team, $category);
	}

	protected function _getEventEditOrResponse(array $event, array $team, array $category)
	{
		$visitor = XenForo_Visitor::getInstance();
		$attachmentModel = Nobita_Teams_Container::getModel('XenForo_Model_Attachment');

		$this->_assertViewEventTab($team, $category);

		$contentData = array(
			'event_id' => $event['event_id'],
			'team_id' => $team['team_id'],
			'content_type' => 'team_event'
		);

		$attachments = array();
		if (!empty($event['event_id']))
		{
			$attachments = $attachmentModel->getAttachmentsByContentId('team_event', $event['event_id']);
			$attachments = $attachmentModel->prepareAttachments($attachments);
		}

		$attachmentHash = null;
		$attachmentParams = $this->_getTeamModel()->getAttachmentParams(
			$team, $category, $contentData, null, null, $attachmentHash
		);

		$canEditTags = false;
		$editTags = array();

		if ($this->_getEventModel()->canEditTags(empty($event['user_id']) ? null : $event, $team, $category))
		{
			$canEditTags = true;

			if (! empty($event['event_id']))
			{
				/** @var XenForo_Model_Tag $tagModel */
				$tagModel = Nobita_Teams_Container::getModel('XenForo_Model_Tag');
				$tagger = $tagModel->getTagger('team_event');
				$tagger->setContent($event['event_id'])->setPermissionsFromContext($event, array_merge($team, array('min_tags' => $category['min_tags'])));

				$editTags = $tagModel->getTagListForEdit('team_event', $event['event_id'], $tagger->getPermission('removeOthers'));
			}
		}

		$eventTypes = $this->_getEventModel()->prepareEventTypesOnCreateOrEdit($team, $category);

		$eventTypeExplain = new XenForo_Phrase('Teams_event_type_explain');

		if(isset($eventTypes[Nobita_Teams_Model_Event::EVENT_MEMBER]))
		{
			$eventTypeExplain->setParams(array(
				'member_only' => new XenForo_Phrase('Teams_event_member_only'),
				'member_explain' => new XenForo_Phrase('Teams_event_type_member_explain')
			));
		}

		if(isset($eventTypes[Nobita_Teams_Model_Event::EVENT_MODERATOR]))
		{
			$eventTypeExplain->setParams(array(
				'admin_only' => ', '.new XenForo_Phrase('Teams_event_admin_only'),
				'admin_explain' => new XenForo_Phrase('Teams_event_type_admin_explain')
			));
		}

		$viewParams = array(
			'event' => $event,
			'team' => $team,
			'category' => $category,

			'eventTypes' => $eventTypes,
			'eventTypeExplain' => $eventTypeExplain,

			'timesMap' => Nobita_Teams_Option::getTimeMap(),

			'attachments' => $attachments,
			'attachmentParams' => $attachmentParams,
			'attachmentConstraints' => Nobita_Teams_Container::getModel('XenForo_Model_Attachment')->getAttachmentConstraints(),
			'canViewAttachments' => $visitor->hasPermission('Teams', 'viewAttachment'),
			'canUploadAttachments' => Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category')->canUploadAttachments($category),

			'timeZones' => XenForo_Helper_TimeZone::getTimeZones(),

			'canEditTags' => $canEditTags,
			'tags' => $editTags,
		);

		return $this->_getTeamHelper()->getTeamViewWrapper('events', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Event_Add', 'Team_event_edit', $viewParams)
		);
	}

	public function actionEdit()
	{
		list($event, $team, $category) = $this->_getTeamHelper()->assertEventValidAndViewable();

		$this->_assertViewEventTab($team, $category);

		$eventModel = $this->_getEventModel();
		if (!$eventModel->canEditEvent($event, $team, $category, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}

		return $this->_getEventEditOrResponse($event, $team, $category);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$eventId = $this->_input->filterSingle('event_id', XenForo_Input::UINT);
		$teamId = $this->_input->filterSingle('team_id', XenForo_Input::UINT);

		$visitor = XenForo_Visitor::getInstance();

		if ($eventId)
		{
			list($event, $team, $category) = $this->_getTeamHelper()->assertEventValidAndViewable($eventId);
			if (!$this->_getEventModel()->canEditEvent($event, $team, $category, $error))
			{
				throw $this->getErrorOrNoPermissionResponseException($error);
			}
		}
		else
		{
			$event = array();
			list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable($teamId);
			if (!$this->_getEventModel()->canAddEvent($team, $category, $error))
			{
				throw $this->getErrorOrNoPermissionResponseException($error);
			}
		}

		$this->_assertViewEventTab($team, $category);

		$data = $this->_input->filter(array(
			'event_title' => XenForo_Input::STRING,
			'event_type' => XenForo_Input::STRING,
			'allow_member_comment' => XenForo_Input::UINT
		));

		$timeZone = $this->_input->filterSingle('timezone', XenForo_Input::STRING);
		if (!$timeZone)
		{
			$timeZone = XenForo_Locale::getDefaultTimeZone();
		}

		$beginDate = $this->_input->filterSingle('begin_date', XenForo_Input::ARRAY_SIMPLE);
		$endDate = $this->_input->filterSingle('end_date', XenForo_Input::ARRAY_SIMPLE);

		$beginDate = $this->_getEventDate($beginDate, $timeZone);
		$endDate = $this->_getEventDate($endDate, $timeZone);
		if(!$this->_input->filterSingle('end_time_enable', XenForo_Input::UINT))
		{
			$endDate = 0;
		}

		if (empty($beginDate))
		{
			return $this->responseError(new XenForo_Phrase('please_enter_valid_value'));
		}

		/* upload attachment to event. */
		$attachmentHash = $this->_input->filterSingle('attachment_hash', XenForo_Input::STRING);

		$description = $this->getHelper('Editor')->getMessageText('description', $this->_input);
		$description = XenForo_Helper_String::autoLinkBbCode($description);

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Event');
		if ($event)
		{
			$dw->setExistingData($event['event_id']);
		}
		else
		{
			$dw->set('user_id', $visitor['user_id']);
			$dw->set('username', $visitor['username']);
		}

		// added in 2.3.6
		$tags = $this->_input->filterSingle('tags', XenForo_Input::STRING);
		$tagger = null;

		if ($this->_getEventModel()->canEditTags(empty($eventId) ? null : $event, $team, $category)
			&& XenForo_Application::$versionId > 1050000
		)
		{
			/** @var XenForo_Model_Tag $tagModel */
			$tagModel = Nobita_Teams_Container::getModel('XenForo_Model_Tag');
			$tagger = $tagModel->getTagger('team_event');

			$editTags = array();
			if (!empty($event['event_id']))
			{
				//$tagger->setContent($event['event_id'])->setPermissionsFromContext($event, array_merge($team, array('min_tags' => $category['min_tags'])));
				$tagger->setContent($event['event_id']);
				$editTags = $tagModel->getTagListForEdit('team_event', $event['event_id'], $tagger->getPermission('removeOthers'));
				if ($editTags['uneditable'])
				{
					// this is mostly a sanity check; this should be ignored
					$tags .= (strlen($tags) ? ', ' : '') . implode(', ', $editTags['uneditable']);
				}
			}

			$tagger->setPermissionsFromContext($event, array_merge($team, array('min_tags' => $category['min_tags'])))
				->setTags($tagModel->splitTags($tags));
			$dw->mergeErrors($tagger->getErrors());
		}

		$dw->bulkSet($data);

		$dw->set('team_id', $team['team_id']);
		$dw->set('event_description', $description);

		$dw->set('begin_date', $beginDate);
		$dw->set('end_date', $endDate);

		$dw->set('timezone', $timeZone);

		$dw->setExtraData(Nobita_Teams_DataWriter_Event::TEAM_DATA, $team);
		$dw->setExtraData(Nobita_Teams_DataWriter_Event::TEAM_CATEGORY_DATA, $category);
		$dw->setExtraData(Nobita_Teams_DataWriter_Event::DATA_ATTACHMENT_HASH, $attachmentHash);

		$dw->preSave();
		if (!$dw->hasErrors())
		{
			$this->assertNotFlooding('post'); // use post instead.
		}

		$dw->save();
		$event = $dw->getMergedData(); // get event data

		if ($tagger)
		{
			if($dw->isInsert())
			{
				$tagger->setContent($event['event_id'], true);
			}
			$tagger->save();
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			Nobita_Teams_Link::buildTeamLink('events', $event)
		);
	}

	protected function _getEventDate(array $options)
	{
		$timestamp = 0;

		try
		{
			$dt = new DateTime();
			$timeZone = new DateTimeZone('UTC');

			$date = explode('-', $options['date']);
			$dt->setDate($date[0], $date[1], $date[2]);

			$time = array_map('intval', explode(':', $options['time']));
			$dt->setTime($time[0], $time[1], 0);
			$dt->setTimeZone($timeZone);

			$timestamp = $dt->format('U');
		}
		catch(Exception $e) {}

		return $timestamp;
	}
/*
	public function actionComment()
	{
		$this->_assertPostOnly();

		list($event, $team, $category) = $this->_getTeamHelper()->assertEventValidAndViewable();

		$this->_assertViewEventTab($team, $category);

		if (!$this->_getEventModel()->canCommentOnEvent($event, $team, $category, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}

		$message = $this->getHelper('Editor')->getMessageText('message', $this->_input);
		$message = XenForo_Helper_String::autoLinkBbCode($message, false);

		$formatter = XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_BbCode_Filter');
		$parser = XenForo_BbCode_Parser::create($formatter);
		$message = $parser->render($message);
		if ($formatter->getDisabledTally())
		{
			$formatter->setStripDisabled(false);
			$message = $parser->render($message);
		}

		if(!$formatter->Teams_validateComment($message, $errors))
		{
			return $this->responseError($errors);
		}

		$visitor = XenForo_Visitor::getInstance();

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Comment');
		$dw->bulkSet(array(
			'team_id' => $team['team_id'],
			'content_id'=> $event['event_id'],
			'user_id' => $visitor['user_id'],
			'username' => $visitor['username'],
			'message' => $message,
			'content_type' => 'event'
		));

		$dw->setOption(
			Nobita_Teams_DataWriter_Comment::OPTION_MAX_TAGGED_USERS, $visitor->hasPermission('general', 'maxTaggedUsers')
		);

		$dw->preSave();

		if (!$dw->hasErrors())
		{
			$this->assertNotFlooding('post');
		}

		$dw->save();

		$commentModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');
		$comment = $commentModel->getCommentById($dw->get('comment_id'), array(
			'join' => Nobita_Teams_Model_Comment::FETCH_COMMENTER
		));

		$viewParams = array(
			'comment' => $commentModel->prepareComment($comment, $event, $team),
			'event' => $event,
			'team' => $team,
			'category' => $category,
			'categoryBreadcrumbs' => Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category')->getCategoryBreadcrumb($category)
		);

		return $this->responseView('Nobita_Teams_ViewPublic_Event_Comment', 'Team_event_comment', $viewParams);
	}
*/
	public function actionDelete()
	{
		list($event, $team, $category) = $this->_getTeamHelper()->assertEventValidAndViewable();

		$this->_assertViewEventTab($team, $category);

		if (!$this->_getEventModel()->canDeleteEvent($event, $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		if ($this->isConfirmedPost())
		{
			$this->_getEventModel()->deleteEvent($event['event_id']);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink('events', null, array('team_id' => $team['team_id']))
			);
		}
		else
		{
			return $this->_getTeamViewWrapper('events', $team, $category,
			 	$this->responseView('Nobita_Teams_ViewPublic_Event_Delete', 'Team_event_delete', array(
					'team' => $team,
					'category' => $category,
					'event' => $event
			)));
		}
	}

	public function actionLike()
	{
		$this->_assertPostOnly();
		list($event, $team, $category) = $this->_getTeamHelper()->assertEventValidAndViewable();

		$this->_assertViewEventTab($team, $category);

		if (!$this->_getEventModel()->canLikeEvent($event, $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$likeModel = Nobita_Teams_Container::getModel('XenForo_Model_Like');
		$existingLike = $likeModel->getContentLikeByLikeUser(
			'team_event', $event['event_id'], XenForo_Visitor::getUserId()
		);

		if ($existingLike)
		{
			$latestUsers = $likeModel->unlikeContent($existingLike);
		}
		else
		{
			$latestUsers = $likeModel->likeContent('team_event', $event['event_id'], $event['user_id']);
		}

		$liked = ($existingLike ? false : true);

		$event['likeUsers'] = $latestUsers;
		$event['likes'] += ($liked ? 1 : -1);
		$event['like_date'] = ($liked ? XenForo_Application::$time : 0);

		$viewParams = array(
			'event' => $event,
			'team' => $team,
			'category' => $category,
			'liked' => $liked,
		);

		return $this->responseView('Nobita_Teams_ViewPublic_Event_LikeConfirmed', '', $viewParams);
	}

	public function actionLikes()
	{
		list($event, $team, $category) = $this->_getTeamHelper()->assertEventValidAndViewable();

		$this->_assertViewEventTab($team, $category);

		$likes =  Nobita_Teams_Container::getModel('XenForo_Model_Like')->getContentLikes('team_event', $event['event_id']);
		if (!$likes)
		{
			return $this->responseError(new XenForo_Phrase('no_one_has_liked_this_post_yet'));
		}

		$viewParams = array(
			'event' => $event,
			'team' => $team,
			'category' => $category,
			'likes' => $likes
		);

		return $this->_getTeamViewWrapper('events', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_Event_Likes', 'Team_event_likes', $viewParams)
		);
	}


	public function actionReport()
	{
		list($event, $team, $category) = $this->_getTeamHelper()->assertEventValidAndViewable();
		$this->_assertViewEventTab($team, $category);

		if (!$event['canReport'])
		{
			return $this->responseNoPermission();
		}

		if ($this->isConfirmedPost())
		{
			$reportMessage = $this->_input->filterSingle('message', XenForo_Input::STRING);
			if (!$reportMessage)
			{
				return $this->responseError(new XenForo_Phrase('please_enter_reason_for_reporting_this_message'));
			}

			$this->assertNotFlooding('report');

			$reportContentData  = array(
				'event_id' => $event['event_id'],
				'event' => $event,
				'team' => $team
			);

			/* @var $reportModel XenForo_Model_Report */
			$reportModel = Nobita_Teams_Container::getModel('XenForo_Model_Report');
			$reportModel->reportContent('team_event', $reportContentData, $reportMessage);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				Nobita_Teams_Link::buildTeamLink(null, $team),
				new XenForo_Phrase('thank_you_for_reporting_this_message')
			);
		}
		else
		{
			$viewParams = array(
				'event' => $event,
				'team' => $team,
				'category' => $category
			);

			return $this->_getTeamViewWrapper('events', $team, $category,
				$this->responseView('Nobita_Teams_ViewPublic_Post_Report', 'Team_event_report', $viewParams)
			);
		}
	}

	protected function _assertViewEventTab(array $team, array $category)
	{
		if (!$this->_getTeamModel()->canViewTabAndContainer('events', $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}
}
