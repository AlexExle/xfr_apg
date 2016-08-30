<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
     * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Extends_XenForo_ControllerPublic_Account extends XFCP_XFA_Tournament_Extends_XenForo_ControllerPublic_Account
{  
	public function actionAlertPreferencesSave()
	{
    	/* Execute parent */
        $response = parent::actionAlertPreferencesSave();
                
        /* If parent success and we can get our stored info in XenForo_Application registry, we can proceed */
        if ($response->redirectType == XenForo_ControllerResponse_Redirect::SUCCESS)
        {    	
    	    $newTournamentAlert = $this->_input->filterSingle('new_tournament_alert', XenForo_Input::UINT);
    	    
     		$dw = XenForo_DataWriter::create('XenForo_DataWriter_User');    		
    		$dw->setExistingData(XenForo_Visitor::getUserId());
    		$dw->set('xfa_tourn_new_alert', $newTournamentAlert);
    		$dw->save();
    	}
    	
		return $response;
	}     
}