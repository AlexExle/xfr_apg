<?php

class Nobita_Teams_Model_XenMedia_Media extends Nobita_Teams_Model_Abstract
{
	public function canAddMedia(array $team, array $category, array $mediaCategory, &$error = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (!$this->_getTeamModel()->canViewTeamAndContainer($team, $category, $error, $viewingUser))
		{
			return false;
		}

		return Nobita_Teams_Container::getModel('XenGallery_Model_Category')->canAddMediaToCategory($mediaCategory, $error, $viewingUser);
	}

	public function canViewMedia(array $media, array $team, array $category, &$error = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$this->_getTeamModel()->canViewTeamAndContainer($team, $category, $error, $viewingUser))
		{
			return false;
		}

		return Nobita_Teams_Container::getModel('XenGallery_Model_Media')->canViewMediaItem($media, $error, $viewingUser);
	}

}