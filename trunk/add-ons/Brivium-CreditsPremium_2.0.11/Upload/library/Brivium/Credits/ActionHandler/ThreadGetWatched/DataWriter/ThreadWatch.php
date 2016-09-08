<?php

class Brivium_Credits_ActionHandler_ThreadGetWatched_DataWriter_ThreadWatch extends XFCP_Brivium_Credits_ActionHandler_ThreadGetWatched_DataWriter_ThreadWatch
{
	protected function _postSave()
	{
		if(isset($GLOBALS['BRC_WATCHTHREAD'])){
			$thread = $GLOBALS['BRC_WATCHTHREAD'];
			if(isset($thread['user_id'], $thread['node_id']) && !empty($thread['user_id']) && $thread['user_id'] != $this->get('user_id')){
				$dataCredit = array(
					'node_id'		=>	$thread['node_id'],
					'content_id' 	=>	$this->get('thread_id'),
					'content_type'	=>	'thread',
					'extraData' 	=>	array('thread_id' => $this->get('thread_id')),
					'user_action_id'=>	$this->get('user_id'),
				);
				$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('threadGetWatched', $thread['user_id'], $dataCredit);
			}
		}
		return parent::_postSave();
	}
}