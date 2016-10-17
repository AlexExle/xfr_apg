<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_AlertHandler_Tournament extends XenForo_AlertHandler_Abstract
{
	protected $_tournamentModel;

	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$tournamentModel = $this->_getTournamentModel();

		$tournaments = $tournamentModel->getTournamentsByIds($contentIds);
		foreach ($tournaments AS &$tournament)
		{
			$tournament['title'] = XenForo_Helper_String::censorString($tournament['title']);
		}

		return $tournaments;
	}

	public function canViewAlert(array $alert, $content, array $viewingUser)
	{
		$tournamentModel = $this->_getTournamentModel();

		return $tournamentModel->canView();
	}
	
	protected function _getTournamentModel()
	{
		if (!$this->_tournamentModel)
		{
			$this->_tournamentModel = XenForo_Model::create('XFA_Tournament_Model_Tournament');
		}

		return $this->_tournamentModel;
	}
}