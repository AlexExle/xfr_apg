<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Deferred_Upgrade_902000090_Bracket extends XenForo_Deferred_Abstract
{
	public function execute(array $deferred, array $data, $targetRunTime, &$status)
	{
		$data = array_merge(array(
			'position' => 0,
			'batch' => 10
		), $data);
		$data['batch'] = max(1, $data['batch']);

		$tournamentModel = XenForo_Model::create('XFA_Tournament_Model_Tournament');

		$tournamentIds = $userModel->getTournamentIdsInRange($data['position'], $data['batch']);
		if (sizeof($tournamentIds) == 0)
		{
			return true;
		}
        
        /* Get Tournaments */
        $tournaments = $userModel->getTournamentsByIds($tournamentIds);

        foreach($tournaments AS $tournament)
        {
            
        }       

        /* Prepare for new round */
		$data['position'] = end($tournamentIds);

		$actionPhrase = new XenForo_Phrase('rebuilding');
		$typePhrase = new XenForo_Phrase('xfa_tourn_converting_brackets');
		$status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

		return $data;
	}

	public function canCancel()
	{
		return false;
	}
}