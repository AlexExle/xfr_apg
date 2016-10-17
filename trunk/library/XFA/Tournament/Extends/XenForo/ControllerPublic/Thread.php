<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
     * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Extends_XenForo_ControllerPublic_Thread extends XFCP_XFA_Tournament_Extends_XenForo_ControllerPublic_Thread
{
    public function actionIndex()
    {
        $response = parent::actionIndex();
        
        if ($response instanceof XenForo_ControllerResponse_View)
        {
            $visitor            = XenForo_Visitor::getInstance();
            $tournamentModel    = $this->_getTournamentModel();
            
    		$thread = $response->params['thread'];
    		if ($thread['discussion_type'] == 'tournament' && $tournamentModel->canView())
    		{        
        		$tournament = $tournamentModel->getTournamentByThreadId($thread['thread_id']);
        		
        		if ($tournament)
        		{
            		$tournament = $tournamentModel->prepareTournament($tournament);
            		
            		/* Get category */
            		$categoryModel  = $this->_getCategoryModel();
            		$category       = $categoryModel->getCategoryById($tournament['tournament_category_id']);
            		
                	/* Get participants */
                	$participants = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournament['tournament_id']), array('join' => XFA_Tournament_Model_Participant::FETCH_USER));
                	             	
                    /* Get thread if any */
            		if ($tournament['discussion_thread_id'])
            		{
            			$threadModel = $this->getModelFromCache('XenForo_Model_Thread');
            			$thread = $threadModel->getThreadById(
            				$tournament['discussion_thread_id'],
            				array(
            					'join' => XenForo_Model_Thread::FETCH_FORUM,
            					'permissionCombinationId' => XenForo_Visitor::getInstance()->permission_combination_id
            				)
            			);
            
            			$null = null;
            			if ($thread
            				&& $thread['discussion_type'] == 'tournament'
            				&& !$threadModel->canViewThreadAndContainer(
            					$thread, $thread, $null, XenForo_Permission::unserializePermissions($thread['node_permission_cache'])
            				)
            			)
            			{
            				$thread = false;
            			}
            			if ($thread)
            			{
            				$thread['title'] = XenForo_Helper_String::censorString($thread['title']);
            			}
            		}
            		else
            		{
            			$thread = false;
            		}
            		
            		$response->params['selectedTab']                = 'discussion';
            		$response->params['tournament']                 = $tournament;
            		$response->params['category']                   = $category;
            		$response->params['categories']                 = $categoryModel->getAllCategories();
            		$response->params['participants']               = $participants;            		
            		$response->params['canEditTournament']          = $tournamentModel->canEdit($tournament,$category);
            		$response->params['canDeleteTournament']        = $tournamentModel->canDelete($tournament,$category);
            		$response->params['canRegisterTournament']      = $tournamentModel->canRegister($tournament,$category);
            		$response->params['canUnregisterTournament']    = $tournamentModel->canUnregister($tournament,$category);
            		$response->params['canManageTournament']        = $tournamentModel->canManage($tournament, $category);
            		$response->params['canInviteTournament']        = $tournamentModel->canInvite($tournament, $category);
            		$response->params['canAddToTournament']         = $tournamentModel->canAddUser($tournament, $category);
                }
            }
        }
        
        return $response;
	}    
	
	protected function _getTournamentModel()
	{
    	return $this->getModelFromCache('XFA_Tournament_Model_Tournament');
	}
    
	protected function _getCategoryModel()
	{
		return $this->getModelFromCache('XFA_Tournament_Model_Category');
	}
			
	protected function _getParticipantModel()
	{
        return $this->getModelFromCache('XFA_Tournament_Model_Participant');
	}   
}