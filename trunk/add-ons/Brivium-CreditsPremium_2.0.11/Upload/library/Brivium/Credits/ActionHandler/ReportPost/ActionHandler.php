<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ReportPost_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 330;
	protected $_contentRoute = 'posts';
	protected $_contentIdKey = 'post_id';
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_Report' => 'Brivium_Credits_ActionHandler_ReportPost_DataWriter_Report'
		),
	);

 	public function getActionId()
 	{
 		return 'reportPost';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_reportPost';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_reportPost_description';
 	}
}