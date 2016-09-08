<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_UploadAttachmentRe_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 411;
	protected $_contentRoute = 'posts';
	protected $_contentIdKey = 'post_id';
	protected $_extendedClasses = array(
		'load_class_datawriter' => array(
			'XenForo_DataWriter_Attachment' => 'Brivium_Credits_ActionHandler_UploadAttachmentRe_DataWriter_Attachment'
		)
	);

 	public function getActionId()
 	{
 		return 'uploadAttachmentRe';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_uploadAttachmentRe';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_uploadAttachmentRe_description';
 	}
}