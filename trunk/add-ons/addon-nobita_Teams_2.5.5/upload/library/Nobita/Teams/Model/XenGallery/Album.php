<?php

class Nobita_Teams_Model_XenGallery_Album extends XenForo_Model
{
	public function canCreateAlbum(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		$galleryModel = Nobita_Teams_Container::getModel('sonnb_XenGallery_Model_Gallery');
		if (!$galleryModel->canUpload())
		{
			$errorPhraseKey = 'sonnb_xengallery_you_do_not_have_permission_to_canUpload';
			return false;
		}

		if ($galleryModel->isMobileiOS())
		{
			$errorPhraseKey = 'sonnb_xengallery_currently_we_do_not_support_upload_from_ios_devices';
			return false;
		}

		$albumModel = Nobita_Teams_Container::getModel('sonnb_XenGallery_Model_Album');

		$currentAlbumCount = $albumModel->countAlbumsByUserId($viewingUser['user_id']);
		$maximumAlbumAllowed = $albumModel->getUserMaximumAllowedAlbumCount();

		if ($maximumAlbumAllowed > 0 && $currentAlbumCount >= $maximumAlbumAllowed)
		{
			throw new XenForo_Exception(new XenForo_Phrase('sonnb_xengallery_you_reach_maximum_album_allowed', array(
				'limit' => XenForo_Locale::numberFormat($maximumAlbumAllowed)
			)), true);
			return false;
		}

		return true;
	}
}