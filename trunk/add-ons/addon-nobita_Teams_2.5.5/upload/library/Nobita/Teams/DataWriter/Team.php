<?php

class Nobita_Teams_DataWriter_Team extends XenForo_DataWriter
{
	const DATA_THREAD_WATCH_DEFAULT = 'watchDefault';

	// added 2.3.6
	const OPTION_CUSTOM_TITLE_DISALLOWED = 'customTitleDisallowed';
	const OPTION_ADMIN_EDIT = 'adminEdit';

	/**
	 * The custom fields to be updated. Use setCustomFields to manage this.
	 *
	 * @var array|null
	 */
	protected $_updateCustomFields = null;

	/**
	 * Holds the reason for soft deletion.
	 *
	 * @var string
	 */
	const DATA_DELETE_REASON = 'deleteReason';

	/**
	 * Option that controls whether this should be published in the news feed. Defaults to true.
	 *
	 * @var string
	 */
	const OPTION_PUBLISH_FEED = 'publishFeed';

	/**
	 * Option that controls whether the data in this discussion should be indexed for
	 * search. If this value is set inconsistently for the same discussion (and messages within),
	 * data might be orphaned in the search index. Defaults to true.
	 *
	 * @var string
	 */
	const OPTION_INDEX_FOR_SEARCH = 'indexForSearch';

	const IMPORT_EXTERNAL_DATA_MODE = 'importMode';

	/**
	 * All custom url must have a least x characters.
	 *
	 * @var integer
	 */
	public static $urlThreshold = 8;

	protected function _getFields()
	{
		return array(
			'xf_team' => array(
				'team_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'title' => array('type' => self::TYPE_STRING, 'maxLength' => 100, 'required' => true,
					'requiredError' => 'please_enter_valid_title'),
				'tag_line' => array('type' => self::TYPE_STRING, 'maxLength' => 100, 'required' => true),
				'custom_url' => array('type' => self::TYPE_STRING, 'maxLength' => 25, 'default' => null,
					'verification' => array('$this', '_verifyCustomURL')),
				'user_id' => array('type' => self::TYPE_UINT, 'required' => true),

				'username' => array('type' => self::TYPE_STRING, 'maxLength' => 50, 'default' => ''),
				'team_state' => array('type' => self::TYPE_STRING,
					'allowedValues' => array('visible', 'moderated', 'deleted'), 'default' => 'visible'),
				'team_date' => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'team_category_id' => array('type' => self::TYPE_UINT, 'required' => true,
					'verification' => array('$this', '_validateCategoryId')),

				'team_avatar_date' => array('type' => self::TYPE_UINT, 'default' => 0),

				//'message_count' => array('type' => self::TYPE_UINT_FORCED, 'default' => 0),
				'member_count' => array('type' => self::TYPE_UINT_FORCED, 'default' => 0),

				'warning_id' => array('type' => self::TYPE_UINT, 'default' => 0),
				'last_updated' => array('type' => self::TYPE_UINT, 'default' => 0),

				// added 1.0.9 BETA 1 modified on version 1.2
				'cover_date' => array('type' => self::TYPE_UINT, 'default' => 0),

				'privacy_state' => array('type' => self::TYPE_BINARY,
					'allowedValues' => array('open', 'closed', 'secret'), 'default' => 'open', 'maxLength' => 25
				),

				'sticky_message_count' => array('type' => self::TYPE_UINT, 'default' => 0),
				'tags' => array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}'),

				'thread_count' => array('type' => self::TYPE_UINT, 'default' => 0),
				'thread_post_count' => array('type' => self::TYPE_UINT, 'default' => 0),
			),
			'xf_team_profile' => array(
				'team_id' => array('type' => self::TYPE_UINT, 'default' => array('xf_team', 'team_id'), 'required' => true),
				'about' => array('type' => self::TYPE_STRING, 'default' => ''),
				'custom_fields' => array('type' => self::TYPE_SERIALIZED, 'default' => ''),
				'member_request_count' => array('type' => self::TYPE_UINT, 'default' => 0),

				'ribbon_text' => array('type' => self::TYPE_STRING, 'maxLength' => 25, 'default' => '', 'verification' => array('$this', '_verifyRibbonText')),
				'ribbon_display_class' => array('type' => self::TYPE_STRING, 'maxLength' => 50, 'default' => ''),

				// 2.2.2
				'invite_count' => array('type' => self::TYPE_UINT, 'default' => 0),

				'remove_inactive_date' => array('type' => self::TYPE_UINT, 'default' => 0),
				'staff_list' => array('type' => self::TYPE_JSON, 'default' => '[]'),
			),
			'xf_team_privacy' => array(
				'team_id' => array('type' => self::TYPE_UINT, 'default' => array('xf_team', 'team_id'), 'required' => true),

				'allow_guest_posting' => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'allow_member_posting' => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'always_moderate_join' => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'always_moderate_posting' => array('type' => self::TYPE_BOOLEAN, 'default' => 1),

				// added 1.1.2 @20-04-2014
				'always_req_message' => array('type' => self::TYPE_BOOLEAN, 'default' => 1),

				// 1.1.3 beta
				'allow_member_event' => array('type' => self::TYPE_BOOLEAN, 'default' => 0),

				// 1.2.0 RC2
				'disable_tabs' => array('type' => self::TYPE_BINARY, 'maxLength' => 100, 'default' => ''),

				// 2.2.2
				'rules' 			=> array('type' => self::TYPE_STRING, 'default' => ''),
				'last_update_rule' 	=> array('type' => self::TYPE_UINT_FORCED, 'default' => 0),
				'last_update_user_id' => array('type' => self::TYPE_UINT, 'default' => 0)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$teamID = $this->_getExistingPrimaryKey($data, 'team_id'))
		{
			return false;
		}

