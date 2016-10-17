<?php
class Brivium_Credits_ActionHandler_UploadAttachment_DataWriter_DiscussionMessage_Post extends XFCP_Brivium_Credits_ActionHandler_UploadAttachment_DataWriter_DiscussionMessage_Post
{
	protected function _messagePostSave()
	{
		$newAttachCount = 0;
		if ($this->isInsert() && $this->get('attach_count')){
			$newAttachCount = $this->get('attach_count');
		}else if($this->isUpdate() && ($this->get('attach_count') > $this->getExisting('attach_count'))){
			$newAttachCount = $this->get('attach_count') - $this->getExisting('attach_count') ;
		}
		if($newAttachCount){
			$threadId = $this->get('thread_id');
			if(isset($GLOBALS['BRC_THREADUPDATE']['thread_id']) &&
				$threadId==$GLOBALS['BRC_THREADUPDATE']['thread_id'])
			{
				$thread = $GLOBALS['BRC_THREADUPDATE'];
			}else{
				$thread = $this->getModelFromCache('XenForo_Model_Thread')->getThreadById($threadId);
				$GLOBALS['BRC_THREADUPDATE'] = $thread;
			}

			$dataCredit = array(
				'node_id'		=>	$thread['node_id'],
				'multi_amount' 	=> 	$newAttachCount,
				'content_id' 	=>	$this->get('post_id'),
				'content_type'	=>	'post',
				'extraData' 	=>	array('post_id'=>$this->get('post_id'))
			);
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('uploadAttachment',$this->get('user_id'),$dataCredit);

		}
		return parent::_messagePostSave();
	}
}