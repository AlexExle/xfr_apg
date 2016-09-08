<?php
class Brivium_Credits_ActionHandler_ThreadStickyRe_DataWriter_Discussion_Thread extends XFCP_Brivium_Credits_ActionHandler_ThreadStickyRe_DataWriter_Discussion_Thread
{
	protected function _discussionPostSave()
	{
		if(!$this->get('sticky') && $this->isUpdate() && $this->isChanged('sticky')){
			$dataCredit = array(
				'node_id'		=>	$this->get('node_id'),
				'content_id' 	=>	$this->get('thread_id'),
				'content_type'	=>	'thread',
				'extraData' 	=>	array('thread_id'=>$this->get('thread_id'))
			);
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('threadStickyRe', $this->get('user_id'), $dataCredit);
		}
		return parent::_discussionPostSave();
	}
}