<?php

class Brivium_Credits_ActionHandler_ThreadDeleted_DataWriter_Discussion_Thread extends XFCP_Brivium_Credits_ActionHandler_ThreadDeleted_DataWriter_Discussion_Thread
{
	protected function _discussionPostDelete()
	{
		$creditModel = $this->getModelFromCache('Brivium_Credits_Model_Credit');
		// threadDeleted
		$wordCount = 0;
		if(isset($GLOBALS['BRC_ThreadDeleted_wordCount'])){
			$wordCount = $GLOBALS['BRC_ThreadDeleted_wordCount'] ;
		}
		$dataCredit = array(
			'node_id'			=>	$this->get('node_id'),
			'multiplier'		=>	$wordCount,
			'ignore_min_handle'	=>	true,
			'content_id' 		=>	$this->get('thread_id'),
			'content_type'		=>	'thread',
			'extraData' 		=>	array('node_id'=>$this->get('node_id'))
		);
		$creditModel->updateUserCredit('threadDeleted', $this->get('user_id'), $dataCredit);
		return parent::_discussionPostDelete();
	}

	protected function _deleteDiscussionMessages()
	{
		$creditModel = $this->getModelFromCache('Brivium_Credits_Model_Credit');
		$firstPost = $this->getModelFromCache('XenForo_Model_Post')->getPostById($this->get('first_post_id'));
		$wordCount = $creditModel->calculateWordAmount(strtolower($firstPost['message']));
		$GLOBALS['BRC_ThreadDeleted_wordCount'] = $wordCount;
		return parent::_deleteDiscussionMessages();
	}
}