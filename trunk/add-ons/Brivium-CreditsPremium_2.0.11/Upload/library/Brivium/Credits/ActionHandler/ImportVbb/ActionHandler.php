<?php

/**
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_ActionHandler_ImportVbb_ActionHandler extends Brivium_Credits_ActionHandler_Abstract
{
	protected $_displayOrder = 55;
	protected $_extendedClasses = array(
		'load_class_importer' => array(
			'XenForo_Importer_vBulletin' => 'Brivium_Credits_ActionHandler_ImportVbb_Importer_vBulletin'
		)
	);

 	public function getActionId()
 	{
 		return 'importVbb';
 	}

	public function getActionTitlePhrase()
 	{
 		return 'BRC_action_importVbb';
 	}

	public function getDescriptionPhrase()
 	{
 		return 'BRC_action_importVbb_description';
 	}
}