<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_LikeProfilePostRe_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 156;
	protected $_contentRoute = 'profile-posts';
	protected $_contentIdKey = 'profile_post_id';
	protected $_extendedClasses = array(
		'load_class_model' => array(
			'XenForo_Model_Like' => 'Brivium_Credits_ActionHandler_LikeProfilePostRe_Model_Like'
		)
	);

 	public function getActionId()
 	{
 		return 'likeProfilePostRe';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_likeProfilePostRe';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_likeProfilePostRe_description';
 	}
}