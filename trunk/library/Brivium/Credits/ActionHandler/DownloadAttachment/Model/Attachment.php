<?php

class Brivium_Credits_ActionHandler_DownloadAttachment_Model_Attachment extends XFCP_Brivium_Credits_ActionHandler_DownloadAttachment_Model_Attachment
{
	/**
	 * Logs the viewing of an attachment.
	 *
	 * @param integer $attachmentId
	 */
	public function logAttachmentView($attachmentId)
	{
		$attachment = $this->getAttachmentById($attachmentId);
		$user = XenForo_Visitor::getInstance()->toArray();
		$userId = $user['user_id'];
		if (!empty($attachment['content_type']) && $attachment['content_type']=='post' && !empty($attachment['data_id']) && $userId)){
			$attachmentData = $this->getAttachmentDataById($attachment['data_id']);
			if($attachmentData['user_id'] != $userId){
				$options = XenForo_Application::get('options');
				$extensionMap =	preg_split('/\s+/', trim($options->BRC_excludedFileExtensions));
				if(!$extensionMap)$extensionMap=array();
				$extension = XenForo_Helper_File::getFileExtension($attachment['filename']);
				if (!in_array($extension, $extensionMap))
				{
					$creditModel = $this->_getCreditModel();
					if(!empty($GLOBALS['BRC_AttachmentViewNodeId'])){
						$nodeId = $GLOBALS['BRC_AttachmentViewNodeId'];
					}else{
						$nodeId = $creditModel->getNodeIdFromPostId($attachment['content_id']);
					}
					$conditions = array(
						'user_action_id' => $attachmentData['user_id'],
						'content_id' => $attachment['attachment_id'],
						'content_type' => 'attachment',
					);
					if($creditModel->countEventsTriggerByUser('downloadAttachment', $userId, $conditions)){
						return parent::logAttachmentView($attachmentId);
					}
					$dataCredit = array(
						'user' 	=>	$user,
						'node_id' 	=>	$nodeId,
						'user_action_id' 	=>	$attachmentData['user_id'],
						'content_id' 		=>	$attachment['attachment_id'],
						'content_type'		=>	'attachment',
						'extra_data' =>	array(
							'attachment_id'	=> $attachmentId
						),
					);
					$creditModel->updateUserCredit('downloadAttachment', $userId, $dataCredit, $errorString);
				}
			}
		}
		return parent::logAttachmentView($attachmentId);
	}
	/**
	 * Gets the action model.
	 *
	 * @return Brivium_Credits_Model_Credit
	 */
	protected function _getCreditModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Credit');
	}
}