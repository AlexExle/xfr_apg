<?php

class Nobita_Teams_sonnb_XenGallery_ControllerPublic_XenGallery_Category extends XFCP_Nobita_Teams_sonnb_XenGallery_ControllerPublic_XenGallery_Category
{
    public function actionView()
    {
        $albumModel = $this->_getAlbumModel();
        $albumModel->forceJoinGroup = true;

        return parent::actionView();
    }
}
