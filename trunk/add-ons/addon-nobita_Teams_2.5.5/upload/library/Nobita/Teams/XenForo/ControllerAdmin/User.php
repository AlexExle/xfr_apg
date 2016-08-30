<?php

class Nobita_Teams_XenForo_ControllerAdmin_User extends XFCP_Nobita_Teams_XenForo_ControllerAdmin_User
{
    public function actionDelete()
    {
        $response = parent::actionDelete();
        if($response instanceof XenForo_ControllerResponse_View)
        {
            $teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
            $response->params['userGroupCount'] = $teamModel->countTeamsYouAdmin($response->params['user']['user_id']);
        }

        return $response;
    }
}
