<?php

class Nobita_Teams_DataWriter_Post extends XenForo_DataWriter
{
	const TEAM_DATA = 'teamData';

	/**
	 * Option that controls the maximum number of characters that are allowed in
	 * a message.
	 *
	 * @var string
	 */
	const OPTION_MAX_MESSAGE_LENGTH = 'maxMessageLength';

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
	 * Option that controls whether this should be published in the news feed. Defaults to true.
	 *
	 * @var string
	 */
	const OPTION_PUBLISH_FEED = 'publishFeed';

	const OPTION_MAX_TAGGED_USERS = 'maxTaggedUsers';
	protected $_taggedUsers = array();

	/**
	 * Holds the temporary hash used to pull attachments and associate them with this message.
	 *
	 * @var string
	 */
	const DATA_ATTACHMENT_HASH = 'attachmentHash';

	protected function _getFields()
	{
		return array(
			'xf_team_post' => array(
				'post_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'team_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'user_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'username' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 50),
				'post_date' => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'message' => array('type' => self::TYPE_STRING, 'requiredError' => 'please_enter_valid_message', 'required' => true),

				'message_state' => array('type' => self::TYPE_STRING,
					'allowedValues' => array('moderated', 'visible'), 'default' => 'visible'),
				'likes' => array('type' => self::TYPE_UINT_FORCED, 'default' => 0),
				'like_users' => array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}'),
				'comment_count' => array('type' => self::TYPE_UINT_FORCED, 'default' => 0),
				'warning_id' => array('type' => self::TYPE_UINT, 'default' => 0),

				'first_comment_date' => array('type' => self::TYPE_UINT, 'default' => 0),
				'last_comment_date' => array('type' => self::TYPE_UINT, 'default' => 0),
				'latest_comment_ids' => array('type' => self::TYPE_JSON, 'default' => '[]'),

				'sticky' => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'attach_count' => array('type' => self::TYPE_UINT_FORCED, 'default' => 0, 'max' => 65535),

				'share_privacy' => array(
					'type' => self::TYPE_BINARY,
					'allowedValues' => $this->_getPostModel()->getSharePrivacyList(),
					'default' => 'public',
					'maxLength' => 25
				)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('xf_team_post' => $this->_getPostModel()->getPostById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'post_id = ' . $this->_db->quote($this->getExisting('post_id'));
	}

	protected function _getDefaultOptions()
	{
		$options = XenForo_Application::get('options');

		return array(
			self::OPTION_MAX_MESSAGE_LENGTH => $options->messageMaxLength,
			self::OPTION_MAX_IMAGES => $options->messageMaxImages,
			self::OPTION_MAX_MEDIA => $options->messageMaxMedia,
			self::OPTION_PUBLISH_FEED => false, // we not support XF news feed
			self::OPTION_MAX_TAGGED_USERS => 0
		);
	}

	/**
	 * Check that the contents of the message are valid, based on length, images, etc.
	 */
	protected function _checkMessageValidity()
	{
		$message = $this->get('message');

		if (!utf8_strlen($message))
		{
			$this->error(new XenForo_Phrase('please_enter_valid_message'), 'message');
			return false;
		}

		$maxLength = $this->getOption(self::OPTION_MAX_MESSAGE_LENGTH);
		if ($maxLength && utf8_strlen($message) > $maxLength)
		{
			$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters', array('count' => $maxLength)), 'message');
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
				$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_images', array('count' => $maxImages)), 'message');
			}
			if ($maxMedia && $formatter->getMediaCount() > $maxMedia)
			{
				$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_media', array('count' => $maxMedia)), 'message');
			}
		}
	}

	public function getPostId()
	{
		return $this->get('post_id');
	}

	public function getPostContentType()
	{
		return 'team_post';
	}

	protected function _preSave()
	{
		if (!$this->_getTeamData())
		{
			$this->error(new XenForo_Phrase('Teams_requested_team_not_found'));
			return false;
		}

		if ($this->isChanged('message'))
		{
			$this->_checkMessageValidity();

			/** @var $taggingModel XenForo_Model_UserTagging */
			$taggingModel = Nobita_Teams_Container::getModel('XenForo_Model_UserTagging');

			$this->_taggedUsers = $taggingModel->getTaggedUsersInMessage(
				$this->get('message'), $newMessage, 'bb'
			);
			$this->set('message', $newMessage);
		}
	}

	protected function _postSave()
	{
		$attachmentHash = $this->getExtraData(self::DATA_ATTACHMENT_HASH);
		if ($attachmentHash)
		{
			$this->_associateAttachments($attachmentHash);
		}

		$db = $this->_db;
		if ($this->isInsert())
		{
			$db->update('xf_team', array('last_updated' => XenForo_Application::$time),
				'team_id = ' . $db->quote($this->get('team_id'))
			);

			$this->_getNewsFeedModel()->publish(
				$this->get('team_id'), $this->get('post_id'), 'post', array(), $this->get('post_date')
			);

			// Auto set user watch to post :D
			$this->_getPostModel()->watch($this->get('post_id'), $this->get('user_id'));
		}

		if ($this->isChanged('sticky'))
		{
			$teamDw = $this->_getTeamData();
			if ($this->getExisting('sticky'))
			{
				// old post is sticky
				$teamDw->updateStickyMessageCount(-1);
				$teamDw->save();
			}
			else
			{
				// new sticky
				$teamDw->updateStickyMessageCount(1);
				$teamDw->save();
			}
		}

		if($this->isChanged('team_id') && $this->get('sticky'))
		{
			$db->query('
				UPDATE xf_team
				SET sticky_message_count = IF(sticky_message_count > 1, sticky_message_count - 1, 0)
				WHERE team_id = ?
			', $this->getExisting('team_id'));

			$db->query('
				UPDATE xf_team
				SET sticky_message_count = sticky_message_count + 1
				WHERE team_id = ?
			', $this->get('team_id'));
		}

		if($this->isUpdate() && $this->isChanged('team_id'))
		{
			$db->query('
				UPDATE xf_team_news_feed
				SET team_id = ?
				WHERE content_id = ? AND content_type = ?
			', array(
				$this->get('team_id'), $this->get('post_id'), 'post'
			));
		}
	}

	/**
	 * Post-save handling, after the transaction is committed.
	 */
	protected function _postSaveAfterTransaction()
	{
		// perform alert actions if the message is visible, and is a new insert,
		// or is an update where the message state has changed from 'moderated'

		$post = $this->getMergedData();
		$team = $this->_getTeamExtraData();

		if ($this->get('message_state') == 'visible')
		{
			if ($this->isInsert() || $this->getExisting('message_state') == 'moderated')
			{
				$this->_publishToNewsFeed();

				$alertedUsers = array();
				if ($this->getExisting('message_state') == 'moderated')
				{
					$alertedUsers = array_merge(
						$alertedUsers,
						$this->_getModeratedNotifySentOut()
					);
				}

				$maxTagged = $this->getOption(self::OPTION_MAX_TAGGED_USERS);
				if ($maxTagged && $this->_taggedUsers)
				{
					if ($maxTagged > 0)
					{
						$alertTagged = array_slice($this->_taggedUsers, 0, $maxTagged, true);
					}
					else
					{
						$alertTagged = $this->_taggedUsers;
					}

					$alertedUsers = array_merge(
						$alertedUsers,
						$this->_getPostModel()->alertTaggedMembers($post, $team, $alertTagged, $alertedUsers)
					);
				}

				$this->_getPostModel()->sendNotificationsToUser($post, $team, $alertedUsers);
			}
		}

		if ($this->isInsert() && $this->get('message_state') == 'moderated')
		{
			$this->_getPostModel()->sendNotificationsToModerators($post, $team);
		}

		if ($this->isUpdate() && $this->isChanged('message'))
		{
			Nobita_Teams_Container::getModel('XenForo_Model_BbCode')->deleteBbCodeParseCacheForContent(
				'team_post', $this->get('post_id')
			);
		}

		$this->_indexForSearch();
	}

	protected function _indexForSearch()
	{
		if ($this->get('message_state') == 'visible')
		{
			if($this->isInsert() || $this->isChanged('message_state') || $this->isChanged('message'))
			{
				$this->_insertIntoSearchIndex();
			}
		}
		elseif ($this->isUpdate() && $this->get('message_state') != 'visible' && $this->getExisting('message_state') == 'visible')
		{
			$this->_deleteFromSearchIndex();
		}
	}

	protected function _insertIntoSearchIndex()
	{
		$post = $this->getMergedData();
		$indexer = new XenForo_Search_Indexer();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Nobita_Teams_Search_DataHandler_Post');
		if ($dataHandler)
		{
			$dataHandler->insertIntoIndex($indexer, $post, $this->_getTeamExtraData());
		}
	}

	/**
	 * Deletes this discussion from the search index.
	 */
	protected function _deleteFromSearchIndex()
	{
		$indexer = new XenForo_Search_Indexer();
		$mergedData = $this->getMergedData();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Nobita_Teams_Search_DataHandler_Post');
		if ($dataHandler)
		{
			$dataHandler->deleteFromIndex($indexer, $mergedData);
		}
	}

	protected function _getModeratedNotifySentOut()
	{
		$db = $this->_db;

		$postId = $this->get('post_id');

		$sentOut = $db->fetchCol('
			SELECT alerted_user_id AS user_id
			FROM xf_user_alert
			WHERE content_id = ?
				AND content_type = ?
				AND action = ?
		', array($postId, 'team_post', 'insert'));

		return $sentOut;
	}

	/**
	 * Associates attachments with this message.
	 *
	 * @param string $attachmentHash
	 */
	protected function _associateAttachments($attachmentHash)
	{
		$rows = $this->_db->update('xf_attachment', array(
			'content_type' => $this->getPostContentType(),
			'content_id' => $this->getPostId(),
			'temp_hash' => '',
			'unassociated' => 0
		), 'temp_hash = ' . $this->_db->quote($attachmentHash));
		if ($rows)
		{
			// TODO: ideally, this can be consolidated with other post-save message updates (see updateIpData)
			$this->set('attach_count', $this->get('attach_count') + $rows, '', array('setAfterPreSave' => true));

			$this->_db->update('xf_team_post', array(
				'attach_count' => $this->get('attach_count')
			), 'post_id = ' .  $this->_db->quote($this->getPostId()));
		}
	}

	protected function _getTeamExtraData()
	{
		if (!$this->getExtraData(self::TEAM_DATA))
		{
			$team = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getFullTeamById($this->get('team_id'));
			$this->setExtraData(self::TEAM_DATA, $team ? $team : array());
		}

		return $this->getExtraData(self::TEAM_DATA);
	}

	protected function _getTeamData()
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
		if ($dw->setExistingData($this->get('team_id')))
		{
			return $dw;
		}

		return false;
	}

	protected function _postDelete()
	{
		$db = $this->_db;
		$postId = $this->get('post_id');

		$bbCodeModel = $this->getModelFromCache('XenForo_Model_BbCode');
		$commentIds = $db->fetchCol('
			SELECT comment_id
			FROM xf_team_comment
			WHERE content_id = ? AND content_type = ?
		', array($postId, Nobita_Teams_Model_Comment::CONTENT_TYPE_POST));
		if ($commentIds)
		{
			$this->_getAlertModel()->deleteAlerts('team_comment', $commentIds);
			$bbCodeModel->deleteBbCodeParseCacheForContent('team_comment', $commentIds);
			$db->delete('xf_team_comment', 'comment_id IN (' . $db->quote($commentIds) .')');
		}

		if ($this->get('sticky'))
		{
			$db->query("UPDATE xf_team
				SET sticky_message_count = IF(sticky_message_count > 1, sticky_message_count - 1, 0)
				WHERE team_id = ?", array($this->get('team_id')));
		}

		if ($this->get('likes'))
		{
			$this->_deleteLikes();
		}

		$relateData = array(
			'XenForo_Model_Alert' => array(
				'method' => 'deleteAlerts',
				'args' => array('team_post', array($postId))
			),
			'XenForo_Model_NewsFeed' => array(
				'method' => 'delete',
				'args' => array('team_post', $postId)
			),
			'XenForo_Model_BbCode' => array(
				'method' => 'deleteBbCodeParseCacheForContent',
				'args' => array('team_post', $postId)
			),
			'Nobita_Teams_Model_NewsFeed' => array(
				'method' => 'delete',
				'args' => array($this->get('team_id'), $postId, 'post')
			)
		);

		if($this->get('attach_count'))
		{
			$relateData['XenForo_Model_Attachment'] = array(
				'method' => 'deleteAttachmentsFromContentIds',
				'args' => array('team_post', array($postId))
			);
		}

		$this->_deleteFromSearchIndex();

		foreach($relateData as $modelName => $options)
		{
			$model = $this->getModelFromCache($modelName);
			call_user_func_array(array($model, $options['method']), $options['args']);
		}
	}

	/* BUILDING NEWS FEED SYSTEM. */
	/**
	 * Publishes an insert or update event to the news feed
	 */
	protected function _publishToNewsFeed()
	{
		$this->getModelFromCache('XenForo_Model_NewsFeed')->publish(
			$this->get('user_id'),
			$this->get('username'),
			$this->getPostContentType(),
			$this->getPostId(),
			'insert'
		);
	}

	/**
	 * Delete all like entries for content.
	 */
	protected function _deleteLikes()
	{
		$updateUserLikeCounter = ($this->get('message_state') == 'visible');
		Nobita_Teams_Container::getModel('XenForo_Model_Like')->deleteContentLikes(
			$this->getPostContentType(), $this->getPostId(), $updateUserLikeCounter
		);
	}

	public function handleNewCommentPublished(Nobita_Teams_DataWriter_Comment $commentDw)
	{
		$commentModel = $this->_getCommentModel();
		$lastCommentIds = $commentModel->getCommentIds(array(
			'post_id' => $this->get('post_id')
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
		", array(
			$commentDate, $this->get('post_id'), Nobita_Teams_Model_Comment::CONTENT_TYPE_POST
		));

		// Auto set commenter watch to post.
		$this->_getPostModel()->watch($this->get('post_id'), $commentDw->get('user_id'));
		$this->_getCommentModel()->sendNotificationsForPost(
			$commentDw->getMergedData(), $this->getMergedData(), $this->_getTeamExtraData(), $commentDw->getNoAlerts()
		);
	}

	public function handleCommentDeleted(Nobita_Teams_DataWriter_Comment $commentDw)
	{
		$commentModel = $this->_getCommentModel();
		$lastCommentIds = $commentModel->getCommentIds(array(
			'post_id' => $this->get('post_id')
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
		', array($this->get('post_id'), Nobita_Teams_Model_Comment::CONTENT_TYPE_POST));

		$this->bulkSet($updates);
	}

	protected function _getNewsFeedModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_NewsFeed');
	}

	protected function _getCommentModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');
	}

	protected function _getPostModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
	}
}
