<?php

class Nobita_Teams_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_Nobita_Teams_XenForo_DataWriter_DiscussionMessage_Post
{
	protected function _postSaveAfterTransaction()
	{
		if ($this->get('message_state') == 'visible')
		{
			if ($this->isInsert() || $this->getExisting('message_state') == 'moderated')
			{
				$thread = $this->getDiscussionData();
				if (!empty($thread['team_id']) AND $this->isDiscussionFirstMessage())
				{
					// send alert to all group members when new thread created
					Nobita_Teams_Container::getModel('XenForo_Model_ForumWatch')->groups_setThreadData($thread);
				}

				if (!empty($thread['team_id']))
				{
					$this->_db->query("
						UPDATE xf_team
						SET last_updated = ?
						WHERE team_id = ?
					", array(XenForo_Application::$time, $thread['team_id']));
				}

				if ($this->isDiscussionFirstMessage() AND !empty($thread['team_id']))
				{
					Nobita_Teams_Container::getModel('Nobita_Teams_Model_NewsFeed')->publish($thread['team_id'], $thread['thread_id'], 'thread');
				}
			}
		}

		return parent::_postSaveAfterTransaction();
	}

}
