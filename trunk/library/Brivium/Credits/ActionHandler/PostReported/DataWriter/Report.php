<?php

class Brivium_Credits_ActionHandler_PostReported_DataWriter_Report extends XFCP_Brivium_Credits_ActionHandler_PostReported_DataWriter_Report
{
	protected function _postSave()
	{
		if ($this->get('content_user_id') && $this->get('content_type')=='post' && $this->isInsert())
		{
			$report = $this->getMergedData();
			if(!empty($report['content_user_id'])){

				$dataCredit = array(
					'content_id' 		=>	$this->get('content_id'),
					'content_type'		=>	'post',
					'extra_data' =>	array(
						'post_id'=> $this->get('content_id'),
						'report_info'	=> $report['content_info']
					),
				);
				$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('postReported', $report['content_user_id'], $dataCredit);
			}
		}
		return parent::_postSave();
	}
}