<?php

class Brivium_Credits_ActionHandler_ThreadGetWatched_ControllerPublic_Thread extends XFCP_Brivium_Credits_ActionHandler_ThreadGetWatched_ControllerPublic_Thread
{
	public function actionWatch()
	{
		$this->_assertPostOnly();
		if(empty($GLOBALS['BRC_WATCHTHREAD'])){
			$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
			list($thread, $forum) = $this->getHelper('ForumThreadPost')->assertThreadValidAndViewable($threadId);
			$GLOBALS['BRC_WATCHTHREAD'] = $thread;
		}

		return parent::actionWatch();
	}

}