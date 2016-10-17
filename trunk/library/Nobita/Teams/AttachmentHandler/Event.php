<?php

class Nobita_Teams_AttachmentHandler_Event extends XenForo_AttachmentHandler_Abstract
{
	protected $_eventModel = null;

	/**
	 * The key of the content ID value in the content data array.
	 * Must be overriden by children.
	 *
	 * @var string
	 */
	protected $_contentIdKey = 'event_id';

	/**
	 * Route to get to a post
	 *
	 * @var string
	 */
	protected $_contentRoute =  'groups/events'; // xF will auto redirected to target route. Can't use ::routePrefix().

	/**
	 * Name of the phrase that describes the conversation_message content type
	 *
	 * @var string
	 */
	protected $_contentTypePhraseKey = 'Teams_handler_phrase_key_events';

	/**
	 * Determines if attachments and be uploaded and managed in this context.
	 *
	 * @see XenForo_AttachmentHandler_Abstract::_canUploadAndManageAttachments()
	 */
	protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
	{
		$eventModel = $this->_getEventModel();

		if (!empty($contentData['event_id']))
		{
			$event = $eventModel->getEventById($contentData['event_id']);
			if ($event)
			{
				$contentData['team_id'] = $event['team_id'];
			}
		}

		$teamModel = $eventModel->getModelFromCache('Nobita_Teams_Model_Team');
		if (!empty($contentData['team_id']))
		{
			$team = $teamModel->getTeamById($contentData['team_id'], array(
				'join' => Nobita_Teams_Model_Team::FETCH_PROFILE
					| Nobita_Teams_Model_Team::FETCH_PRIVACY
					| Nobita_Teams_Model_Team::FETCH_CATEGORY
			));

			if ($team)
			{
				if (!empty($contentData['event_id']))
				{
					if (!$eventModel->canViewEvent($event, $team, $team, $null, $viewingUser)
						|| !$eventModel->canEditEvent($event, $team, $team, $null, $viewingUser)
					)
					{
						return false;
					}
				}

				return (
					$eventModel->canAddEvent($team, $team, $null, $viewingUser)
					&& $teamModel->canViewTeamAndContainer($team, $team, $null, $viewingUser)
					&& $teamModel->canUploadAndManageAttachment($team, $team, $null, $viewingUser)
				);
			}
		}

		return false;
	}

	/**
	 * Determines if the specified attachment can be viewed.
	 *
	 * @see XenForo_AttachmentHandler_Abstract::_canViewAttachment()
	 */
	protected function _canViewAttachment(array $attachment, array $viewingUser)
	{
		$eventModel = $this->_getEventModel();

		$event = $eventModel->getEventById($attachment['content_id'], array(
			'join' => Nobita_Teams_Model_Event::FETCH_TEAM | Nobita_Teams_Model_Event::FETCH_USER
		));

		if (!$event)
		{
			return false;
		}

		/* @var $teamModel Nobita_Teams_Model_Team */
		$teamModel = $eventModel->getModelFromCache('Nobita_Teams_Model_Team');
		$team = $teamModel->getFullTeamById($event['team_id'], array(
			'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
		));

		if ( !$team)
		{
			return false;
		}

		if (!$teamModel->canViewTeamAndContainer($team, $team, $null, $viewingUser))
		{
			return false;
		}

		if (!$eventModel->canViewEventAndContainer($event, $team, $team, $null, $viewingUser))
		{
			return false;
		}

		return $eventModel->canViewAttachmentOnEvent($event, $team, $team, $null, $viewingUser);
	}

	/**
	 * Code to run after deleting an associated attachment.
	 *
	 * @see XenForo_AttachmentHandler_Abstract::attachmentPostDelete()
	 */
	public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
	{
		$db->query('
			UPDATE xf_team_event
			SET attach_count = IF(attach_count > 0, attach_count - 1, 0)
			WHERE event_id = ?
		', $attachment['content_id']);
	}

	public function getContentLink(array $attachment, array $extraParams = array(), $skipPrepend = false)
	{
		$data = $this->getContentDataFromContentId($attachment['content_id']);
		return XenForo_Link::buildPublicLink(
			TEAM_ROUTE_PREFIX . '/events', $data, $extraParams, $skipPrepend
		);
	}

	protected function _getEventModel()
	{
		if ($this->_eventModel === null)
		{
			$this->_eventModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');
		}

		return $this->_eventModel;
	}
}
