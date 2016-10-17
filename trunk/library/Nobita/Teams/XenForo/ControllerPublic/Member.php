<?php

class Nobita_Teams_XenForo_ControllerPublic_Member extends XFCP_Nobita_Teams_XenForo_ControllerPublic_Member
{
    public function actionMember()
    {
        $GLOBALS['Team_fetchTeamRibbon'] = true;

        $response = parent::actionMember();
        if ($response instanceof XenForo_ControllerResponse_View AND !empty($response->params))
        {
            $params =& $response->params;
            if(empty($params['user']))
            {
                return $response;
            }

            $memberModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');
            $hasJoinAny = $memberModel->countMembers(array(
                'member_state' => 'accept',
                'user_id' => $response->params['user']['user_id']
            ));

            $params['user']['groupList'] = $hasJoinAny;
        }

        return $response;
    }

    public function actionCard()
    {
        $GLOBALS['Team_fetchTeamRibbon'] = true;

        return parent::actionCard();
    }

}
