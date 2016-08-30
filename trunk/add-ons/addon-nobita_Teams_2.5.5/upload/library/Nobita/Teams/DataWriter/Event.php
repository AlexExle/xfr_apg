<?php

class Nobita_Teams_DataWriter_Event extends XenForo_DataWriter
{
	const TEAM_DATA = 'teamData';
	const TEAM_CATEGORY_DATA = 'teamCategoryData';

	/**
	 * Maximum number of images allowed in a message.
	 *
	 * @var string
	 */
	const OPTION_MAX_IMAGES = 'maxImages';

	/**
	 * Maximum pieces of media allowed in a message.
	 *
	 * @var string
	 */
	const OPTION_MAX_MEDIA = 'maxMedia';

	/**
	 * Holds the temporary hash used to pull attachments and associate them with this message.
	 *
	 * @var string
	 */
	const DATA_ATTACHMENT_HASH = 'attachmentHash';

	/**
	 * Option that controls the maximum number of characters that are allowed in
	 * a message.
	 *
	 * @var string
	 */
	const OPTION_MAX_MESSAGE_LENGTH = 'maxMessageLength';

	protected function _getFields()
	{
		return array(
			'xf_team_event' => array(
				'event_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'event_title' => array('type' => self::TYPE_STRING, 'maxLength' => 100, 'required' => true),
				'team_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'user_id' => array('type' => self::TYPE_UINT, 'required' => true),

				'username' => array('type' => self::TYPE_STRING, 'maxLength' => 50, 'default' => ''),
				'event_description' => array('type' => self::TYPE_STRING, 'required' => true),
				'event_type' => array('type' => self::TYPE_BINARY,
					'allowedValues' => array('public', 'admin', 'member'), 'default' => 'public', 'maxLength' => 25),

				'publish_date' => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),

				'begin_date' => array('type' => self::TYPE_UINT_FORCED, 'required' => true),
				'end_date' => array('type' => self::TYPE_UINT_FORCED, 'default' => 0),

				'allow_member_comment' => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'attach_count' => array('type' => self::TYPE_UINT_FORCED, 'default' => 0, 'max' => 65535),

				'likes' => array('type' => self::TYPE_UINT, 'default' => 0),
				'like_users' => array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}'),
				'timezone' => array('type' => self::TYPE_STRING, 'default' => XenForo_Application::getOptions()->guestTimeZone),

				'tags' => array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}'),

				'comment_count' => array('type' => self::TYPE_UINT, 'default' => 0),
				'latest_comment_ids' => array('type' => self::TYPE_JSON, 'default' => '[]'),
				'first_comment_date' => array('type' => self::TYPE_UINT, 'default' => 0),
				'last_comment_date' => array('type' => self::TYPE_UINT, 'default' => 0),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('xf_team_event' => $this->_getEventModel()->getEventById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'event_id = ' . $this->_db->quote($this->getExisting('event_id'));
	}

	protected function _preSave()
	{
		if (!$this->_getTeamData())
		{
			$this->error(new XenForo_Phrase('Teams_requested_team_not_found'), 'team_id');
			return false;
		}

		if ($this->get('event_type'))
		{
			$type = $this->get('event_type');

			$team = $this->_getTeamData();
			$category = $this->_getTeamCategoryData();

			if (!empty($team) && !empty($category))
			{
				$allowedTypes = $this->_getEventModel()->prepareEventTypesOnCreateOrEdit($team, $category);
				if ($this->isInsert())
				{
					if (isset($allowedTypes[$type]) && !$allowedTypes[$type])
					{
						/* verify the event type when create or edit event. depends who creating event. */
						$this->error(new XenForo_Phrase('Teams_invalid_event_type_provide'));
					}
				}

				if ($this->isUpdate() && $this->isChanged('event_type'))
				{
					if ($this->get('user_id') == XenForo_Visitor::getUserId()) // owner of event edit!
					{
						if (isset($allowedTypes[$type]) && !$allowedTypes[$type])
						{
							/* verify the event type when create or edit event. depends who creating event. */
							$this->error(new XenForo_Phrase('Teams_invalid_event_type_provide'), 'event_type');
						}
					}
				}
			}
		}

		if ($this->get('end_date'))
		{
			if ($this->get('begin_date') > $this->get('end_date'))
			{
				$this->error(new XenForo_Phrase('Teams_the_begin_date_great_than_end_date'), 'begin_date');
			}
		}

		if ($this->isChanged('event_description'))
		{
			$this->_checkDescriptionValidity();
		}
	}

