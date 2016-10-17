<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
     * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Proxy
{
    public static function extendsControllerPublicThread($class, &$extend)
    {
        $extend[] = 'XFA_Tournament_Extends_XenForo_ControllerPublic_Thread';
    }
    
    public static function extendsControllerPublicAccount($class, &$extend)
    {
        $extend[] = 'XFA_Tournament_Extends_XenForo_ControllerPublic_Account';
    }
    
    public static function extendsDataWriterUser($class, &$extend)
    {
        $extend[] = 'XFA_Tournament_Extends_XenForo_DataWriter_User';
    }
}