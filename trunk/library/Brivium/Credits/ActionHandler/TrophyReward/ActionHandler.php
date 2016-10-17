<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_TrophyReward_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_editTemplate = 'BRC_action_edit_template_user';
	protected $_displayOrder = 170;
	protected $_extendedClasses = array(
		'load_class_model' => array(
			'XenForo_Model_Trophy' => 'Brivium_Credits_ActionHandler_TrophyReward_Model_Trophy'
		)
	);

 	public function getActionId()
 	{
 		return 'trophyReward';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_trophyReward';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_trophyReward_description';
 	}
}