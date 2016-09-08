<?php

/**
* Data writer for conversation masters.
*
* @package XenForo_Conversation
*/

class Brivium_Credits_ActionHandler_ReplyConversation_DataWriter_ConversationMaster extends XFCP_Brivium_Credits_ActionHandler_ReplyConversation_DataWriter_ConversationMaster
{
	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		if (isset($GLOBALS['BRC_ReplyConversation_replyUserId']) && $GLOBALS['BRC_ReplyConversation_replyUserId']!=$this->get('user_id')) {
			$dataCredit = array(
				'content_id' 	=>	$this->get('conversation_id'),
				'content_type'	=>	'conversation',
			);

			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('replyConversation', $GLOBALS['BRC_ReplyConversation_replyUserId'], $dataCredit);

			unset($GLOBALS['BRC_ReplyConversation_replyUserId']);
		}
		return parent::_postSave();
	}
}