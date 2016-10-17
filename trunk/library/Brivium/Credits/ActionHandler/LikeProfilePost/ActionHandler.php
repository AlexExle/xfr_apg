<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_LikeProfilePost_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 155;
	protected $_contentRoute = 'profile-posts';
	protected $_contentIdKey = 'profile_post_id';
	protected $_extendedClasses = array(
		'load_class_model' => array(
			'XenForo_Model_Like' => 'Brivium_Credits_ActionHandler_LikeProfilePost_Model_Like'
		)
	);

 	public function getActionId()
 	{
 		return 'likeProfilePost';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_likeProfilePost';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_likeProfilePost_description';
 	}
}