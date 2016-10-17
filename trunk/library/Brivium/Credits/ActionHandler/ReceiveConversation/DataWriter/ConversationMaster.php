<?php

/**
* Data writer for conversation masters.
*
* @package XenForo_Conversation
*/

class Brivium_Credits_ActionHandler_ReceiveConversation_DataWriter_ConversationMaster extends XFCP_Brivium_Credits_ActionHandler_ReceiveConversation_DataWriter_ConversationMaster
{
	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		if ($recipients = $this->getModelFromCache('XenForo_Model_User')->getUsersByIds($this->_newRecipients, array('join' => XenForo_Model_User::FETCH_USER_FULL)))
		{
			$visitor = XenForo_Visitor::getInstance();
			$creditModel = $this->getModelFromCache('Brivium_Credits_Model_Credit');
			$dataCredit = array(
				'content_id' 	=>	$this->get('conversation_id'),
				'content_type'	=>	'conversation',
				'user_action_id' 	=>	$visitor['user_id'],
			);

			foreach ($recipients AS $recipient)
			{
				$dataCredit['user'] = array();
				$dataCredit['user'] = $recipient;
				$creditModel->updateUserCredit('receiveConversation', $recipient['user_id'], $dataCredit);
			}
		}
		return parent::_postSave();
	}
}