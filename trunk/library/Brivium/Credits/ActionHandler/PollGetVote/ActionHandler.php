<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_PollGetVote_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 252;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_Poll' => 'Brivium_Credits_ActionHandler_PollGetVote_DataWriter_Poll'
		),
	);

 	public function getActionId()
 	{
 		return 'pollGetVote';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_pollGetVote';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_pollGetVote_description';
 	}
}