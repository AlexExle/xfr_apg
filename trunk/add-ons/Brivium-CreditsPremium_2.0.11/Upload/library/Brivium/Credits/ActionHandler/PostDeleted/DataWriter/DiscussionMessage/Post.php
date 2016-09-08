<?php

class Brivium_Credits_ActionHandler_PostDeleted_DataWriter_DiscussionMessage_Post extends XFCP_Brivium_Credits_ActionHandler_PostDeleted_DataWriter_DiscussionMessage_Post
{
	protected function _messagePostDelete()
	{
		if (!$this->isDiscussionFirstMessage())
		{
			if(isset($GLOBALS['BRC_THREADUPDATE']['thread_id']) &&
				$this->get('thread_id')==$GLOBALS['BRC_THREADUPDATE']['thread_id'])
			{
				$thread = $GLOBALS['BRC_THREADUPDATE'];
			}else{
				$thread = $this->getModelFromCache('XenForo_Model_Thread')->getThreadById($this->get('thread_id'));
				$GLOBALS['BRC_THREADUPDATE'] = $thread;
			}

			$post = $this->getMergedExistingData();
			$creditModel = $this->getModelFromCache('Brivium_Credits_Model_Credit');

			$wordCount = $creditModel->calculateWordAmount(strtolower($post['message']));
			$dataCredit = array(
				'node_id'		=>	$thread['node_id'],
				'multiplier'	=>	$wordCount,
				'content_id' 	=>	$this->get('thread_id'),
				'content_type'	=>	'thread',
				'extraData' 	=>	array('thread_id'=>$this->get('thread_id'))
			);
			$creditModel->updateUserCredit('postDeleted',$this->get('user_id'),$dataCredit);
		}
		return parent::_messagePostDelete();
	}
}