	protected function _getDefaultOptions()
	{
		$options = parent::_getDefaultOptions();

		$options[self::OPTION_MAX_MESSAGE_LENGTH] = Nobita_Teams_Option::get('eventDescriptionLength');
		$options[self::OPTION_MAX_MEDIA] = XenForo_Application::get('options')->messageMaxMedia;
		$options[self::OPTION_MAX_IMAGES] = XenForo_Application::get('options')->messageMaxImages;

		return $options;
	}

	protected function _checkDescriptionValidity()
	{
		$message = $this->get('event_description');

		$maxLength = $this->getOption(self::OPTION_MAX_MESSAGE_LENGTH);
		if ($maxLength && utf8_strlen($message) > $maxLength)
		{
			$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters', array('count' => $maxLength)), 'event_description');
		}

		$maxImages = $this->getOption(self::OPTION_MAX_IMAGES);
		$maxMedia = $this->getOption(self::OPTION_MAX_MEDIA);
		if ($maxImages || $maxMedia)
		{
			$formatter = XenForo_BbCode_Formatter_Base::create('ImageCount', false);
			$parser = XenForo_BbCode_Parser::create($formatter);
			$parser->render($message);

			if ($maxImages && $formatter->getImageCount() > $maxImages)
			{
				$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_images', array('count' => $maxImages)), 'event_description');
			}
			if ($maxMedia && $formatter->getMediaCount() > $maxMedia)
			{
				$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_media', array('count' => $maxMedia)), 'event_description');
			}
		}
	}

	protected function _postSave()
	{
		$attachmentHash = $this->getExtraData(self::DATA_ATTACHMENT_HASH);
		if ($attachmentHash)
		{
			$this->_associateAttachments($attachmentHash);
		}

		$event = $this->getMergedData();
		$team = $this->_getTeamData();

		if ($this->isInsert())
		{
			$this->_getEventModel()->sendAlertWhenNewEventCreated($event, $team);
		}

		if ($this->isUpdate() && $this->isChanged('event_description'))
		{
			Nobita_Teams_Container::getModel('XenForo_Model_BbCode')->deleteBbCodeParseCacheForContent(
				'team_event', $this->get('event_id')
			);
		}

		if ($this->isInsert())
		{
			$db = $this->_db;
			$db->update('xf_team', array('last_updated' => XenForo_Application::$time),
				'team_id = ' . $db->quote($this->get('team_id'))
			);

			Nobita_Teams_Container::getModel('Nobita_Teams_Model_NewsFeed')->publish($event['team_id'], $event['event_id'], 'event');
		}

		if($this->isInsert() || ($this->isUpdate() && $this->isChanged('event_description')))
		{
			$this->_indexForSearch();
		}
	}

