<?php

class Nobita_Teams_sonnb_XenGallery_Model_Category extends XFCP_Nobita_Teams_sonnb_XenGallery_Model_Category
{
    public function getLatestAlbumsForCategories(array $categories, array $viewingUser = null)
	{
        $albumModel = $this->_getAlbumModel();
        $albumModel->forceJoinGroup = true;

        return parent::getLatestAlbumsForCategories($categories, $viewingUser);
    }
}
