<?php

class Brivium_Credits_ActionHandler_ConversationGetReply_DataWriter_ConversationMessage extends XFCP_Brivium_Credits_ActionHandler_ConversationGetReply_DataWriter_ConversationMessage
{
	protected function _postSave()
	{
		if ($this->isInsert() && $this->getOption(self::OPTION_UPDATE_CONVERSATION))
		{
			$GLOBALS['BRC_ConversationGetReply_replyUserId'] = $this->get('user_id');
		}
		return parent::_postSave();
	}
}