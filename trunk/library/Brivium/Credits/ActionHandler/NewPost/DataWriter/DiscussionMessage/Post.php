<?php
class Brivium_Credits_ActionHandler_NewPost_DataWriter_DiscussionMessage_Post extends XFCP_Brivium_Credits_ActionHandler_NewPost_DataWriter_DiscussionMessage_Post
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
			$creditModel = $this->getModelFromCache('Brivium_Credits_Model_Credit');
			$post = $this->getMergedData();
			$wordCount = $creditModel->calculateWordAmount(strtolower($post['message']));
			$dataCredit = array(
				'node_id'			=>	$thread['node_id'],
				'multiplier'		=>	$wordCount,
				'content_id' 		=>	$this->get('post_id'),
				'content_type'		=>	'post',
				'errorMinimumHandle'=>	new XenForo_Phrase('BRC_post_needs_to_have_at_least_x_words'),
				'extraData' 		=>	array('post_id'=>$this->get('post_id'))
			);
			$creditModel->updateUserCredit('newPost',$post['user_id'],$dataCredit);
		}
		return parent::_messagePostSave();
	}
}