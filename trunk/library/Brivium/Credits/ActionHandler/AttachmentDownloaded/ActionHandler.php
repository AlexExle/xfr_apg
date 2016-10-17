<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_AttachmentDownloaded_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 421;
	protected $_contentRoute = 'attachments';
	protected $_contentIdKey = 'attachment_id';

	/*protected $_extendedClasses = array(
		'load_class_model' => array(
			'XenForo_Model_Attachment' => 'Brivium_Credits_ActionHandler_AttachmentDownloaded_Model_Attachment'
		)
	);
*/
 	public function getActionId()
 	{
 		return 'attachmentDownloaded';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_attachmentDownloaded';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_attachmentDownloaded_description';
 	}
}