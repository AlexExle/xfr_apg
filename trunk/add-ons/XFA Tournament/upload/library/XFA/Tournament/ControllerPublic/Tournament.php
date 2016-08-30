<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
     * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_ControllerPublic_Tournament extends XenForo_ControllerPublic_Abstract
{
	protected function _preDispatch($action)
    {
        if (!$this->_getTournamentModel()->canView())
        {
			throw $this->getNoPermissionResponseException();
		}
    }
    
    public function actionIndex()
    {
		if ($tournamentId = $this->_input->filterSingle('tournament_id', XenForo_Input::UINT))
		{
			return $this->responseReroute(__CLASS__, 'view');
		}        
		
        /* Get usefull models */
		$tournamentModel    = $this->_getTournamentModel();
		$categoryModel      = $this->_getCategoryModel();

        /* Handle per page display */
		$page       = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage    = XenForo_Application::get('options')->xfa_tourn_tournamentsPerPage;
		
		$conditions  = array();

		/* Get order and direction if provided or set default (necessary for the tabs) */
		$order = $this->_input->filterSingle('order', XenForo_Input::STRING, array('default' => 'free_slots'));

        $fetchOptions = array(
            'join'      => XFA_Tournament_Model_Tournament::FETCH_USER | XFA_Tournament_Model_Tournament::FETCH_CATEGORY | XFA_Tournament_Model_Tournament::FETCH_WINNER,
            'perPage'   => $perPage,
            'page'      => $page,
            'order'     => $order,
            'direction' => ($order == 'end_date' ? 'ASC' : 'DESC')
        );

        /* Get categories */
		$categories = $categoryModel->getAllCategories();

		$totalTournaments = $tournamentModel->countTournaments($conditions, $fetchOptions);

		$this->canonicalizePageNumber($page, $perPage, $totalTournaments, 'tournaments');
		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('tournaments', null, array('page' => $page)));

        /* Get Tournaments */
		$tournaments = $tournamentModel->getTournaments($conditions, $fetchOptions);	
		
		/* Get top winners */
		$topWinners = array();
		if (XenForo_Application::get('options')->xfa_tourn_nbTopWinners > 0)
		{
    		$topWinners = $tournamentModel->getTopWinners(XenForo_Application::get('options')->xfa_tourn_nbTopWinners, null);
        }

		/* Get team top winners */
		$teamTopWinners = array();
		if (XenForo_Application::get('options')->xfa_tourn_nbTopWinners > 0)
		{
			$teamTopWinners = $tournamentModel->getTopTeamWinners(XenForo_Application::get('options')->xfa_tourn_nbTopWinners, null);
		}
		
		$pageNavParams = array('order' => ($order != 'free_slots' ? $order : false));

		$viewParams = array(
			'categories'        => $categories,
			'tournaments'       => $tournamentModel->prepareTournaments($tournaments),
			'topWinners'        => $topWinners,
			'totalTournaments'  => $totalTournaments,
			'canAddTournament'  => $tournamentModel->canAdd(),
			'page'              => $page,
			'perPage'           => $perPage,
			'order'             => $order,
			'pageNavParams'     => $pageNavParams,
			'teamTopWinners'    => $teamTopWinners
		);
		
		return $this->responseView('XFA_Tournament_ViewPublic_Index', 'xfa_tourn_tournaments_index', $viewParams);
    }
    
    public function actionCategory()
    {
    	$category = $this->_getTournamentHelper()->getCategoryOrError();

        /* Get usefull models */
		$tournamentModel    = $this->_getTournamentModel();
		$categoryModel      = $this->_getCategoryModel();

		$conditions  = array('tournament_category_id' => $category['tournament_category_id']);

		/* Get order and direction if provided or set default (necessary for the tabs) */
		$order = $this->_input->filterSingle('order', XenForo_Input::STRING, array('default' => 'free_slots'));

        /* Get categories */
		$categories = $categoryModel->getAllCategories();

        /* Handle per page display */
		$page       = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage    = XenForo_Application::get('options')->xfa_tourn_tournamentsPerPage;

        $fetchOptions = array(
            'join'      => XFA_Tournament_Model_Tournament::FETCH_USER | XFA_Tournament_Model_Tournament::FETCH_CATEGORY | XFA_Tournament_Model_Tournament::FETCH_WINNER,
            'perPage'   => $perPage,
            'page'      => $page,
            'order'     => $order,
            'direction' => ($order == 'end_date' ? 'ASC' : 'DESC')
        );

		$totalTournaments = $tournamentModel->countTournaments($conditions, $fetchOptions);
		
		$this->canonicalizePageNumber($page, $perPage, $totalTournaments, 'tournaments/categories');
		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('tournaments/categories', $category, array('page' => $page)));

		$tournaments = $tournamentModel->getTournaments($conditions, $fetchOptions);	

		/* Get top winners */
		$topWinners = array();
		if (XenForo_Application::get('options')->xfa_tourn_nbTopWinners > 0)
		{
    		$topWinners = $tournamentModel->getTopWinners(XenForo_Application::get('options')->xfa_tourn_nbTopWinners, $category['tournament_category_id']);
        }

		/* Get team top winners */
		$teamTopWinners = array();
		if (XenForo_Application::get('options')->xfa_tourn_nbTopWinners > 0)
		{
			$teamTopWinners = $tournamentModel->getTopTeamWinners(XenForo_Application::get('options')->xfa_tourn_nbTopWinners, $category['tournament_category_id']);
		}


		$pageNavParams = array('order' => ($order != 'free_slots' ? $order : false));
		
		$viewParams = array(
    		'category'              => $category,
			'categories'            => $categories,
			'topWinners'            => $topWinners,			
			'tournaments'           => $tournamentModel->prepareTournaments($tournaments),
			'totalTournaments'      => $totalTournaments,
			'canAddTournament'      => $tournamentModel->canAdd(),
			'page'                  => $page,
			'perPage'               => $perPage,
			'order'                 => $order,
			'pageNavParams'         => $pageNavParams,
			'selectedCategoryId'    => $category['tournament_category_id'],
			'teamTopWinners'    => $teamTopWinners
		);
		
		return $this->responseView('XFA_Tournament_ViewPublic_Category_View', 'xfa_tourn_category', $viewParams);
    }
    
	protected function _getTournamentAddOrEditResponse(array $tournament, array $category)
	{
		$viewParams = array(
			'tournament'    => $tournament,
			'category'      => $category,
			'categories'    => $this->_getCategoryModel()->getAllCategories()
		);

		return $this->responseView('XFA_Tournament_ViewPublic_Tournament_Add', 'xfa_tourn_tournament_add', $viewParams);
	}    
    
	public function actionAdd()
	{ 		
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

        /* Check can add */
        if (!$tournamentModel->canAdd())
        {
			throw $this->getNoPermissionResponseException();
        }

        /* Check if category provided */
		$categoryId = $this->_input->filterSingle('tournament_category_id', XenForo_Input::UINT);
		if ($categoryId)
		{
			$category = $this->_getTournamentHelper()->getCategoryOrError($categoryId);
		}
		else
		{
			$category = false;
		}
		
		if (!$category)
		{
			$categories = $categoryModel->getAllCategories();
			return $this->responseView('XFA_Tournament_ViewPublic_Tournament_ChooseCategory', 'xfa_tourn_tournament_choose_category', array(
				'categories' =>$categories
			));
		}
		else
		{
			$tournament = array(
				'tournament_category_id' => $categoryId
			);

			return $this->_getTournamentAddOrEditResponse($tournament, $category);
		}
	}

	public function actionEdit()
	{
    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
    	$category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());

		if (!$this->_getTournamentModel()->canEdit($tournament, $category, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
		
		return $this->_getTournamentAddOrEditResponse($tournament, $category);
	}
	
	public function actionSave()
	{
		$this->_assertPostOnly();

		$categoryModel = $this->_getCategoryModel();

        /* Check permission to edit the tournament if edit */
		if ($tournamentId = $this->_input->filterSingle('tournament_id', XenForo_Input::UINT))
		{
    	    $tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
            $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
            
			if (!$this->_getTournamentModel()->canEdit($tournament, $category, $errorPhraseKey))
			{
				throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
			}
		}
		else
		{
			$category   = false;
			$tournament = false;
		}

        /* Set up tournament data */
		$tournamentData = $this->_input->filter(array(
			'tournament_category_id'    => XenForo_Input::UINT,
			'type'                      => XenForo_Input::STRING,
			'title'                     => XenForo_Input::STRING,
			'slots'                     => XenForo_Input::UINT,	
			'end_date'                  => XenForo_Input::UINT,	
			'private'                   => XenForo_Input::UINT,	
			'automatic_generation'      => XenForo_Input::UINT,
			'third_place'               => XenForo_Input::UINT,
			'team_mode'                 => XenForo_Input::UINT
		));
		$tournamentData['description']  = $this->getHelper('Editor')->getMessageText('description', $this->_input);
		$tournamentData['description']  = XenForo_Helper_String::autoLinkBbCode($tournamentData['description']);
		$tournamentData['rules']        = $this->getHelper('Editor')->getMessageText('rules', $this->_input);
		$tournamentData['rules']        = XenForo_Helper_String::autoLinkBbCode($tournamentData['rules']);
		
		if ($tournamentData['slots'] > 256)
		{
    		$tournamentData['slots'] = 256;
		}
		
		/* No category, error */
		if (!$tournamentData['tournament_category_id'])
		{
			return $this->responseError(new XenForo_Phrase('xfa_tourn_you_must_select_category'));
		}

		/* Let's write the data */
		$dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Tournament');

		if ($tournamentId)
		{
			$dw->setExistingData($tournament['tournament_id']);
			
			/* If tournament started we only allow description/title/category */
			if ($tournament['bracket'])
			{
    			unset($tournamentData['type']);
    			unset($tournamentData['slots']);
    			unset($tournamentData['end_date']);
    			unset($tournamentData['private']);
    			unset($tournamentData['automatic_generation']);
    			unset($tournamentData['third_place']);
				unset($tournamentData['team_mode']);
            }
		}
		else
		{
    		/* Set user info */
			$visitor = XenForo_Visitor::getInstance();

			$dw->set('user_id',  $visitor['user_id']);
			$dw->set('username', $visitor['username']);
            $dw->set('creation_date', XenForo_Application::$time);
		}

		$dw->bulkSet($tournamentData);
		$dw->set('last_update', XenForo_Application::$time);
		
		$dw->preSave();
		$dw->save();
		
		$tournament = $dw->getMergedData();

        /* Success, redirect */
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('tournaments', $tournament)
		);
	}	
	
	public function actionView()
	{
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
    	
    	/* Get participants and bracket data */
        list($participants, $bracketData) = $tournamentModel->getParticipantsAndBracket($tournament);
        
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
        
    	
		$viewParams = array(
    		'selectedTab'               => 'content',
    		'thread'                    => $thread,
    		'bracketData'               => $bracketData,
			'tournament'                => $tournament,
			'category'                  => $category,
			'categories'                => $categoryModel->getAllCategories(),
			'participants'              => $participants,
			'canEditTournament'         => $tournamentModel->canEdit($tournament,$category),
			'canDeleteTournament'       => $tournamentModel->canDelete($tournament,$category),
			'canRegisterTournament'     => $tournamentModel->canRegister($tournament,$category),
			'canUnregisterTournament'   => $tournamentModel->canUnregister($tournament,$category),
			'canManageTournament'       => $tournamentModel->canManage($tournament, $category),
			'canInviteTournament'       => $tournamentModel->canInvite($tournament, $category),
			'canAddToTournament'        => $tournamentModel->canAddUser($tournament, $category),
		);

		return $this->responseView('XFA_Tournament_ViewPublic_Tournament_View', 'xfa_tourn_tournament', $viewParams);
	}
	
	public function actionRules()
	{
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());

    	/* Get participants */
		$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);
    	$participants = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournament['tournament_id']), array('join' => $fetchOption));
    	             	
    	/* If no rules => error */
    	if (!$tournament['rules'])
    	{
			throw $this->getNoPermissionResponseException();
    	}
    	
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
		
		$viewParams = array(
    		'selectedTab'               => 'rules',
    		'thread'                    => $thread,
			'tournament'                => $tournament,
			'category'                  => $category,
			'categories'                => $categoryModel->getAllCategories(),
			'participants'              => $participants,
			'canEditTournament'         => $tournamentModel->canEdit($tournament,$category),
			'canDeleteTournament'       => $tournamentModel->canDelete($tournament,$category),
			'canRegisterTournament'     => $tournamentModel->canRegister($tournament,$category),
			'canUnregisterTournament'   => $tournamentModel->canUnregister($tournament,$category),
			'canManageTournament'       => $tournamentModel->canManage($tournament, $category),
			'canInviteTournament'       => $tournamentModel->canInvite($tournament, $category),
			'canAddToTournament'        => $tournamentModel->canAddUser($tournament, $category),
		);

		return $this->responseView('XFA_Tournament_ViewPublic_Tournament_View', 'xfa_tourn_tournament_rules', $viewParams);
	}
	
	public function actionDelete()
	{
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
        
    	/* Check if user can manage */
    	if (!$tournamentModel->canDelete($tournament, $category))
    	{
			throw $this->getNoPermissionResponseException();
    	}
    	
    	if ($this->isConfirmedPost())
    	{
    		$dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Tournament');
    
    		$visitor = XenForo_Visitor::getInstance();
    		$dw->setExistingData($tournament['tournament_id']);
    		$dw->delete();
    		
            /* Redirect */
    		return $this->responseRedirect(
    			XenForo_ControllerResponse_Redirect::SUCCESS,
    			XenForo_Link::buildPublicLink('tournaments')
    		);			
        }
        else
        {
    		$viewParams = array(
        		'category'      => $category,
        		'tournament'    => $tournament
    		);
    
    		return $this->responseView('XFA_Tournament_ViewPublic_Tournament_Delete', 'xfa_tourn_delete_tournament', $viewParams);
        }
	}
	
	public function actionRegister()
	{
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
    	
    	/* Check if user can register */
    	if (!$tournamentModel->canRegister($tournament, $category))
    	{
			throw $this->getNoPermissionResponseException();
    	}
    	
		/* Let's write the data */
		$dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Participant');

		/* Set user info */
		$visitor = XenForo_Visitor::getInstance();
		$dw->set('tournament_id',  $tournament['tournament_id']);
		$dw->set('user_id',  $visitor['user_id']);
		$dw->set('username', $visitor['username']);
		$dw->save();
		
        /* Redirect */
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('tournaments', $tournament)
		);		
	}
	
	public function actionUnregister()
	{
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
    	
    	/* Check if user is registered */
    	if (!$tournamentModel->canUnregister($tournament, $category))
    	{
			throw $this->getNoPermissionResponseException();
    	}
    	
		/* Let's write the data */
		$dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Participant');

		/* Set user info */
		$visitor = XenForo_Visitor::getInstance();
		$dw->setExistingData($tournament['isRegistered']);
		$dw->delete();
		
        /* Redirect */
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('tournaments', $tournament)
		);		
	}
	
	public function actionUnvalidateMatches()
	{
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
    	
    	/* Check if user can manage */
    	if (!$tournamentModel->canManage($tournament, $category))
    	{
			throw $this->getNoPermissionResponseException();
    	}	
    	
    	/* Check if bracket not existing */
    	if (!$tournament['bracket'])
    	{
			throw $this->getNoPermissionResponseException();
    	}    	

        $tournamentModel->resetBracket($tournament['tournament_id']);
		
        /* Post message in the thread */
        if ($tournament['discussion_thread_id'])
        {
			$postWriter = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post', XenForo_DataWriter::ERROR_SILENT);
            
    		$message = new XenForo_Phrase('xfa_tourn_message_matches_unvalidated', array(
    			'title'     => $tournament['title'],
    			'link'      => XenForo_Link::buildPublicLink('canonical:tournaments', $tournament)
    		), false);
    
            $postWriter->set('thread_id', $tournament['discussion_thread_id']);
    		$postWriter->set('message', $message->render());
    		$postWriter->set('user_id', $tournament['user_id']);
    		$postWriter->set('username', $tournament['username']);
    		$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_IS_AUTOMATED, true);
    		$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_PUBLISH_FEED, false);
            $postWriter->save();
        }		
		
        /* Redirect */
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('tournaments', $tournament)
		);		
	}	
	
	public function actionGenerateMatches()
	{
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
    	
    	/* Check if user can manage */
    	if (!$tournamentModel->canManage($tournament, $category))
    	{
			throw $this->getNoPermissionResponseException();
    	}	
    	
    	/* Check if bracket not already existing */
    	if ($tournament['bracket'])
    	{
			throw $this->getNoPermissionResponseException();
    	}
    	
    	if ($this->isConfirmedPost())
    	{
        	/* Get the json data */
            $bracketData = json_decode($this->_input->filterSingle('bracketData', XenForo_Input::STRING), true);

        	if ($tournament['type'] == 'round_robin')
        	{
            	/* Go through the teams and match to reomve the label stuff */
            	foreach($bracketData['teams'] AS &$team)
            	{
                	$team = $team['team'];
                	unset($team['label']);
            	}
            	
            	foreach($bracketData['matches'] AS &$match)
            	{
                	if (!is_int($match['a']['team']))
                	{
                	    $match['a']['team'] = $match['a']['team']['id'];
                    }
                    
                    if (!is_int($match['b']['team']))
                    {
                	    $match['b']['team'] = $match['b']['team']['id'];
                    }
            	}
        	}
        	else
        	{
             	/* Get the json data */
                $bracketData = json_decode($this->_input->filterSingle('bracketData', XenForo_Input::STRING), true);
                
                /* Remove useless stuff from the json data */
                foreach($bracketData['teams'] AS $idxTeam => $team)
                {
                    $bracketData['teams'][$idxTeam][0] = $team[0]['id'];
                    $bracketData['teams'][$idxTeam][1] = $team[1]['id'];
                }
        	}

            $tournamentModel->writeBracket($tournament['tournament_id'], json_encode($bracketData));
            
            /* Post message in the thread */
            if ($tournament['discussion_thread_id'])
            {
    			$postWriter = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post', XenForo_DataWriter::ERROR_SILENT);
                
        		$message = new XenForo_Phrase('xfa_tourn_message_matches_started', array(
        			'title'     => $tournament['title'],
        			'link'      => XenForo_Link::buildPublicLink('canonical:tournaments', $tournament)
        		), false);
        
                $postWriter->set('thread_id', $tournament['discussion_thread_id']);
        		$postWriter->set('message', $message->render());
        		$postWriter->set('user_id', $tournament['user_id']);
        		$postWriter->set('username', $tournament['username']);
        		$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_IS_AUTOMATED, true);
        		$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_PUBLISH_FEED, false);
                $postWriter->save();
            }
        	
            /* Redirect */
    		return $this->responseRedirect(
    			XenForo_ControllerResponse_Redirect::SUCCESS,
    			XenForo_Link::buildPublicLink('tournaments', $tournament)
    		);		
        }
        else
        {
            /* Get and shuffle participants */
			$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);
        	$participants   = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournament['tournament_id']), array('join' => $fetchOption));
            shuffle($participants);
            $nbParticipants = count($participants);
            
            /* Handle depending on the type of tournament */
            switch ($tournament['type'])
            {
                case 'single_el':
                case 'double_el':
                    /* Check we have enough users */
                    if ($tournament['type'] == 'single_el')
                    {
                        if ($nbParticipants < 2)
                        {
                            return $this->responseError(new XenForo_Phrase('xfa_tourn_single_el_need_at_least_2_users'));
                        }        
                    }
                    else
                    {
                        if ($nbParticipants < 4)
                        {
                            return $this->responseError(new XenForo_Phrase('xfa_tourn_double_el_need_at_least_4_users'));
                        }         
                    }
                    
                    /* Check number of users is power of 2 */
                    if (($nbParticipants & ($nbParticipants - 1)) != 0)
                    {
                        return $this->responseError(new XenForo_Phrase('xfa_tourn_users_must_be_power_of_2'));
                    }
                    
                    /* Group participants by 2 */
                    $teams  = array();
                    $i      = 0;
                    foreach($participants AS $participant)
                    {
                        $teams[(int)$i / 2][$i % 2] = $participant;
                        $i++;
                    }                    
                          
                    /* Set view params */ 
            		$viewParams = array(
                		'category'      => $category,
                		'tournament'    => $tournament,
                		'teams'         => $teams
            		);     
            		                                           
                    break;
                case 'round_robin':
                default:
                    /* Check we have enough users */
                    if ($nbParticipants < 2)
                    {
                        return $this->responseError(new XenForo_Phrase('xfa_tourn_single_el_need_at_least_2_users'));
                    }
                
                    /* Prepare bracket */
                	$bracketData = '{ teams: [';
                	$i = 0;
                	foreach($participants AS $participant)
                	{
                	    $bracketData    .= '{id:"' . $i . '", pid:"' . ($participant['participant_id']) . '", name: "' . $participant['username'] . '"},';
                    	$players[]      = $i++;
                	}
                	
                    $bracketData .= '],';
                    $bracketData .= 'matches:' . json_encode($tournamentModel->roundRobin($players));
                    $bracketData .= '}';                    
                          
                    /* Set view params */ 
            		$viewParams = array(
                		'category'      => $category,
                		'tournament'    => $tournament,
                		'bracketData'   => $bracketData
            		);  
            		              
                    break;
            }
    
    		return $this->responseView('XFA_Tournament_ViewPublic_Tournament_GenerateBracket', 'xfa_tourn_generate_bracket', $viewParams);
        }
	}
	
	public function actionSaveScores()
	{
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
    	
    	/* Check if user can manage */
    	if (!($tournamentModel->canManage($tournament, $category) && $tournament['bracket']))
    	{
			throw $this->getNoPermissionResponseException();
    	}	    	
        
    	/* Bracket not available, error */
    	if (!$tournament['bracket'])
    	{
			throw $this->getNoPermissionResponseException();
        }
    	
    	/* Get results */
    	if ($tournament['type'] == 'round_robin')
    	{
        	/* Get the json data */
            $bracketData = json_decode($this->_input->filterSingle('bracketData', XenForo_Input::STRING), true);

        	/* Go through the teams and match to reomve the label stuff */
        	foreach($bracketData['teams'] AS &$team)
        	{
            	$team = $team['team'];
            	unset($team['label']);
        	}
        	
        	foreach($bracketData['matches'] AS &$match)
        	{
            	if (!is_int($match['a']['team']))
            	{
            	    $match['a']['team'] = $match['a']['team']['id'];
                }
                
                if (!is_int($match['b']['team']))
                {
            	    $match['b']['team'] = $match['b']['team']['id'];
                }
        	}
        	
        	/* We need to reorder the teams bracket to ensure they are kept in the correct order (id) */
        	usort($bracketData['teams'], function($a, $b) { return $a['id'] - $b['id']; });        	
        }
        else
        {
            $results = $this->_input->filterSingle('results', XenForo_Input::ARRAY_SIMPLE);

        	/* Get current bracket data */
        	$bracketData = json_decode($tournament['bracket'], true);
        	
        	/* Merge new results to bracket and save tournament */
        	$bracketData['results'] = $results;
        }

        $tournamentModel->updateTournament($tournament, $bracketData);
    	
        /* Redirect */
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('tournaments', $tournament)
		);
	}
	
	public function actionManageUsers()
	{
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
    	
    	/* Check if user can manage */
    	if ($tournament['bracket'] || !$tournamentModel->canManage($tournament, $category))
    	{
			throw $this->getNoPermissionResponseException();
    	}	
    	
    	if ($this->isConfirmedPost())
    	{     	
            $users = $this->_input->filterSingle('kick', XenForo_Input::ARRAY_SIMPLE);
            
            $this->_getParticipantModel()->removeTournamentParticipants($tournament['tournament_id'], $users);
            
            /* Redirect */
    		return $this->responseRedirect(
    			XenForo_ControllerResponse_Redirect::SUCCESS,
    			XenForo_Link::buildPublicLink('tournaments', $tournament)
    		);		
        }
        else
        {
			$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);
    		$viewParams = array(
        		'category'      => $category,
        		'tournament'    => $tournament,
        		'participants'  => $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournament['tournament_id']), array('join' => $fetchOption))
    		);
    
    		return $this->responseView('XFA_Tournament_ViewPublic_Tournament_ManageUsers', 'xfa_tourn_manage_users', $viewParams);
        }
    }
    
    public function actionInviteUsers()
    {
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
    	
    	/* Check if user can invite */
    	if ($tournament['bracket'] || !$tournamentModel->canInvite($tournament, $category))
    	{
			throw $this->getNoPermissionResponseException();
    	}
    	
    	if ($this->isConfirmedPost())
    	{     	
            $usernames = $this->_input->filterSingle('usernames', XenForo_Input::STRING);

            /* Check users exists */
            if (!$usernames)
            {
				return $this->responseError(new XenForo_Phrase('xfa_tourn_requested_users_not_found'));
            }
            
			$userModel  = $this->getModelFromCache('XenForo_Model_User');
			$users      = $userModel->getUsersByNames(explode(',', $usernames));
			if (!$users)
			{
				return $this->responseError(new XenForo_Phrase('xfa_tourn_requested_users_not_found'));
			}
            
            /* Alert users */
            $visitor = XenForo_Visitor::getInstance();
            
    		foreach ($users AS $user)
    		{          		
				XenForo_Model_Alert::alert(
					$user['user_id'],
					$visitor['user_id'],
					$visitor['username'],
					'xfa_tourn',
					$tournament['tournament_id'],
					'invite'
				);
    		}  
    		
    		/* Register invites */
            $dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Tournament');
            $dw->setExistingData($tournament['tournament_id']);
            
            if (empty($tournament['invites']))
            {
                $dw->set('invites', implode(',', array_keys($users)));
            }
            else
            {
                $invites = explode(',', $tournament['invites']);
                $invites = array_merge($invites, array_keys($users));
                
                $dw->set('invites', implode(',', $invites));
            }
            $dw->save();
            
            /* Redirect */
    		return $this->responseRedirect(
    			XenForo_ControllerResponse_Redirect::SUCCESS,
    			XenForo_Link::buildPublicLink('tournaments', $tournament)
    		);
        }
        else
        {
    		$viewParams = array(
        		'category'      => $category,
        		'tournament'    => $tournament
    		);
    
    		return $this->responseView('XFA_Tournament_ViewPublic_Tournament_InviteUsers', 'xfa_tourn_invite_users', $viewParams);
        }   
    }
    
    public function actionAddUsers()
    {
		$categoryModel      = $this->_getCategoryModel();
		$tournamentModel    = $this->_getTournamentModel();

    	$tournament = $this->_getTournamentHelper()->getTournamentOrError(null, array());
        $category   = $this->_getTournamentHelper()->getCategoryOrError($tournament['tournament_category_id'], array());
    	
    	/* Check if user can add */
    	if ($tournament['bracket'] || !$tournamentModel->canAddUser())
    	{
			throw $this->getNoPermissionResponseException();
    	}
    	
    	if ($this->isConfirmedPost())
    	{


			$participantIdField='';
			$participantNameField='';
			$participants = null;

			XenForo_Error::logError("team mode " + $tournament['team_mode']);
			if ($tournament['team_mode'] == 1)
			{
				$teamnames = $this->_input->filterSingle('teamnames', XenForo_Input::STRING);
				XenForo_Error::logError("$teamnames " + $teamnames);
				$teamModel = $this->getModelFromCache('Nobita_Teams_Model_Team');
				$teams = $teamModel->getTeamsByTitles(explode(',', $teamnames));
				if (!$teams) {
					return $this->responseError(new XenForo_Phrase('xfa_tourn_requested_users_not_found'));
				}
				$participantIdField='team_id';
				$participantNameField='title';
				$participants = $teams;
			}
			else {
				$usernames = $this->_input->filterSingle('usernames', XenForo_Input::STRING);

				/* Check users exists */
				if (!$usernames) {
					return $this->responseError(new XenForo_Phrase('xfa_tourn_requested_users_not_found'));
				}
				$userModel = $this->getModelFromCache('XenForo_Model_User');
				$users = $userModel->getUsersByNames(explode(',', $usernames));
				if (!$users) {
					return $this->responseError(new XenForo_Phrase('xfa_tourn_requested_users_not_found'));
				}

				$participantIdField = 'user_id';
				$participantNameField = 'username';
				$participants = $users;
			}
			/* Check number of users is not higher than slots */
			if (count($participants) > ($tournament['slots'] - $tournament['user_count'])) {
				return $this->responseError(new XenForo_Phrase('xfa_tourn_too_much_users_provided'));
			}

			/* Add users and send alert */
			$visitor = XenForo_Visitor::getInstance();

			foreach ($participants AS $part) {
				/* Let's write the data */
				$dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Participant');

				/* Set user info */
				$visitor = XenForo_Visitor::getInstance();
				$dw->set('tournament_id', $tournament['tournament_id']);
				$dw->set('user_id', $part[$participantIdField]);
				$dw->set('username', $part[$participantNameField]);
				$dw->save();

				if ($tournament['team_mode'] == 1){
					$teamModel = $this->getModelFromCache('Nobita_Teams_Model_Team');
					$teamModel->massAlert($part, 'Your team added to the tournament ' . $tournament['title']);
				}
				else {
					XenForo_Model_Alert::alert(
						$part[$participantIdField],
						$visitor['user_id'],
						$visitor['username'],
						'xfa_tourn',
						$tournament['tournament_id'],
						'added_to_tournament'
					);
				}
			}

			/* Redirect */
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('tournaments', $tournament)
			);

        }
        else
        {
    		$viewParams = array(
        		'category'      => $category,
        		'tournament'    => $tournament
    		);
    
    		return $this->responseView('XFA_Tournament_ViewPublic_Tournament_AddUsers', 'xfa_tourn_add_users', $viewParams);
        }   
    }    
    
    public function actionMyTournaments()
    {        
        /* Get usefull models */
		$tournamentModel    = $this->_getTournamentModel();
		$categoryModel      = $this->_getCategoryModel();

        /* Handle per page display */
		$page       = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage    = XenForo_Application::get('options')->xfa_tourn_tournamentsPerPage;
		
		$conditions  = array(
    		'user_id' => XenForo_Visitor::getUserId()
		);

        $fetchOptions = array(
            'join'      => XFA_Tournament_Model_Tournament::FETCH_USER | XFA_Tournament_Model_Tournament::FETCH_CATEGORY | XFA_Tournament_Model_Tournament::FETCH_WINNER,
            'perPage'   => $perPage,
            'page'      => $page,
            'direction' => 'DESC'
        );
        
		$totalTournaments = $tournamentModel->countTournaments($conditions, $fetchOptions);

		$this->canonicalizePageNumber($page, $perPage, $totalTournaments, 'tournaments/my-tournaments');
		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('tournaments/my-tournaments', null, array('page' => $page)));

        /* Get Tournaments */
		$tournaments = $tournamentModel->getTournaments($conditions, $fetchOptions);	

		$viewParams = array(
			'tournaments'       => $tournamentModel->prepareTournaments($tournaments),
			'totalTournaments'  => $totalTournaments,
			'page'              => $page,
			'perPage'           => $perPage
		);
		
		return $this->responseView('XFA_Tournament_ViewPublic_Index', 'xfa_tourn_my_tournaments', $viewParams);        
    }   
    
    public function actionParticipatedTournaments()
    {        
        /* Get usefull models */
		$tournamentModel    = $this->_getTournamentModel();
		$categoryModel      = $this->_getCategoryModel();

        /* Get Tournament ids in which user participated */
        $tournamentIdsArray = $tournamentModel->getParticipatedTournamentsByUserId(XenForo_Visitor::getUserId());

        /* Handle per page display */
		$page       = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage    = XenForo_Application::get('options')->xfa_tourn_tournamentsPerPage;
		
		$conditions  = array(
    		'tournament_id' => $tournamentIdsArray
		);

        $fetchOptions = array(
            'join'      => XFA_Tournament_Model_Tournament::FETCH_USER | XFA_Tournament_Model_Tournament::FETCH_CATEGORY | XFA_Tournament_Model_Tournament::FETCH_WINNER,
            'perPage'   => $perPage,
            'page'      => $page,
            'direction' => 'DESC'
        );
        
		$totalTournaments = $tournamentModel->countTournaments($conditions, $fetchOptions);

		$this->canonicalizePageNumber($page, $perPage, $totalTournaments, 'tournaments/participated-tournaments');
		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('tournaments/participated-tournaments', null, array('page' => $page)));

        /* Get Tournaments */
		$tournaments = $tournamentModel->getTournaments($conditions, $fetchOptions);	

		$viewParams = array(
			'tournaments'       => $tournamentModel->prepareTournaments($tournaments),
			'totalTournaments'  => $totalTournaments,
			'page'              => $page,
			'perPage'           => $perPage
		);
		
		return $this->responseView('XFA_Tournament_ViewPublic_Index', 'xfa_tourn_participated_tournaments', $viewParams);        
    }
    
	protected function _getCategoryModel()
	{
		return $this->getModelFromCache('XFA_Tournament_Model_Category');
	}
	
	protected function _getTournamentModel()
	{
    	return $this->getModelFromCache('XFA_Tournament_Model_Tournament');
	}
	
	protected function _getParticipantModel()
	{
        return $this->getModelFromCache('XFA_Tournament_Model_Participant');
	}   
	
	protected function _getTournamentHelper()
	{
		return $this->getHelper('XFA_Tournament_ControllerHelper_Tournament');
	}   
}