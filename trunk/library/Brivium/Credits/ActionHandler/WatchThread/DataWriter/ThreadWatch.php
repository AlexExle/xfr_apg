<?php

class Brivium_Credits_ActionHandler_WatchThread_DataWriter_ThreadWatch extends XFCP_Brivium_Credits_ActionHandler_WatchThread_DataWriter_ThreadWatch
{
	protected function _postSave()
	{
		if(isset($GLOBALS['BRC_WATCHTHREAD'])){
			$thread = $GLOBALS['BRC_WATCHTHREAD'];
			if(isset($thread['user_id'], $thread['node_id'])){
				$creditModel = $this->getModelFromCache('Brivium_Credits_Model_Credit');
				$dataCredit = array(
					'node_id'		=>	$thread['node_id'],
					'content_id' 	=>	$this->get('thread_id'),
					'content_type'	=>	'thread',
					'extraData' 	=>	array('thread_id' => $this->get('thread_id'))
				);
				if(!empty($thread['user_id'])){
					if($thread['user_id'] != $this->get('user_id')){
						$creditModel->updateUserCredit('watchThread', $this->get('user_id'), $dataCredit);
					}
				}else{
					$creditModel->updateUserCredit('watchThread', $this->get('user_id'), $dataCredit);
				}
			}
		}
		return parent::_postSave();
	}
}