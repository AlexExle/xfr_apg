<?php

abstract class Nobita_Teams_Importer_Abstract extends XenForo_Importer_Abstract
{
	protected $_teamDwName 		= 'Nobita_Teams_DataWriter_Team';
	protected $_categoryDwName 	= 'Nobita_Teams_DataWriter_Category';
	protected $_memberDwName 	= 'Nobita_Teams_DataWriter_Member';

	public static function getName()
	{
		throw new RuntimeException("Please overwrite in child class.");
	}

	public function getSteps()
	{
		return array(
			'categories' => array(
				'title' => new XenForo_Phrase('Teams_import_categories')
			),
			'groups' => array(
				'title' => new XenForo_Phrase('Teams_import_groups'),
				'depends' => array('categories')
			),
			'members' => array(
				'title' => new XenForo_Phrase('Teams_import_members'),
				'depends' => array('groups')
			)
		);
	}

	abstract public function stepCategories($start, array $options);

	abstract public function stepGroups($start, array $options);

	abstract public function stepMembers($start, array $options);

	protected function _checkIfRetainKeysEnabled(XenForo_Controller $controller)
	{
		if($controller->getInput()->filterSingle('retain_keys', XenForo_Input::BOOLEAN)) 
		{
			throw $controller->responseException(
				$controller->responseError(new XenForo_Phrase('Teams_this_importer_did_not_support_retain_ids'))
			);
		}
	}
}