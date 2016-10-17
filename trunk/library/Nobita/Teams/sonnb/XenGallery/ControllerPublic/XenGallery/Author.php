<?php

class Nobita_Teams_sonnb_XenGallery_ControllerPublic_XenGallery_Author extends XFCP_Nobita_Teams_sonnb_XenGallery_ControllerPublic_XenGallery_Author
{
	protected function _getDefaultAlbumConditions(array $user)
	{
		$conditions = parent::_getDefaultAlbumConditions($user);
		$conditions['team_id'] = 0;

		return $conditions;
	}

}