<?php

class Nobita_Teams_XenForo_DataWriter_Forum extends XFCP_Nobita_Teams_XenForo_DataWriter_Forum
{
	private $_teamDw;

    protected function _getFields()
    {
        $fields = parent::_getFields();
        $fields['xf_forum']['team_id'] = array('type' => self::TYPE_UINT, 'default' => 0);

        return $fields;
    }

    protected function _postSave()
    {
    	$response = parent::_postSave();

    	$teamDw = $this->_getTeamDataWriter();
    	if($teamDw)
    	{
    		if($this->isUpdate() && $this->isChanged('discussion_count'))
	    	{
	    		$diff = $this->get('discussion_count') - $this->getExisting('discussion_count');
	    		$teamDw->set('thread_count', max(0, $teamDw->get('thread_count') + $diff));
	    	}

	    	if($this->isUpdate() && $this->isChanged('message_count'))
	    	{
	    		$diff = $this->get('message_count') - $this->getExisting('message_count');
	    		$teamDw->set('thread_post_count', max(0, $teamDw->get('thread_post_count') + $diff));
	    	}

	    	if($teamDw->hasChanges())
	    	{
	    		$teamDw->save();
	    	}
    	}

    	return $response;
    }

    protected function _postDelete()
    {
    	$db = $this->_db;
    	$discussionCount = $this->get('discussion_count');
    	$messageCount = $this->get('message_count');

    	$db->query("
    		UPDATE xf_team
    		SET thread_count = IF(thread_count > ?, thread_count - ?, 0),
    			thread_post_count = IF(thread_post_count > ?, thread_post_count - ?, 0)
    		WHERE team_id = ?
    	", array(
    		$discussionCount, $discussionCount, $messageCount, $messageCount, $this->get('team_id')
    	));

    	return parent::_postDelete();
    }

    protected function _getTeamDataWriter()
    {
    	if($this->_teamDw === null)
    	{
    		$this->_teamDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
    		if($this->_teamDw->setExistingData($this->get('team_id')))
    		{
    			return $this->_teamDw;
    		}
    	}

    	return false;
    }
}
