<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_PostReported_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 331;
	protected $_contentRoute = 'posts';
	protected $_contentIdKey = 'post_id';

	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_Report' => 'Brivium_Credits_ActionHandler_PostReported_DataWriter_Report'
		),
	);

 	public function getActionId()
 	{
 		return 'postReported';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_postReported';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_postReported_description';
 	}
}