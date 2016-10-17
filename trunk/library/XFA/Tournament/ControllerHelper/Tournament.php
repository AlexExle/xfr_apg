 <?php
/*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_ControllerHelper_Tournament extends XenForo_ControllerHelper_Abstract
{	
	public function getCategoryOrError($categoryId = null, array $fetchOptions = array())
	{
    	/* If no category id get it from input */
		if ($categoryId === null)
		{
			$categoryId = $this->_controller->getInput()->filterSingle('tournament_category_id', XenForo_Input::UINT);
		}

        /* Get category */
		$category = $this->_controller->getModelFromCache('XFA_Tournament_Model_Category')->getCategoryById(
			$categoryId, $fetchOptions
		);

		/* Category not found */
		if (!$category)
		{
			throw $this->_controller->responseException(
				$this->_controller->responseError(new XenForo_Phrase('xfa_tourn_requested_category_not_found'), 404)
			);
		}

		return $category;
	}	
	
    public function getTournamentOrError($tournamentId = null, array $fetchOptions = array())
	{
    	/* If no tournament id, retrieve it from the input */
		if ($tournamentId === null)
		{
			$tournamentId = $this->_controller->getInput()->filterSingle('tournament_id', XenForo_Input::UINT);
		}

        /* Get tournament */
		$tournament = $this->_controller->getModelFromCache('XFA_Tournament_Model_Tournament')->getTournamentById($tournamentId, $fetchOptions);
		
		/* Document not found */
		if (!$tournament)
		{
			throw $this->_controller->responseException(
				$this->_controller->responseError(new XenForo_Phrase('xfa_tourn_requested_tournament_not_found'), 404)
			);
		}

		return $this->_controller->getModelFromCache('XFA_Tournament_Model_Tournament')->prepareTournament($tournament);
	}
	
	protected function _getUserModel()
	{
        return $this->getModelFromCache('XenForo_Model_User');
	}  
}