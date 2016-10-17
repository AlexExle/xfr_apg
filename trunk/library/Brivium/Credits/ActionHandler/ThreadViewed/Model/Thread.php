<?php

class Brivium_Credits_ActionHandler_ThreadViewed_Model_Thread extends XFCP_Brivium_Credits_ActionHandler_ThreadViewed_Model_Thread
{
	public function markThreadRead(array $thread, array $forum, $readDate, array $viewingUser = null)
	{
		$result = parent::markThreadRead($thread,$forum,$readDate,$viewingUser);
		if(!isset($viewingUser['user_id'])){
			$this->standardizeViewingUserReference($viewingUser);
		}
		$userId = $viewingUser['user_id'];
		if($thread['user_id'] && $thread['user_id'] != $userId && (($userId && $result) ||(!$userId && XenForo_Application::get('options')->BRC_guestTriggerViewActions))){
			$dataCredit = array(
				'content_id' 	=>	$thread['thread_id'],
				'content_type'	=>	'thread',
				'node_id' 			=>	$thread['node_id'],
				'extraData' 	=>	array('thread_id'=>$thread['thread_id'])
			);
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('threadViewed', $thread['user_id'], $dataCredit);
		}
		return $result;
	}

}