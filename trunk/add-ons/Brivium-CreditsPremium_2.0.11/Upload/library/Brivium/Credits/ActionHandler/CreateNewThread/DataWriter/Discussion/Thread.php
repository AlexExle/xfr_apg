<?php
class Brivium_Credits_ActionHandler_CreateNewThread_DataWriter_Discussion_Thread extends XFCP_Brivium_Credits_ActionHandler_CreateNewThread_DataWriter_Discussion_Thread
{
	protected function _saveFirstMessageDw()
	{
		$response = parent::_saveFirstMessageDw();
		if ($this->isInsert())
		{
			$creditModel = $this->getModelFromCache('Brivium_Credits_Model_Credit');
			// createNewThread
			$messageDw = $this->_firstMessageDw;
			$wordCount = $creditModel->calculateWordAmount(strtolower($messageDw->get('message')));
			$dataCredit = array(
				'node_id'		=>	$this->get('node_id'),
				'multiplier' 	=> 	$wordCount,
				'content_id' 	=>	$this->get('thread_id'),
				'content_type'	=>	'thread',
				'errorMinimumHandle' =>	new XenForo_Phrase('BRC_post_needs_to_have_at_least_x_words'),
				'extraData' 	=>	array('thread_id'=>$this->get('thread_id'))
			);
			$creditModel->updateUserCredit('createNewThread',$this->get('user_id'),$dataCredit);
		}
		return $response;
	}
}