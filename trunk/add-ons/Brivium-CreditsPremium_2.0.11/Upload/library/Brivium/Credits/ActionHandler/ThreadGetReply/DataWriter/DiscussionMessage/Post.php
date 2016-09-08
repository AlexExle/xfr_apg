<?php
class Brivium_Credits_ActionHandler_ThreadGetReply_DataWriter_DiscussionMessage_Post extends XFCP_Brivium_Credits_ActionHandler_ThreadGetReply_DataWriter_DiscussionMessage_Post
{
	protected function _messagePostSave()
	{
		if ($this->isInsert() && !$this->isDiscussionFirstMessage())
		{
			if(isset($GLOBALS['BRC_THREADUPDATE']['thread_id']) &&
				$this->get('thread_id')==$GLOBALS['BRC_THREADUPDATE']['thread_id'])
			{
				$thread = $GLOBALS['BRC_THREADUPDATE'];
			}else{
				$thread = $this->getModelFromCache('XenForo_Model_Thread')->getThreadById($this->get('thread_id'));
				$GLOBALS['BRC_THREADUPDATE'] = $thread;
			}

			$post = $this->getMergedData();
			if($thread['user_id']!=$this->get('user_id')){
				$dataCredit = array(
					'user_action_id'=>	$post['user_id'],
					'node_id'		=>	$thread['node_id'],
					'content_id' 	=>	$this->get('thread_id'),
					'content_type'	=>	'thread',
					'extraData' 	=>	array('post_id'=>$this->get('post_id'))
				);
				$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('threadGetReply', $thread['user_id'], $dataCredit);
			}
		}
		return parent::_messagePostSave();
	}
}