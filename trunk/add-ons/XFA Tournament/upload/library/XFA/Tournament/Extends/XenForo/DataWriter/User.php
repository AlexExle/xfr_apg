<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
     * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Extends_XenForo_DataWriter_User extends XFCP_XFA_Tournament_Extends_XenForo_DataWriter_User
{  
    protected function _getFields()
    {
		$fields = parent::_getFields();
        $fields['xf_user']['xfa_tourn_new_alert'] = array('type' => self::TYPE_UINT,   'default' => 0);
        return $fields;
    }   
}