	public function handleNewCommentPublished(Nobita_Teams_DataWriter_Comment $commentDw)
	{
		$commentModel = $this->_getCommentModel();
		$lastCommentIds = $commentModel->getCommentIds(array(
			'event_id' => $this->get('event_id')
		), array(
			'order' => 'recent_comment',
			'limit' => 5,
		));

		$commentDate = $commentDw->get('comment_date');
		if (!$this->get('first_comment_date') || $commentDate < $this->get('first_comment_date'))
		{
			$this->set('first_comment_date', $commentDate);
		}

		$this->set('last_comment_date', $commentDate);
		$this->set('latest_comment_ids', $lastCommentIds);
		$this->set('comment_count', $this->get('comment_count') + 1);

		$db = $this->_db;
		$db->query("
			UPDATE xf_team_news_feed
			SET event_date = ?
			WHERE content_id = ? AND content_type = ?
		", array($commentDate, $this->get('event_id'), Nobita_Teams_Model_Comment::CONTENT_TYPE_EVENT));
	}

	public function handleCommentDeleted(Nobita_Teams_DataWriter_Comment $commentDw)
	{
		$commentModel = $this->_getCommentModel();
		$lastCommentIds = $commentModel->getCommentIds(array(
			'event_id' => $this->get('event_id')
		), array(
			'order' => 'recent_comment',
			'limit' => 5,
		));

		$this->set('latest_comment_ids', $lastCommentIds);
		$this->set('comment_count', max(0, $this->get('comment_count') - 1));

		$db = $this->_db;
		$updates = $db->fetchRow('
			SELECT MIN(comment_date) AS first_comment_date,
				MAX(comment_date) AS last_comment_date
			FROM xf_team_comment
			WHERE content_id = ? AND content_type = ?
		', array($this->get('event_id'), Nobita_Teams_Model_Comment::CONTENT_TYPE_EVENT));

		$this->bulkSet($updates);
	}

	public function getContentType()
	{
		return 'team_event';
	}

	public function getEventId()
	{
		return $this->get('event_id');
	}

	protected function _indexForSearch()
	{
		$indexer = new XenForo_Search_Indexer();
		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Nobita_Teams_Search_DataHandler_Event');
		if ($dataHandler)
		{
			$dataHandler->insertIntoIndex($indexer, $this->getMergedData(), $this->_getTeamData());
		}
	}

	protected function _deleteFromSearchIndex()
	{
		$indexer = new XenForo_Search_Indexer();
		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Nobita_Teams_Search_DataHandler_Event');
		if ($dataHandler)
		{
			$dataHandler->deleteFromIndex($indexer, $this->getMergedData());
		}
	}

	/**
	 * Associates attachments with this message.
	 *
	 * @param string $attachmentHash
	 */
	protected function _associateAttachments($attachmentHash)
	{
		$rows = $this->_db->update('xf_attachment', array(
			'content_type' => $this->getContentType(),
			'content_id' => $this->getEventId(),
			'temp_hash' => '',
			'unassociated' => 0
		), 'temp_hash = ' . $this->_db->quote($attachmentHash));
		if ($rows)
		{
			// TODO: ideally, this can be consolidated with other post-save message updates (see updateIpData)
			$this->set('attach_count', $this->get('attach_count') + $rows, '', array('setAfterPreSave' => true));

			$this->_db->update('xf_team_event', array(
				'attach_count' => $this->get('attach_count')
			), 'event_id = ' .  $this->_db->quote($this->getEventId()));
		}
	}

	protected function _postDelete()
	{
		$db = $this->_db;

		$eventId = $this->getEventId();
		$contentType = Nobita_Teams_Model_Comment::CONTENT_TYPE_EVENT;

		$commentIds = $db->fetchCol('
			SELECT comment_id
			FROM xf_team_comment
			WHERE content_id = ? AND content_type = ?
		', array($eventId, $contentType));

		$bbCodeModel = $this->getModelFromCache('XenForo_Model_BbCode');
		if ($commentIds)
		{
			foreach ($commentIds as $commentId)
			{
				$this->_getNewsFeedModel()->delete('team_comment', $commentId);
			}

			$this->_getAlertModel()->deleteAlerts('team_comment', $commentIds);
			$bbCodeModel->deleteBbCodeParseCacheForContent('team_comment', $commentIds);
			$db->delete('xf_team_comment', 'comment_id IN ('. $db->quote($commentIds) .')');
		}

		$this->_deleteFromSearchIndex();

		$relateData = array(
			'XenForo_Model_Alert' => array(
				'method' => 'deleteAlerts',
				'args' => array('team_event', array($eventId))
			),
			'XenForo_Model_BbCode' => array(
				'method' => 'deleteBbCodeParseCacheForContent',
				'args' => array('team_event', $eventId)
			),
			'XenForo_Model_Tag' => array(
				'method' => 'deleteContentTags',
				'args' => array('team_event', $eventId)
			),
			'Nobita_Teams_Model_NewsFeed' => array(
				'method' => 'delete',
				'args' => array($this->get('team_id'), $eventId, 'event')
			)
		);

		if($this->get('attach_count'))
		{
			$relateData['XenForo_Model_Attachment'] = array(
				'method' => 'deleteAttachmentsFromContentIds',
				'args' => array('team_event', array($eventId))
			);
		}

		foreach($relateData as $modelName => $options)
		{
			$model = $this->getModelFromCache($modelName);
			call_user_func_array(array($model, $options['method']), $options['args']);
		}
	}

	protected function _getTeamData()
	{
		if (!$this->getExtraData(self::TEAM_DATA))
		{
			$team = $this->_getTeamModel()->getFullTeamById($this->get('team_id'));
			$this->setExtraData(self::TEAM_DATA, $team ? $team : array());
		}

		return $this->getExtraData(self::TEAM_DATA);
	}

	protected function _getTeamCategoryData()
	{
		$team = $this->_getTeamData();
		if (!$team)
		{
			return array();
		}

		if (!$this->getExtraData(self::TEAM_CATEGORY_DATA))
		{
			$category = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category')->getCategoryById($team['team_category_id']);
			$this->setExtraData(self::TEAM_CATEGORY_DATA, $category ? $category : array());
		}

		return $this->getExtraData(self::TEAM_CATEGORY_DATA);
	}

	protected function _getCommentModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}

	protected function _getEventModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
	}
}
