<?php

class Nobita_Teams_sonnb_XenGallery_ControllerPublic_XenGallery extends XFCP_Nobita_Teams_sonnb_XenGallery_ControllerPublic_XenGallery
{
	public function actionIndex()
	{
		if (!Nobita_Teams_Option::get('showPhotosOnIndex'))
		{
			// Odes//
			Nobita_Teams_Helper_Photo::$groupId = 0;
		}

		return parent::actionIndex();
	}

	protected function _getAlbumFetchElements(array $conditions)
	{
		$response = parent::_getAlbumFetchElements($conditions);
		if(!empty($response['options']))
		{
			$response['options']['nobita_Teams_joinTeam'] = true;
		}

		return $response;
	}
}
