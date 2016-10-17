<?php

class Nobita_Teams_ViewPublic_Team_Find extends XenForo_ViewPublic_Base
{
    public function renderJson()
    {
        $results = array();
        foreach ($this->_params['teams'] AS $teams)
        {

            $results[$teams['title']] = array(
                'title' => htmlspecialchars( $teams['title']),
                'logo' => XenForo_Template_Helper_Core::callHelper('grouplogo', array($teams)),
                'object' => "team"
            );
        }

        return array(
            'results' => $results
        );
    }
}