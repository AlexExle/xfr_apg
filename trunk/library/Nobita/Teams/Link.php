<?php

class Nobita_Teams_Link extends XenForo_Link
{
    public static function buildTeamLink($type = '', $data = null, array $extraParams = array(), $skipPrepend = false)
    {
        if(empty($type))
        {
            $type = Nobita_Teams_Option::get('routePrefix');
        }
        else
        {
            if(strpos($type, ':') !== false)
            {
                $parts = explode(':', $type, 2);
                if(!empty($parts[0]))
                {
                    $type = $parts[0].':'.Nobita_Teams_Option::get('routePrefix').'/'.$parts[1];
                }
                else
                {
                    $type = Nobita_Teams_Option::get('routePrefix').'/'.$type;
                }
            }
            else
            {
                $type = Nobita_Teams_Option::get('routePrefix').'/'.$type;
            }
        }

        return self::buildPublicLink($type, $data, $extraParams, $skipPrepend);
    }
}
