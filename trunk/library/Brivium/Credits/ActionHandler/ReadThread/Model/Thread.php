<?php

class Brivium_Credits_ActionHandler_ReadThread_Model_Thread extends XFCP_Brivium_Credits_ActionHandler_ReadThread_Model_Thread
{
	public function markThreadRead(array $thread, array $forum, $readDate, array $viewingUser = null)
	{
		$result = parent::markThreadRead($thread, $forum, $readDate, $viewingUser);
		if(!isset($viewingUser['user_id'])){
			$this->standardizeViewingUserReference($viewingUser);
		}
		$userId = $viewingUser['user_id'];
		if($thread['user_id'] == $userId){
			return $result;
		}
		if($userId && $result){
			$creditModel = $this->getModelFromCache('Brivium_Credits_Model_Credit');
			$conditions = array(
				'user_action_id' => $userId,
				'content_id' => $thread['thread_id'],
				'content_type' => 'thread',
			);
			if($creditModel->countEventsTriggerByUser('readThread', $thread['user_id'], $conditions)){
				return $result;
			}
			$dataCredit = array(
				'user' 			=>	$viewingUser,
				'content_id' 	=>	$thread['thread_id'],
				'content_type'	=>	'thread',
				'node_id' 		=>	$thread['node_id'],
				'extraData' 	=>	array('thread_id'=>$thread['thread_id'])
			);
			$creditModel->updateUserCredit('readThread', $userId, $dataCredit);
		}
		return $result;
	}

}