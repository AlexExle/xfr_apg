<?php

class Nobita_Teams_ControllerPublic_XenGallery_Abstract extends sonnb_XenGallery_ControllerPublic_Abstract
{
	protected function _preDispatch($action)
	{
		if (!Nobita_Teams_AddOnChecker::getInstance()->isSonnbXenGalleryExistsAndActive())
		{
			throw $this->getNoPermissionResponseException();
		}

		if (!$this->_getTeamModel()->canViewTeams($error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		return parent::_preDispatch($action);
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}

	protected function _getTeamHelper()
	{
		return $this->getHelper('Nobita_Teams_ControllerHelper_Team');
	}



}
