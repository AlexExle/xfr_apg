<?php

class Nobita_Teams_XenForo_DataWriter_Discussion_Thread extends XFCP_Nobita_Teams_XenForo_DataWriter_Discussion_Thread
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		$fields['xf_thread']['team_id'] = array('type' => self::TYPE_UINT, 'default' => 0);

		return $fields;
	}

	protected function _discussionPreSave()
	{
		if (isset($GLOBALS[Nobita_Teams_Listener::TEAM_CONTROLLERPUBLIC_FORUM_ADDTHREAD]))
		{
			$GLOBALS[Nobita_Teams_Listener::TEAM_CONTROLLERPUBLIC_FORUM_ADDTHREAD]->Team_actionAddThread($this);
		}

		return parent::_discussionPreSave();
	}

	protected function _discussionPostDelete()
	{
		Nobita_Teams_Container::getModel('Nobita_Teams_Model_NewsFeed')->delete($this->get('team_id'), $this->get('thread_id'), 'thread');

		return parent::_discussionPostDelete();
	}

}
