<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_LikePostRe_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_discussion';
	protected $_displayOrder = 321;
	protected $_contentRoute = 'posts';
	protected $_contentIdKey = 'post_id';

	protected $_extendedClasses = array(
		'load_class_controller' => array(
			'XenForo_ControllerPublic_Post' => 'Brivium_Credits_ActionHandler_LikePostRe_ControllerPublic_Post'
		),
		'load_class_model' => array(
			'XenForo_Model_Like' => 'Brivium_Credits_ActionHandler_LikePostRe_Model_Like'
		)
	);

 	public function getActionId()
 	{
 		return 'likePostRe';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_likePostRe';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_likePostRe_description';
 	}
}