<?php
class Brivium_Credits_ActionHandler_PollGetVote_DataWriter_Poll extends XFCP_Brivium_Credits_ActionHandler_PollGetVote_DataWriter_Poll
{
	protected function _postSave()
	{
		if($this->get('content_type') == 'thread'){

			if ($this->isUpdate() && $this->getExisting('voter_count') < $this->get('voter_count')){
				$contentId = $this->get('content_id');
				if(isset($GLOBALS['BRC_THREADUPDATE']['thread_id']) && $this->get('thread_id')==$GLOBALS['BRC_THREADUPDATE']['thread_id'])
				{
					$thread = $GLOBALS['BRC_THREADUPDATE'];
				}else{
					$thread = $this->getModelFromCache('XenForo_Model_Thread')->getThreadById($contentId);
					$GLOBALS['BRC_THREADUPDATE'] = $thread;
				}
				$userId = XenForo_Visitor::getUserId();
				if(!empty($thread['user_id']) && $thread['user_id'] != $userId){
					$dataCredit = array(
						'user_action_id'=>	$userId,
						'content_id' 	=>	$contentId,
						'content_type'	=>	'thread_poll',
						'node_id' 		=>	$thread['node_id'],
						'extraData' 	=>	array('thread_id'=>$contentId)
					);
					$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('pollGetVote', $thread['user_id'], $dataCredit);
				}
			}
		}
		return parent::_postSave();
	}
}