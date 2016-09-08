<?php
class Brivium_Credits_ActionHandler_CreateNewPoll_DataWriter_Poll extends XFCP_Brivium_Credits_ActionHandler_CreateNewPoll_DataWriter_Poll
{
	protected function _postSave()
	{
		if($this->get('content_type') == 'thread'){

			if ($this->isInsert()){
				$contentId = $this->get('content_id');
				if(isset($GLOBALS['BRC_THREADUPDATE']['thread_id']) &&
					$this->get('thread_id')==$GLOBALS['BRC_THREADUPDATE']['thread_id'])
				{
					$thread = $GLOBALS['BRC_THREADUPDATE'];
				}else{
					$thread = $this->getModelFromCache('XenForo_Model_Thread')->getThreadById($contentId);
					$GLOBALS['BRC_THREADUPDATE'] = $thread;
				}

				$dataCredit = array(
					'node_id' 	=>	$thread['node_id'],
					'extraData' =>	array('thread_id'=>$contentId)
				);
				$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('createNewPoll', $thread['user_id'], $dataCredit);
			}
		}
		return parent::_postSave();
	}
}