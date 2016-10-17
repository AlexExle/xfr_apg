<?php

class Brivium_Credits_ActionHandler_UploadAttachmentRe_DataWriter_Attachment extends XFCP_Brivium_Credits_ActionHandler_UploadAttachmentRe_DataWriter_Attachment
{
	protected function _postDelete()
	{
		$data = $this->getMergedData();
		if($data['content_type'] == 'post'){
			$attachmentData = $this->_getAttachmentModel()->getAttachmentDataById($data['data_id']);
			if(!empty($attachmentData['user_id'])){
				$creditModel = $this->getModelFromCache('Brivium_Credits_Model_Credit');
				$nodeId = $creditModel->getNodeIdFromPostId($data['content_id']);
				$dataCredit = array(
					'node_id'		=>	$nodeId,
					'content_id' 	=>	$data['content_id'],
					'multi_amount' 	=> 	1,
					'content_type'	=>	'post',
					'extraData' 	=>	array('post_id'=>$data['content_id'])
				);
				$creditModel->updateUserCredit('uploadAttachmentRe', $attachmentData['user_id'], $dataCredit);
			}
		}
		return parent::_postDelete();
	}
}