		if (!$teamInfo = $this->_getTeamModel()->getFullTeamById($teamID))
		{
			return false;
		}

		return $this->getTablesDataFromArray($teamInfo);
	}

	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return  'team_id = ' . $this->_db->quote($this->getExisting('team_id'));
	}

	/**
	* Gets the default set of options for this data writer.
	*
	* @return array
	*/
	protected function _getDefaultOptions()
	{
		$options = XenForo_Application::get('options');

		return array(
			self::OPTION_PUBLISH_FEED => true,
			self::OPTION_INDEX_FOR_SEARCH => true,

			self::OPTION_CUSTOM_TITLE_DISALLOWED => preg_split('/\r?\n/', Nobita_Teams_Option::get('disallowedRibbonTitles')),
			self::OPTION_ADMIN_EDIT => false,
		);
	}

	protected function _verifyRibbonText(&$text)
	{
		if (!$this->getOption(self::OPTION_ADMIN_EDIT))
		{
			if ($text === $this->getExisting('ribbon_text'))
			{
				return true; // can always keep the existing value
			}

			if ($text !== XenForo_Helper_String::censorString($text))
			{
				$this->error(new XenForo_Phrase('Teams_please_enter_ribbon_text_that_does_not_contain_any_censored_words'), 'ribbon_text');
				return false;
			}

			$disallowed = $this->getOption(self::OPTION_CUSTOM_TITLE_DISALLOWED);
			if ($disallowed)
			{
				foreach ($disallowed AS $value)
				{
					$value = trim($value);
					if ($value === '')
					{
						continue;
					}
					if (stripos($text, $value) !== false)
					{
						$this->error(new XenForo_Phrase('Teams_please_enter_another_ribbon_text_disallowed_words'), 'ribbon_text');
						return false;
					}
				}
			}
		}

		return true;
	}

	protected function _validateCategoryId(&$categoryId)
	{
		$category = $this->_getCategoryModel()->getCategoryById($categoryId);
		if (!$category)
		{
			$this->error(new XenForo_Phrase('requested_category_not_found'), 'team_category_id');
			return false;
		}

		return true;
	}

	protected function _verifyCustomURL(&$data)
	{
		if (!$data)
		{
			$data = null;
			return true;
		}

		if ($this->getExisting('custom_url') === $this->get('custom_url')
			&& $this->get('custom_url') !== null
		)
		{
			return true;
		}

		if (strpos($data, '.') !== false)
		{
			$this->error(new XenForo_Phrase('Teams_please_enter_url_using_alphanumeric'), 'custom_url');
			return false;
		}

		if (!preg_match('/^[a-z0-9_\-]+$/i', $data))
		{
			$this->error(new XenForo_Phrase('Teams_please_enter_url_using_alphanumeric'), 'custom_url');
			return false;
		}

		if ($data === strval(intval($data)) || $data == '-')
		{
			$this->error(new XenForo_Phrase('Teams_url_contain_more_numbers_hyphen'), 'custom_url');
			return false;
		}

		if (in_array(strtolower($data), Nobita_Teams_Blacklist::$blacklist))
		{
			throw new XenForo_Exception("Your defined URL disallow to use. Please try with difference URL.", true);
			return false;
		}

		return true;
	}

	public function setCustomFields(array $fieldValues, array $fieldsShown = null)
	{
		$fieldModel = $this->_getFieldModel();
		$fields = $fieldModel->getTeamFieldsForEdit($this->get('team_category_id'));

		if (!is_array($fieldsShown))
		{
			$fieldsShown = array_keys($fields);
		}

		if ($this->get('team_id') && !$this->_importMode)
		{
			$existingValues = $fieldModel->getTeamFieldValues($this->get('team_id'));
		}
		else
		{
			$existingValues = array();
		}

		$finalValues = array();

		foreach ($fieldsShown AS $fieldId)
		{
			if (!isset($fields[$fieldId]))
			{
				continue;
			}

			$field = $fields[$fieldId];
			$multiChoice = ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect');

			if ($multiChoice)
			{
				// multi selection - array
				$value = array();
				if (isset($fieldValues[$fieldId]))
				{
					if (is_string($fieldValues[$fieldId]))
					{
						$value = array($fieldValues[$fieldId]);
					}
					else if (is_array($fieldValues[$fieldId]))
					{
						$value = $fieldValues[$fieldId];
					}
				}
			}
			else
			{
				// single selection - string
				if (isset($fieldValues[$fieldId]))
				{
					if (is_array($fieldValues[$fieldId]))
					{
						$value = count($fieldValues[$fieldId]) ? strval(reset($fieldValues[$fieldId])) : '';
					}
					else
					{
						$value = strval($fieldValues[$fieldId]);
					}
				}
				else
				{
					$value = '';
				}
			}

			$existingValue = (isset($existingValues[$fieldId]) ? $existingValues[$fieldId] : null);

			if (!$this->_importMode)
			{
				$valid = $fieldModel->verifyTeamFieldValue($field, $value, $error);
				if (!$valid)
				{
					$this->error($error, "custom_field_$fieldId");
					continue;
				}

				if ($field['required'] && ($value === '' || $value === array()))
				{
					$this->error(new XenForo_Phrase('please_enter_value_for_all_required_fields'), "required");
					continue;
				}
			}

			if ($value !== $existingValue)
			{
				$finalValues[$fieldId] = $value;
			}
		}

		$this->_updateCustomFields = $this->_filterValidFields($finalValues + $existingValues, $fields);
		$this->set('custom_fields', $this->_updateCustomFields);
	}

	protected function _filterValidFields(array $values, array $fields)
	{
		$newValues = array();
		foreach ($fields AS $field)
		{
			if (isset($values[$field['field_id']]))
			{
				$newValues[$field['field_id']] = $values[$field['field_id']];
			}
		}

		return $newValues;
	}

	protected function _preSave()
	{
		if ($this->get('custom_url') && $this->isChanged('custom_url'))
		{
			$conflict = $this->_getTeamModel()->getTeamByCustomUrl($this->get('custom_url'));
			if ($conflict)
			{
				$this->error(new XenForo_Phrase('Teams_url_must_be_unique'));
				return false;
			}

			#if (utf8_strlen($this->get('custom_url')) < 6)
			if (strlen($this->get('custom_url')) < self::$urlThreshold)
			{
				$this->error(new XenForo_Phrase('Teams_please_enter_value_that_is_at_least_x_characters_long', array(
					'count' => self::$urlThreshold
				)));
				return false;
			}
		}

		if ($this->isChanged('team_category_id'))
		{
			if ($this->isUpdate() && !is_array($this->_updateCustomFields))
			{
				$fieldModel = $this->_getFieldModel();

				$this->_updateCustomFields = $this->_filterValidFields(
					$fieldModel->getTeamFieldValues($this->get('team_id')),
					$fieldModel->getTeamFieldsForEdit($this->get('team_category_id'))
				);
				$this->set('custom_fields', $this->_updateCustomFields);
			}
		}

		if ($this->get('user_id'))
		{
			$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserById($this->get('user_id'));

			if ($user)
			{
				$this->set('username', $user['username']);
			}
			else
			{
				$this->set('user_id', 0);
			}
		}

		if ($this->get('rules') && $this->isChanged('rules'))
		{
			$maxLength = Nobita_Teams_Option::get('rulesLength');

			if ($maxLength && utf8_strlen($this->get('rules')) > $maxLength)
			{
				$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters',
					array('count' => $maxLength))
				);
			}
		}

		if ($this->isInsert() && !$this->get('rules'))
		{
			$this->updateEmptyRules();
		}

		if ($this->isInsert())
		{
			$this->set('last_updated', XenForo_Application::$time);
		}
	}

	protected function _postSave()
	{
		$postSaveChanges = array();
		$db = $this->_db;

		$teamId = $this->get('team_id');

		$this->updateCustomFields();

		$removed = false;
		if ($this->isChanged('team_state'))
		{
			if ($this->get('team_state') == 'visible')
			{
				$this->_teamMadeVisible($postSaveChanges);
			}
			else if ($this->isUpdate() && $this->getExisting('team_state') == "visible")
			{
				$this->_teamRemoved();
				$removed = true;
			}

			$this->_updateDeletionLog();
			$this->_updateModerationQueue();

			$this->_updateTaggingVisibility();
		}

		$catDw = $this->_getCategoryDwForUpdate();
		if ($catDw && !$removed)
		{
			$catDw->teamUpdate($this);
			$catDw->save();
		}

		if ($this->isUpdate() && $this->isChanged('user_id'))
		{
			if ($this->get('user_id') && $this->get('team_state') == 'visible' && !$this->isChanged('team_state'))
			{
				$this->_getTeamModel()->updateManageTeamCountForUser($this->get('user_id'));
			}

			if ($this->getExisting('user_id') && $this->getExisting('team_state') == 'visible')
			{
				$this->_getTeamModel()->updateManageTeamCountForUser($this->getExisting('user_id'));
			}
		}

		if ($this->getOption(self::OPTION_PUBLISH_FEED))
		{
			if ($this->isInsert() || ($this->isUpdate()
				&& $this->isChanged('team_state')
				&& $this->get('team_state') == 'visible'
			))
			{
				$this->_publishToNewsFeed();
			}
		}

		if ($this->isUpdate() && $this->isChanged('cover_date') && $this->get('cover_date'))
		{
			// group has new cover?
			$tempCover  = XenForo_Helper_File::getInternalDataPath() . '/groups/covers/' . md5($this->get('team_id'));
			if (file_exists($tempCover))
			{
				@unlink($tempCover);
			}
		}

		if ($this->getOption(self::OPTION_INDEX_FOR_SEARCH))
		{
			$this->_indexForSearch();
		}

		if ($this->isUpdate())
		{
			/*$visitor = XenForo_Visitor::getInstance();
			if ($this->isChanged('privacy_state'))
			{
				$newPrivacy = $this->get('privacy_state');
				$oldPrivacy = $this->getExisting('privacy_state');

				$message = new XenForo_Phrase('Teams_changed_the_team_privacy_setting_from_x_to_y', array(
					'name' => $visitor['username'],
					'userId' => $visitor['user_id'],
					'old' => ucfirst($oldPrivacy),
					'new' => ucfirst($newPrivacy)
				), false);

				Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post')->publishNewsFeed(0, 'member', array(
					'team_id' 	=> $this->get('team_id'),
					'user_id' 	=> $visitor->user_id,
					'username' 	=> $visitor->username,
					'message' 	=> $message
				));
			}

			if ($this->isChanged('ribbon_text') || $this->isChanged('ribbon_display_class'))
			{
				$message = new XenForo_Phrase('Teams_x_updated_new_ribbon_for_team', array(
					'name' => $visitor['username'],
					'userId' => $visitor['user_id']
				), false);

				Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post')->publishNewsFeed(0, 'member', array(
					'team_id' 	=> $this->get('team_id'),
					'user_id' 	=> $visitor->user_id,
					'username' 	=> $visitor->username,
					'message' 	=> $message
				));
			}*/

			$postSaveChanges['last_updated'] = XenForo_Application::$time;
		}

		if ($postSaveChanges)
		{
			$this->_db->update('xf_team', $postSaveChanges, 'team_id = ' . $this->_db->quote($this->get('team_id')));
		}
	}

	protected function _updateTaggingVisibility()
	{
		$newState = $this->get('team_state');
		$oldState = $this->getExisting('team_state');

		if ($newState == 'visible' && $oldState != 'visible')
		{
			$newVisible = true;
		}
		else if ($oldState == 'visible' && $newState != 'visible')
		{
			$newVisible = false;
		}
		else
		{
			return;
		}

		/** @var XenForo_Model_Tag $tagModel */
		$tagModel = Nobita_Teams_Container::getModel('XenForo_Model_Tag');
		$tagModel->updateContentVisibility('team', $this->get('team_id'), $newVisible);
	}


	/**
	* Post-save handling, after the transaction is committed.
	*/
	protected function _postSaveAfterTransaction()
	{
		$importMode = $this->getExtraData(self::IMPORT_EXTERNAL_DATA_MODE);
		if ($this->isInsert() && ! $importMode)
		{
			Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->insertMember(
				$this->get('user_id'), $this->get('team_id'),
				'admin', 'accept',
				array(), array('insert' => true)
			);

			Nobita_Teams_Container::getModel('Nobita_Teams_Model_CategoryWatch')->sendNotificationToWatchUsers(
				$this->getMergedData()
			);
		}

	}

	public function updateCustomFields()
	{
		if (is_array($this->_updateCustomFields))
		{
			$teamId = $this->get('team_id');

			$this->_db->query('DELETE FROM xf_team_field_value WHERE team_id = ?', $teamId);

			foreach ($this->_updateCustomFields AS $fieldId => $value)
			{
				if (is_array($value))
				{
					$value = serialize($value);
				}
				$this->_db->query('
					INSERT INTO xf_team_field_value
						(team_id, field_id, field_value)
					VALUES
						(?, ?, ?)
					ON DUPLICATE KEY UPDATE
						field_value = VALUES(field_value)
				', array($teamId, $fieldId, $value));
			}
		}
	}

	protected function _teamRemoved()
	{
		if ($this->get('user_id'))
		{
			$this->_getTeamModel()->updateManageTeamCountForUser($this->get('user_id'));

			$this->_db->query('
				UPDATE xf_user
				SET team_ribbon_id = 0
				WHERE team_ribbon_id = ?
			', array($this->get('team_id')));
		}

		$catDw = $this->_getCategoryDwForUpdate();
		if ($catDw)
		{
			$catDw->teamRemoved($this);
			$catDw->save();
		}
	}

	protected function _teamMadeVisible(array &$postSaveChanges)
	{
		if ($this->get('user_id') && $this->get('team_state') == 'visible')
		{
			$this->_getTeamModel()->updateManageTeamCountForUser($this->get('user_id'));
		}
	}


	/**
	 *	Gets the current value of the team ID for this team.
	 *
	 *	@return integer
	 */
	public function getTeamId()
	{
		return $this->get('team_id');
	}

	public function getContentType()
	{
		return 'team';
	}

	/**
	 * Publishes an insert or update event to the news feed
	 */
	protected function _publishToNewsFeed()
	{
		$this->_getNewsFeedModel()->publish(
			$this->get('user_id'),
			$this->get('username'),
			$this->getContentType(),
			$this->getTeamId(),
			'insert'
		);
	}

	/**
	 * Removes an already published news feed item
	 */
	protected function _deleteFromNewsFeed()
	{
		$this->_getNewsFeedModel()->delete(
			$this->getContentType(),
			$this->getTeamId()
		);
	}

	protected function _updateDeletionLog($hardDelete = false)
	{
		if ($hardDelete
			|| ($this->isChanged('team_state') && $this->getExisting('team_state') == 'deleted')
		)
		{
			Nobita_Teams_Container::getModel('XenForo_Model_DeletionLog')->removeDeletionLog(
				$this->getContentType(), $this->getTeamId()
			);
		}

		if ($this->isChanged('team_state') && $this->get('team_state') == 'deleted')
		{
			$reason = $this->getExtraData(self::DATA_DELETE_REASON);
			Nobita_Teams_Container::getModel('XenForo_Model_DeletionLog')->logDeletion(
				$this->getContentType(), $this->getTeamId(), $reason
			);
		}
	}

	protected function _updateModerationQueue()
	{
		if (!$this->isChanged('team_state'))
		{
			return;
		}

		if ($this->get('team_state') == 'moderated')
		{
			Nobita_Teams_Container::getModel('XenForo_Model_ModerationQueue')->insertIntoModerationQueue(
				$this->getContentType(), $this->getTeamId(), $this->get('team_date')
			);
		}
		else if ($this->getExisting('team_state') == 'moderated')
		{
			Nobita_Teams_Container::getModel('XenForo_Model_ModerationQueue')->deleteFromModerationQueue(
				$this->getContentType(), $this->getTeamId()
			);
		}
	}

	protected function _postDelete()
	{
		$db = $this->_db;

		$teamId = $this->get('team_id');
		$teamIdQuoted = $db->quote($teamId);

		$postIds = $db->fetchCol('
			SELECT post_id
			FROM xf_team_post
			WHERE team_id = ?
		', $teamId);

		$alertModel = $this->getModelFromCache('XenForo_Model_Alert');
		$bbCodeModel = $this->getModelFromCache('XenForo_Model_BbCode');
		$attachmentModel = $this->getModelFromCache('XenForo_Model_Attachment');
		$newsFeedModel = $this->getModelFromCache('XenForo_Model_NewsFeed');

		if($postIds)
		{
			foreach($postIds as $postId)
			{
				$newsFeedModel->delete('team_post', $postId);
			}

			$alertModel->deleteAlerts('team_post', $postIds);
			$bbCodeModel->deleteBbCodeParseCacheForContent('team_post', $postIds);
			$attachmentModel->deleteAttachmentsFromContentIds('team_post', $postIds);

			$db->delete('xf_team_post', 'team_id = ' .$teamIdQuoted);
		}

		$db->delete('xf_team_field_value', 'team_id = ' . $teamIdQuoted);
		$db->delete('xf_team_feature', 'team_id = ' . $teamIdQuoted);
		$db->delete('xf_team_news_feed', 'team_id = ' . $teamIdQuoted);

		$commentIds = $db->fetchCol('SELECT comment_id FROM xf_team_comment WHERE team_id = ?', $teamId);
		if ($commentIds)
		{
			$alertModel->deleteAlerts('team_comment', $commentIds);
			$bbCodeModel->deleteBbCodeParseCacheForContent('team_comment', $commentIds);

			$db->delete('xf_team_comment', 'team_id = ' .$teamIdQuoted);
		}

		$memberUserIds = $db->fetchCol('
			SELECT user_id
			FROM xf_team_member
			WHERE team_id = ?
		', $teamId);

		$queueData = array(
			'userIds' => $memberUserIds
		);
		Nobita_Teams_Model_Deferred::queue(
			$teamId, 'team_delete', $queueData
		);
		$db->delete('xf_team_member', 'team_id = ' . $teamIdQuoted);

		// decrease counter for user
		// bug: https://nobita.me/threads/256/
		$this->_getTeamModel()->updateManageTeamCountForUser($this->get('user_id'));

		// update thread
		$db->query("
			UPDATE xf_thread
			SET team_id = 0, discussion_type = ''
			WHERE team_id = ?
		", $teamId);

		// 1.1.3 support event!
		$eventIds = $db->fetchCol('SELECT event_id FROM xf_team_event WHERE team_id = ?', $teamId);
		if ($eventIds)
		{
			$attachmentModel->deleteAttachmentsFromContentIds('team_event', $eventIds);
			$alertModel->deleteAlerts('team_event', $eventIds);
			$bbCodeModel->deleteBbCodeParseCacheForContent('team_event', $eventIds);

			$db->delete('xf_team_event', 'team_id = ' . $teamIdQuoted);
		}

		if ($this->getExisting('team_state') == 'visible')
		{
			$this->_teamRemoved();
		}
		$this->_updateDeletionLog(true);
		Nobita_Teams_Container::getModel('XenForo_Model_ModerationQueue')->deleteFromModerationQueue(
			'team', $this->get('team_id')
		);

		Nobita_Teams_Container::getModel('Nobita_Teams_Model_Logo')->deleteLogo($this->getTeamId(), false);

		if ($this->get('cover_date'))
		{
			Nobita_Teams_Container::getModel('Nobita_Teams_Model_Cover')->deleteCover($this->get('team_id'), false);
		}
		// Delete temp cover
		$tempCover = XenForo_Helper_File::getInternalDataPath() . '/groups/covers/' . md5($this->get('team_id'));
		if (file_exists($tempCover))
		{
			@unlink($tempCover);
		}

		$this->_deleteFromNewsFeed();
		$alertModel->deleteAlerts('team', $teamId);

		try
		{
			$db->query("
				UPDATE sonnb_xengallery_album
				SET team_id = 0
				WHERE team_id = ?
			", $teamId);
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$db->query("
				UPDATE xengallery_media
				SET social_group_id = 0
				WHERE social_group_id = ?
			", $this->get('team_id'));
		}
		catch(Zend_Db_Exception $e) {}

		/** @var XenForo_Model_Tag $tagModel */
		$tagModel = Nobita_Teams_Container::getModel('XenForo_Model_Tag');
		$tagModel->deleteContentTags('team', $this->get('team_id'));

		$this->deleteAllTeamForums();

		$this->_deleteFromSearchIndex();
	}

	public function deleteAllTeamForums()
	{
		$db = $this->_db;
		$nodeIds = $db->fetchAll('SELECT node_id FROM xf_forum WHERE team_id = ?', $this->get('team_id'));

		if(empty($nodeIds))
		{
			return;
		}

		foreach($nodeIds as $nodeId)
		{
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Forum');
			$dw->setExistingData($nodeId);
			$dw->delete();
		}
	}

	public function updateMemberCountInTeam($adjust = null)
	{
		if ($adjust === null)
		{
			$this->set('member_count', $this->_db->fetchOne("
				SELECT COUNT(*)
				FROM xf_team_member
				WHERE team_id = ?
					AND member_state = 'accept'
			", $this->get('team_id')));
		}
		else
		{
			$this->set('member_count', $this->get('member_count') + $adjust);
		}
	}

	public function updateMemberRequestCount($adjust = null)
	{
		if ($adjust === null)
		{
			$this->set('member_request_count', $this->_db->fetchOne("
				SELECT COUNT(*)
				FROM xf_team_member
				WHERE team_id = ?
					AND member_state = 'request'
					AND action != 'invite'
			", $this->get('team_id')));
		}
		else
		{
			$this->set('member_request_count', $this->get('member_request_count') + $adjust);
		}
	}

	public function updateMemberInviteCount($adjust = null)
	{
		if (is_null($adjust))
		{
			$this->set('invite_count', $this->_db->fetchOne("
				SELECT COUNT(*)
				FROM xf_team_member
				WHERE team_id = ?
					AND member_state = 'request'
					AND action = 'invite'
			", $this->get('team_id')));
		}
		else
		{
			$this->set('invite_count', $this->get('invite_count') + $adjust);
		}
	}

	public function updateStickyMessageCount($adjust = null)
	{
		if (is_null($adjust))
		{
			$this->set('sticky_message_count', $this->_db->fetchOne("
				SELECT COUNT(*)
				FROM xf_team_post
				WHERE team_id = ? AND sticky = 1
			", $this->get('team_id')));
		}
		else
		{
			$this->set('sticky_message_count', $this->get('sticky_message_count') + $adjust);
		}
	}

	public function updateInvalidCustomUrl()
	{
		$customUrl = $this->get('custom_url');
		if (is_null($customUrl))
		{
			return true;
		}

		if (strlen($customUrl) < self::$urlThreshold)
		{
			$this->set('custom_url', null);
		}

		if (strpos($customUrl, '.') !== false) // ehnm? dot in custom URL?
		{
			$this->_db->update('xf_team', array('custom_url' => null),
				'team_id = ' . $this->_db->quote($this->get('team_id')));
		}

		return true;
	}

	public function updateTeamOwner()
	{
		$userId = $this->get('user_id');
		$db = $this->_db;

		$db->update('xf_team_member', array(
			'member_role_id' => 'admin'
		), 'user_id = ' . $db->quote($userId) . ' AND team_id = ' . $db->quote($this->get('team_id')));
	}

	public function updateTeamPrivacyIssue()
	{
		$userId = $this->get('user_id');
		if ($this->get('privacy_state') != 'secret')
		{
			return;
		}

		$user = Nobita_Teams_Container::getModel('XenForo_Model_User')->getUserById($userId, array(
			'join' => XenForo_Model_User::FETCH_USER_FULL | XenForo_Model_User::FETCH_USER_PERMISSIONS
		));

		if (!$user)
		{
			return;
		}

		$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
		if (!$this->_getTeamModel()->canAddSecretTeam($null, $user))
		{
			$this->_db->update('xf_team', array('privacy_state' => 'closed'), 'team_id = ' . $this->_db->quote($this->get('team_id')));
		}
	}

	public function updateEmptyRules()
	{
		$defaultRules = Nobita_Teams_Option::get('defaultRules');

		$rules = $this->get('rules');
		if (!empty($defaultRules) && empty($rules))
		{
			$this->set('rules', $defaultRules);
			$this->set('last_update_rule', XenForo_Application::$time);
			/*$this->_db->update('xf_team_privacy', array(
				'rules' => $defaultRules,
				'last_update_rule' => XenForo_Application::$time
			), 'team_id = ' . $this->_db->quote($this->get('team_id')));*/
		}
	}

	public function rebuildStaffList()
	{
		$memberModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');
		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');

		$staffIds = $memberRoleModel->getStaffIds();
		if(empty($staffIds))
		{
			return;
		}

		$conditions = array(
			'member_role_id' => $staffIds
		);
		$fetchOptions = array(
			'join' => Nobita_Teams_Model_Member::FETCH_USER
		);

		$members = $memberModel->getAllMembersInTeam($this->get('team_id'), $conditions, $fetchOptions);
		$this->set('staff_list', $members);
	}

	public function rebuildCounters()
	{
		$this->updateMemberCountInTeam();
		$this->updateMemberRequestCount();
		$this->updateMemberInviteCount();
		$this->updateInvalidCustomUrl();

		$this->updateStickyMessageCount();

		$this->updateEmptyRules();

		$this->_getTeamModel()->updateRibbonAssociations($this->getMergedData());
	}

	protected function _indexForSearch()
	{
		if ($this->get('team_state') == "visible")
		{
			if ($this->getExisting('team_state') != 'visible')
			{
				$this->_insertIntoSearchIndex();
			}
			else if ($this->_needsSearchIndexUpdate())
			{
				$this->_updateSearchIndexTitle();
			}
		}
		else if ($this->isUpdate() && $this->get('team_state') != 'visible' && $this->getExisting('team_state') == 'visible')
		{
			$this->_deleteFromSearchIndex();
		}
	}

	/**
	 * Returns true if the changes made require the search index to be updated.
	 *
	 * @return boolean
	 */
	protected function _needsSearchIndexUpdate()
	{
		return $this->isChanged('title') || $this->isChanged('tag_line') || $this->isChanged('team_category_id');
	}

	/**
	 * Inserts a record in the search index for this discussion.
	 */
	protected function _insertIntoSearchIndex()
	{
		$team = $this->getMergedData();
		$indexer = new XenForo_Search_Indexer();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Nobita_Teams_Search_DataHandler_Team');
		if ($dataHandler)
		{
			$dataHandler->insertIntoIndex($indexer, $team);
		}

	}

	/**
	 * Updates the title in the search index for this discussion.
	 */
	protected function _updateSearchIndexTitle()
	{
		$indexer = new XenForo_Search_Indexer();
		$mergedData = $this->getMergedData();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Nobita_Teams_Search_DataHandler_Team');
		if ($dataHandler)
		{
			$dataHandler->insertIntoIndex($indexer, $mergedData);
		}
	}

	/**
	 * Deletes this discussion from the search index.
	 */
	protected function _deleteFromSearchIndex()
	{
		$indexer = new XenForo_Search_Indexer();
		$mergedData = $this->getMergedData();

		$dataHandler = XenForo_Search_DataHandler_Abstract::create('Nobita_Teams_Search_DataHandler_Team');
		if ($dataHandler)
		{
			$dataHandler->deleteFromIndex($indexer, $mergedData);
		}
	}

	/**
	 * @return Nobita_Teams_DataWriter_Category|bool
	 */
	protected function _getCategoryDwForUpdate()
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Category', XenForo_DataWriter::ERROR_SILENT);
		if ($dw->setExistingData($this->get('team_category_id')))
		{
			return $dw;
		}
		else
		{
			return false;
		}
	}

	protected function _getNewsFeedModel()
	{
		return Nobita_Teams_Container::getModel('XenForo_Model_NewsFeed');
	}

	protected function _getCategoryModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category');
	}

	protected function _getFieldModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Field');
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}
}
