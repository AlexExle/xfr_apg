<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_CronEntry_Tournament
{
	public static function automaticBracketGeneration()
	{
		$tournamentModel = XenForo_Model::create('XFA_Tournament_Model_Tournament');
		
        /* Get ready for generation tournaments */
        $tournaments = $tournamentModel->getTournaments(array('automatic_generation' => 1));
        
        /* Generate brackets */
        if ($tournaments)
        {
            foreach($tournaments AS $tournament)
            {
    	        $tournamentModel->generateBracket($tournament);
            }
        }
	}   
}