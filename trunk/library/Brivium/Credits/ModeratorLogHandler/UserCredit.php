<?php

class Brivium_Credits_ModeratorLogHandler_UserCredit extends XenForo_ModeratorLogHandler_Abstract
{
	protected function _log(array $logUser, array $content, $action, array $actionParams = array(), $parentContent = null)
	{
		$dw = XenForo_DataWriter::create('XenForo_DataWriter_ModeratorLog');
		$dw->bulkSet(array(
			'user_id' => $logUser['user_id'],
			'content_type' => 'credit',
			'content_id' => $content['user_id'],
			'content_user_id' => $content['user_id'],
			'content_username' => $content['username'],
			'content_title' => $content['username'],
			'content_url' => XenForo_Link::buildPublicLink('members', $content),
			'discussion_content_type' => 'credit',
			'discussion_content_id' => $content['user_id'],
			'action' => $action,
			'action_params' => $actionParams
		));
		$dw->save();

		return $dw->get('moderator_log_id');
	}

	protected function _prepareEntry(array $entry)
	{
		$elements = json_decode($entry['action_params'], true);
		
		$listElements = array();
		foreach($elements AS $key=>$element){
			if(isset($element['old']) && isset($element['new'])){
				$listElements[] = $key . ': ' . $element['old'] . ' -> ' . $element['new'];
			}
		}
		if ($entry['action'] == 'edit')
		{
			$entry['actionText'] = new XenForo_Phrase(
				'BRC_moderator_log_credit_edit',
				array('elements' => implode(', ', $listElements))
			);
		}

		return $entry;
	}
}