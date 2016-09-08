<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_VotePoll_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 251;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_Poll' => 'Brivium_Credits_ActionHandler_VotePoll_DataWriter_Poll'
		),
	);

 	public function getActionId()
 	{
 		return 'votePoll';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_votePoll';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_votePoll_description';
 	}
}