<?php

/**
 * Controller for attachment-related actions.
 *
 * @package XenForo_Attachment
 */
class Brivium_Credits_ControllerPublic_Attachment extends XFCP_Brivium_Credits_ControllerPublic_Attachment
{
	protected function _getAttachmentOrError($attachmentId)
	{
		$attachment = parent::_getAttachmentOrError($attachmentId);
		if ($attachment)
		{
			$GLOBALS['BRC_attachment'] = $attachment;
		}

		return $attachment;
	}
}