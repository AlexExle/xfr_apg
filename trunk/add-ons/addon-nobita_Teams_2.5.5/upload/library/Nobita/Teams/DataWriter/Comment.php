<?php

class Nobita_Teams_DataWriter_Comment extends XenForo_DataWriter
{
	const TEAM_DATA = 'teamData';

	const OPTION_MAX_MESSAGE_LENGTH = 'maxMessageLength';
	const OPTION_MAX_TAGGED_USERS = 'maxTaggedUsers';

	protected $_taggedUsers = array();
	protected $_contentDw;
	protected $_noAlerts = array();

	protected function _getFields()
	{
		return array(
			'xf_team_comment' => array(
				'comment_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),

				'content_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'content_type' => array(
					'type' => self::TYPE_BINARY,
					'allowedValues' => array('post', 'event'),
					'required' => true,
					'maxLength' => 25
				),

				'user_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'team_id' => array('type' => self::TYPE_UINT, 'required' => true),

				'username' => array('type' => self::TYPE_STRING, 'maxLength' => 50, 'default' => ''),
				'comment_date' => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'message' => array('type' => self::TYPE_STRING, 'required' => true,
					'requiredError' => 'please_enter_valid_message'),

				// 1.1.3
				'likes' => array('type' => self::TYPE_UINT, 'default' => 0),
				'like_users' => array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}')
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$commentId = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('xf_team_comment' => $this->_getCommentModel()->getCommentById($commentId));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'comment_id = ' . $this->_db->quote($this->getExisting('comment_id'));
	}

	/**
	* Gets the default set of options for this data writer.
	*
	* @return array
	*/
	protected function _getDefaultOptions()
	{
		$options = parent::_getDefaultOptions();
		$options[self::OPTION_MAX_MESSAGE_LENGTH] = Nobita_Teams_Option::get('commentLength');
		$options[self::OPTION_MAX_TAGGED_USERS] = 0;

		return $options;
	}

	protected function _preSave()
	{
		if(!$this->_getTeamData())
		{
			throw new XenForo_Exception("Must be set Team Data for comment writer.");
		}

		if ($this->isChanged('message'))
		{
			$this->_checkMessageValidity();
		}

		// do this auto linking after length counting
		/** @var $taggingModel XenForo_Model_UserTagging */
		$taggingModel = Nobita_Teams_Container::getModel('XenForo_Model_UserTagging');

		$this->_taggedUsers = $taggingModel->getTaggedUsersInMessage(
			$this->get('message'), $newMessage, 'bb'
		);

		$this->set('message', $newMessage);
	}

	/**
	 * Check that the contents of the message are valid, based on length, images, etc.
	 */
	protected function _checkMessageValidity()
	{
		$message = $this->get('message');

		$maxLength = $this->getOption(self::OPTION_MAX_MESSAGE_LENGTH);
		if ($maxLength && utf8_strlen($message) > $maxLength)
		{
			$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters', array('count' => $maxLength)), 'message');
			return false;
		}
	}

	protected function _postSave()
	{
		if ($this->isInsert())
		{
			$comment = $this->getMergedData();
			$team = $this->_getTeamData();

			$commentModel = $this->_getCommentModel();

			$maxTagged = $this->getOption(self::OPTION_MAX_TAGGED_USERS);
			$noAlerts = array();

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

				$commentModel->alertTaggedMembers($comment, $team, $alertTagged, $noAlerts);
			}

			$this->_noAlerts = array_merge($noAlerts, $this->_noAlerts);

			$contentDw = $this->_getContentDataWriter();
			if($contentDw)
			{
				$contentDw->handleNewCommentPublished($this);
				if($contentDw->hasChanges())
				{
					$contentDw->save();
				}
			}

			$db = $this->_db;
			$db->update('xf_team', array('last_updated' => XenForo_Application::$time),
				'team_id = ' . $db->quote($this->get('team_id'))
			);
		}

		if($this->isUpdate() && $this->isChanged('message'))
		{
			Nobita_Teams_Container::getModel('XenForo_Model_BbCode')->deleteBbCodeParseCacheForContent(
				'team_comment', $this->get('comment_id')
			);
		}

		if($this->isInsert() || ($this->isUpdate() && $this->isChanged('message')))
		{
			$this->_indexForSearch();
		}
	}

	public function getNoAlerts()
	{
		return $this->_noAlerts;
	}

	public function getContentType()
	{
		return 'team_comment';
	}

	public function getCommentId()
	{
		return $this->get('comment_id');
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

	protected function _getContentDataWriter()
	{
		if($this->_contentDw)
		{
			return $this->_contentDw;
		}

		if($this->get('content_type') == Nobita_Teams_Model_Comment::CONTENT_TYPE_POST)
		{
			$this->_contentDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Post');
			if($this->_contentDw->setExistingData($this->get('content_id')))
			{
				$this->_contentDw->setExtraData(Nobita_Teams_DataWriter_Post::TEAM_DATA, $this->_getTeamData());
				return $this->_contentDw;
			}
		}
		elseif($this->get('content_type') == Nobita_Teams_Model_Comment::CONTENT_TYPE_EVENT)
		{
			$this->_contentDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Event');
			if($this->_contentDw->setExistingData($this->get('content_id')))
			{
				$this->_contentDw->setExtraData(Nobita_Teams_DataWriter_Event::TEAM_DATA, $this->_getTeamData());
				return $this->_contentDw;
			}
		}

		return false;
	}

	protected function _indexForSearch()
	{
		$comment = $this->getMergedData();
		$indexer = new XenForo_Search_Indexer();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Nobita_Teams_Search_DataHandler_Comment');
		if ($dataHandler)
		{
			$dataHandler->insertIntoIndex($indexer, $comment, $this->_getTeamData());
		}
	}

	protected function _deleteFromSearchIndex()
	{
		$indexer = new XenForo_Search_Indexer();
		$mergedData = $this->getMergedData();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Nobita_Teams_Search_DataHandler_Comment');
		if ($dataHandler)
		{
			$dataHandler->deleteFromIndex($indexer, $mergedData);
		}
	}

	protected function _postDelete()
	{
		$db = $this->_db;
		$contentDw = $this->_getContentDataWriter();
		if($contentDw)
		{
			$contentDw->handleCommentDeleted($this);
			if($contentDw->hasChanges())
			{
				$contentDw->save();
			}
		}

		$this->_deleteFromSearchIndex();

		$commentId = $this->get('comment_id');
		$relateData = array(
			'XenForo_Model_Alert' => array(
				'method' => 'deleteAlerts',
				'args' => array('team_comment', $commentId)
			),
			'XenForo_Model_BbCode' => array(
				'method' => 'deleteBbCodeParseCacheForContent',
				'args' => array('team_comment', $commentId)
			)
		);

		foreach($relateData as $modelName => $options)
		{
			call_user_func_array(array($this->getModelFromCache($modelName), $options['method']), $options['args']);
		}
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}

	protected function _getCommentModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');
	}

}
