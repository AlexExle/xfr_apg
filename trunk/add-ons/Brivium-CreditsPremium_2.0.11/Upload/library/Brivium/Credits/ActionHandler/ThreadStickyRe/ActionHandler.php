<?php

class Brivium_Credits_ActionHandler_ThreadStickyRe_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 261;
	protected $_contentRoute = 'threads';
	protected $_contentIdKey = 'thread_id';
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_Discussion_Thread' => 'Brivium_Credits_ActionHandler_ThreadStickyRe_DataWriter_Discussion_Thread'
		),
	);

 	public function getActionId()
 	{
 		return 'threadStickyRe';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_threadStickyRe';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_threadStickyRe_description';
 	}
}