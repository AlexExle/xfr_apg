<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Model_Tournament extends XenForo_Model
{
	const FETCH_USER        = 0x01;
	const FETCH_CATEGORY    = 0x02;
	const FETCH_WINNER      = 0x04;
	
	public function getTournamentById($tournamentId, array $fetchOptions = array())
	{
    	$conditions = array('tournament_id' => $tournamentId);
    	
		$whereClause    = $this->prepareTournamentConditions($conditions, $fetchOptions);
		$joinOptions    = $this->prepareTournamentFetchOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT tournament.*
				' . $joinOptions['selectFields'] . '
			FROM xfa_tourn_tournament AS tournament
				' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause . '
		');
	}		
	
	public function getTournamentByThreadId($threadId, array $fetchOptions = array())
	{
    	$conditions = array('thread_id' => $threadId);
    	
		$whereClause    = $this->prepareTournamentConditions($conditions, $fetchOptions);
		$joinOptions    = $this->prepareTournamentFetchOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT tournament.*
				' . $joinOptions['selectFields'] . '
			FROM xfa_tourn_tournament AS tournament
				' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause . '
		');
	}		
	
	public function getTournamentsByIds($tournamentIds, array $fetchOptions = array())
	{
		if (!$tournamentIds)
		{
			return array();
		}
		
    	$conditions     = array('tournament_id' => $tournamentIds);
    	
		$whereClause    = $this->prepareTournamentConditions($conditions, $fetchOptions);
		$joinOptions    = $this->prepareTournamentFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT tournament.*
				' . $joinOptions['selectFields'] . '
			FROM xfa_tourn_tournament AS tournament
				' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause . '
		', 'tournament_id');
    }
	
	public function getTournaments(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause    = $this->prepareTournamentConditions($conditions, $fetchOptions);

		$orderClause    = $this->prepareTournamentOrderOptions($fetchOptions, 'tournament.end_date DESC');
		$joinOptions    = $this->prepareTournamentFetchOptions($fetchOptions);
		$limitOptions   = $this->prepareLimitFetchOptions($fetchOptions);
					
		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT tournament.*
					' . $joinOptions['selectFields'] . '
				FROM xfa_tourn_tournament AS tournament
				' . $joinOptions['joinTables'] . '
				WHERE ' . $whereClause . '
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'tournament_id');
	}
	
	public function getTournamentIdsInRange($start, $limit)
	{
		$db = $this->_getDb();

		return $db->fetchCol($db->limit('
			SELECT tournament_id
			FROM xfa_tourn_tournament
			WHERE tournament_id > ?
			ORDER BY tournament_id
		', $limit), $start);
	}
	
	public function getParticipatedTournamentsByUserId($userId)
	{
		$db = $this->_getDb();

		return $db->fetchCol('
			SELECT tournament.tournament_id
			FROM xfa_tourn_participant AS participant
			LEFT JOIN xfa_tourn_tournament AS tournament ON (tournament.tournament_id = participant.tournament_id)
			WHERE (tournament.team_mode = 0 AND participant.user_id = ?) OR (tournament.team_mode = 1 AND  participant.user_id in (
		  		SELECT team_id
		  		FROM  xf_team_member
		  		WHERE user_id = ?
			))
		', array($userId,$userId));
	}
	
	public function countTournaments(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause    = $this->prepareTournamentConditions($conditions, $fetchOptions);
		$joinOptions    = $this->prepareTournamentFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xfa_tourn_tournament AS tournament
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause
		);
	}	  
 
	public function prepareTournamentConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();
		
		if (!empty($conditions['tournament_id']))
		{
			if (is_array($conditions['tournament_id']))
			{
				$sqlConditions[] = 'tournament.tournament_id IN (' . $db->quote($conditions['tournament_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'tournament.tournament_id = ' . $db->quote($conditions['tournament_id']);
			}
		}	
		
		if (!empty($conditions['thread_id']))
		{
            $sqlConditions[] = 'tournament.discussion_thread_id = ' . $db->quote($conditions['thread_id']);
		}
		
		if (!empty($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				$sqlConditions[] = 'tournament.user_id IN (' . $db->quote($conditions['user_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'tournament.user_id = ' . $db->quote($conditions['user_id']);
			}    		
		}
		
		if (isset($conditions['tournament_category_id']))
		{
			if (is_array($conditions['tournament_category_id']))
			{
				if (!$conditions['tournament_category_id'])
				{
					$sqlConditions[] = 'tournament.tournament_category_id IS NULL';
				}
				else
				{
					$sqlConditions[] = 'tournament.tournament_category_id IN (' . $db->quote($conditions['tournament_category_id']) . ')';
				}
			}
			else
			{
				$sqlConditions[] = 'tournament.tournament_category_id = ' . $db->quote($conditions['tournament_category_id']);
			}
		}
		
		if (!empty($conditions['tournament_id_not']))
		{
			$sqlConditions[] = 'tournament.tournament_id <> ' . $db->quote($conditions['tournament_id_not']);
		}
		
		if (isset($conditions['automatic_generation']))
		{
    		$sqlConditions[] = 'tournament.automatic_generation = 1
    		                        AND user_count > 2
                                    AND (
                                            (tournament.end_date = 0 AND tournament.user_count = tournament.slots)
                                            OR
                                            (tournament.end_date > 0 AND tournament.end_date < ' . XenForo_Application::$time . ')
                                        )
                                    AND bracket.bracket IS NULL';
		}

        if (!isset($conditions['private']))
        {
            $sqlConditions[] = 'tournament.private = 0
                                OR (tournament.private = 1
                                    AND (tournament.user_id = ' . $db->quote(XenForo_Visitor::getUserId()) . '
                                            OR find_in_set(' . $db->quote(XenForo_Visitor::getUserId()) . ', tournament.invites)))';
        }

        if (isset($fetchOptions['order']))
        {
            if ($fetchOptions['order'] == 'free_slots')
            {
                $sqlConditions[] = 'tournament.user_count < tournament.slots AND bracket.bracket IS NULL';
            }
            
            if ($fetchOptions['order'] == 'end_date')
            {
                $sqlConditions[] = 'tournament.end_date > ' . XenForo_Application::$time;
            }
        }

		return $this->getConditionsForClause($sqlConditions);
	}	
	
	public function prepareTournamentOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'free_slots'    => 'tournament.end_date',
			'last_update'   => 'tournament.last_update',
			'end_date'      => 'tournament.end_date',
			'newest'        => 'tournament.creation_date'
		);
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

	public function prepareTournamentFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$db = $this->_getDb();

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_CATEGORY)
			{
				$selectFields .= ',
					category.*, category.last_update AS category_last_update, tournament.last_update';
				$joinTables .= '
					LEFT JOIN xfa_tourn_category AS category ON
						(category.tournament_category_id = tournament.tournament_category_id)';
			}
			
			if ($fetchOptions['join'] & self::FETCH_USER)
			{
				$selectFields .= ',
					user.*, user_profile.*, IF(user.username IS NULL, tournament.username, user.username) AS username';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = tournament.user_id)
					LEFT JOIN xf_user_profile AS user_profile ON
						(user_profile.user_id = tournament.user_id)';
			}	
            
            if ($fetchOptions['join'] & self::FETCH_WINNER)
			{
				$selectFields .= ',
					IF (tournament.team_mode = 0, wuser.username, team.title) AS wusername,
					IF (tournament.team_mode = 0, wuser.user_id, team.team_id) AS wid
					';
				$joinTables .= '
					LEFT JOIN xf_user AS wuser ON
						(wuser.user_id = tournament.winner_id AND tournament.team_mode = 0)
					LEFT JOIN xf_team AS team ON
						(team.team_id = tournament.winner_id AND tournament.team_mode = 1)';
			}		
        }        
        
        /* Always join bracket */
        $selectFields   .= ', bracket.bracket';
        $joinTables     .= '
					LEFT JOIN xfa_tourn_bracket AS bracket ON
						(bracket.tournament_id = tournament.tournament_id)';

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}	
	
	public function prepareTournament($tournament)
	{
    	$theTime = XenForo_Application::$time;

    	if (($tournament['end_date'] != 0) && ($theTime >= $tournament['end_date']))
    	{
        	$tournament['endRegistration'] = 1;
    	}
    	
        /* Censor string in title */
		if (isset($tournament['tournament_title']))
		{
			$tournament['tournament_title'] = XenForo_Helper_String::censorString($tournament['document_title']);
		}
		else
		{
			$tournament['title'] = XenForo_Helper_String::censorString($tournament['title']);
		}
		$tournament['isCensored'] = true;    
		
		if ($tournament['end_date'] > 0)
		{
		    $tournament['end_date_formatted'] = XenForo_Locale::getFormattedDate($tournament['end_date'],'Y/m/d H:i');	
        }
        
    	$visitor = XenForo_Visitor::getInstance();
    	
    	if ($visitor['user_id'])
    	{
        	$tournament['isRegistered'] = $this->_getParticipantModel()->getParticipantByTournamentAndUserId($tournament['tournament_id'], $visitor['user_id']);
    	}
    	else
    	{
        	$tournament['isRegistered'] = 0;
    	}
    	
    	if (isset($tournament['wusername']))
    	{
			if ($tournament['team_mode'] == 0) {
				$tournament['winner'] = array('user_id' => $tournament['user_id'], 'username' => $tournament['wusername']);
			}
			if ($tournament['team_mode'] == 1){
				$tournament['winner'] = array('team_id' => $tournament['wid'], 'title' => $tournament['wusername'], 'username' => $tournament['wusername']);
			}
    	}
    	
    	return $tournament;
	}
	
	public function prepareTournaments($tournaments)
	{
    	foreach($tournaments AS &$tournament)
    	{
            $tournament = $this->prepareTournament($tournament);	
        }
        
        return $tournaments;
	}
 
    public function canView()
    {
        $visitor = XenForo_Visitor::getInstance();
        
        return $visitor->hasPermission('xfa_tourn', 'canView');
    }
 
    public function canAdd()
    {
        $visitor = XenForo_Visitor::getInstance();
        
        return $visitor->hasPermission('xfa_tourn', 'canCreate');
    }
 
    public function canAddPrivate()
    {
        $visitor = XenForo_Visitor::getInstance();
        
        return $visitor->hasPermission('xfa_tourn', 'canCreatePrivate');
    }
 
    public function canAddUser()
    {
        $visitor = XenForo_Visitor::getInstance();
        
        return $visitor->hasPermission('xfa_tourn', 'canAddUser');
    }
 
    public function canInvite()
    {
        $visitor = XenForo_Visitor::getInstance();
        
        return $visitor->hasPermission('xfa_tourn', 'canInvite');
    }
 
    public function canRegister($tournament, $category)
    {
        $visitor = XenForo_Visitor::getInstance();
        
        /* End registration reached or user already registered => false */
        if (isset($tournament['endRegistration']) || $tournament['isRegistered'] === true)
        {
            return false;
        }
        
        /* User count reached */
        if ($tournament['user_count'] >= $tournament['slots'])
        {
            return false;
        }

        /* Check permission */        
        return $visitor->hasPermission('xfa_tourn', 'canRegister');
    }
 
    public function canUnregister($tournament, $category)
    {
        $visitor = XenForo_Visitor::getInstance();

        return $visitor->hasPermission('xfa_tourn', 'canRegister') && empty($tournament['bracket']) && $tournament['isRegistered'];
    }
    
	public function canEdit(array $tournament, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

        /* No user, no permission */
		if (!$viewingUser['user_id'])
		{
			return false;
		}
		
        /* Check if can edit any */
		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'xfa_tourn', 'canEditByAnyone'))
		{
			return true;
		}
		
        /* Check if can edit self and owner */
		return (
			$tournament['user_id'] == $viewingUser['user_id']
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'xfa_tourn', 'canEditBySelf')
			&& !isset($tournament['endRegistration'])
		);
	}    
    
	public function canDelete(array $tournament, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

        /* No user, no permission */
		if (!$viewingUser['user_id'])
		{
			return false;
		}
		
        /* Check if can edit any */
		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'xfa_tourn', 'canDeleteByAnyone'))
		{
			return true;
		}
		
        /* Check if can edit self and owner */
		return (
			$tournament['user_id'] == $viewingUser['user_id']
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'xfa_tourn', 'canDeleteBySelf')
		);
	}  
    
	public function canManage(array $tournament, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

        /* No user, no permission */
		if (!$viewingUser['user_id'])
		{
			return false;
		}
		
        /* Check if can edit any */
		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'xfa_tourn', 'canEditByAnyone'))
		{
			return true;
		}
		
        /* Check if can edit self and owner */
		return (
			$tournament['user_id'] == $viewingUser['user_id']
		);
	}    

    public function generateBracket($tournament)
    {
        /* Get and shuffle participants */
		$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);
    	$participants   = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournament['tournament_id']), array('join' => $fetchOption));
        shuffle($participants);
        $nbParticipants = count($participants);    
        
        /* Check we have enough users, if not do nothing */
        if ($nbParticipants < 2)
        {
            return;
        }
    
        /* Prepare the bracket */
        switch ($tournament['type'])
        {
            case 'single_el':
            case 'double_el':
                /* Check we have enough users */
                if ($tournament['type'] == 'single_el')
                {
                    if ($nbParticipants < 2)
                    {
                        return;
                    }        
                }
                else
                {
                    if ($nbParticipants < 4)
                    {
                        return;
                    }         
                }
                
                /* Check number of users is power of 2 */
                if (($nbParticipants & ($nbParticipants - 1)) != 0)
                {
                    return;
                }
                
                /* Group participants by 2 */
                $teams  = array();
                $i      = 0;
                foreach($participants AS $participant)
                {
                    $teams[(int)$i / 2][$i % 2] = $participant;
                    $i++;
                }
                
                /* Construct the bracket data */
                $bracketData = '{"teams":[';
                $i           = 0;
                foreach ($teams AS $team)
                {
                    if ($i > 0)
                    {
                        $bracketData .= ',';
                    }
                    
                    $bracketData .= '[' . $team[0]['participant_id'] . ',' . $team[1]['participant_id'] . ']';
                    
                    $i++;
                }
                
                if ($tournament['type'] == 'double_el')
                {
                    $bracketData .= '],"results": [[[[]]], [], []]}';
                }
                else
                {
                    $bracketData .= '],"results": [[]]}';
                }
                break;
            case 'round_robin':
            default:
            	$bracketData = '{"teams":[';
            	$i = 0;
            	foreach($participants AS $participant)
            	{
            	    $teams[]    = '{"id":"' . $i . '","pid":"' . ($participant['participant_id']) . '","name":"' . $participant['username'] . '"}';
                	$players[]  = $i++;
            	}
            	$bracketData .= implode(',', $teams);
                $bracketData .= '],';
                $bracketData .= '"matches":' . json_encode($this->roundRobin($players));
                $bracketData .= '}';             
                break;
        }

        /* Save bracket into the database */
        $this->writeBracket($tournament['tournament_id'], $bracketData);
    }
   
    public function roundRobin(array $participants)
    {
        /* Add a fake team in case of non even number of teams */
        if (count($participants)%2 != 0)
        {
            array_push($participants, -1);
        }

        /* Generate the round robin */
        $away = array_splice($participants, (count($participants)/2));
        $home = $participants;
        
        for ($i = 0; $i < count($home) + count($away) - 1; $i++)
        {
            for ($j = 0; $j < count($home); $j++)
            {
                $round[$i][$j]['home'] = "$home[$j]";
                $round[$i][$j]['away'] = "$away[$j]";
            }
            
            if(count($home) + count($away) - 1 > 2)
            {
                $s      = array_splice($home, 1, 1);
                $slice  = array_shift($s);
                
                array_unshift($away, $slice);
                array_push($home, array_pop($away));
            }
        }
        
        /* Go through to cleanup table */
        $result     = array();
        for ($i = 0; $i < count($round); $i++)
        {
            for($j = 0; $j < count($round[$i]); $j++)
            {
                if (($round[$i][$j]['home'] != -1) && ($round[$i][$j]['away'] != -1))
                {
                    $result[] = array(
                        'a'     => array('team' => $round[$i][$j]['home'], 'score' => null),
                        'b'     => array('team' => $round[$i][$j]['away'], 'score' => null),
                        'round' => $i + 1,
                    );
                }
            }
        }
        
        return $result;
    }
    
    public function writeBracket($tournamentId, $inputBracket)
    {
        /* Get participants */
		$tournament = $this->getTournamentByThreadId($tournamentId);
		$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);
		$participants = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournamentId), array('join' => $fetchOption));

        /* Save bracket into the database */
		$this->_getDb()->query("
			INSERT INTO xfa_tourn_bracket
			    (tournament_id,bracket)
            VALUES
                (" . $this->_getDb()->quote($tournamentId) . "," . $this->_getDb()->quote($inputBracket) . ")
        ");    
        
        /* Notify users that tournament is started */     
        $visitor = XenForo_Visitor::getInstance();
          
		foreach ($participants AS $user)
		{    
            if (XenForo_Model_Alert::userReceivesAlert($user, 'xfa_tourn', 'tournament_start'))
            {
    			XenForo_Model_Alert::alert(
    				$user['user_id'],
    				$visitor['user_id'],
    				$visitor['username'],
    				'xfa_tourn',
    				$tournamentId,
    				'tournament_start'
    			);
			}
		} 
    }
    
    public function resetBracket($tournamentId)
    {
        /* Get participants */
		$tournament = $this->getTournamentByThreadId($tournamentId);
		$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);
		$participants = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournamentId), array('join' => $fetchOption));

        /* Save bracket into the database */
		$this->_getDb()->query("
			DELETE FROM xfa_tourn_bracket
			WHERE tournament_id = " . $this->_getDb()->quote($tournamentId) . "
        ");    
        
        /* Notify users that tournament was reset */     
        $visitor = XenForo_Visitor::getInstance();
          
		foreach ($participants AS $user)
		{    
            if (XenForo_Model_Alert::userReceivesAlert($user, 'xfa_tourn', 'tournament_unvalidate'))
            {        		    		
    			XenForo_Model_Alert::alert(
    				$user['user_id'],
    				$visitor['user_id'],
    				$visitor['username'],
    				'xfa_tourn',
    				$tournamentId,
    				'tournament_unvalidate'
    			);
            }
		} 
    }    
    
    public function updateTournament($tournament, $inputBracket)
    {
        $winner_id          = 0;
        $winner_username    = "";
        
        /* We need to check if the tournament has come to an end or if the winner has changed to store it */
        switch($tournament['type'])
        {
            case 'single_el':
                /* Check if last match is set */
                if (isset($inputBracket['results']) && isset($inputBracket['results'][0]))
                {
                    $nbRounds = count($inputBracket['results'][0]);
                    
                    if ($inputBracket['results'][0][$nbRounds - 1][0][0] != "" && $inputBracket['results'][0][$nbRounds - 1][0][1] != "")
                    {
                        /* Gotta go through the array to find the winner id */
                        $idxCheck = 0;
                        for($i = 1; $i < $nbRounds; $i++)
                        {
                            /* Winner is 0 */
                            if ($inputBracket['results'][0][($nbRounds - $i)][$idxCheck][0] > $inputBracket['results'][0][($nbRounds - $i)][$idxCheck][1])
                            {
                                $idxCheck = $idxCheck * 2;
                            }
                            /* Winner is 1 */
                            else
                            {
                                $idxCheck = $idxCheck * 2 + 1;
                            }
                        } 
                        
                        /* Save the winner */
						$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);
                        $participants   = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournament['tournament_id']), array('join' => $fetchOption));
						XenForo_Error::logError(json_encode($participants));
                        /* Winner is 0 */
                        if ($inputBracket['results'][0][($nbRounds - $i)][$idxCheck][0] > $inputBracket['results'][0][($nbRounds - $i)][$idxCheck][1])
                        {
							XenForo_Error::logError(json_encode($inputBracket['teams'][$idxCheck][0]));
                            $winner_id          = $participants[$inputBracket['teams'][$idxCheck][0]]['user_id'];
                            $winner_username    = $participants[$inputBracket['teams'][$idxCheck][0]]['username'];
                        }
                        /* Winner is 1 */
                        else
                        {
							XenForo_Error::logError(json_encode($inputBracket['teams'][$idxCheck][1]));
                            $winner_id          = $participants[$inputBracket['teams'][$idxCheck][1]]['user_id'];
                            $winner_username    = $participants[$inputBracket['teams'][$idxCheck][1]]['username'];
                        }
                    }
                }
                break;
            case 'double_el':
                /* Check if last match is set */
                if (isset($inputBracket['results'][2]) && $inputBracket['results'][2][0][0][0] != "" && $inputBracket['results'][2][0][0][1] != "")
                {
                    $nbRounds = count($inputBracket['results'][0]);
                    
                    /* Check if last match is set */
                    if (isset($inputBracket['results'][0]) && $inputBracket['results'][0][$nbRounds - 1][0][0] != "" && $inputBracket['results'][0][$nbRounds - 1][0][1] != "")
                    {
                        /* Gotta go through the array to find the winner id */
                        $idxCheck = 0;
                        for($i = 1; $i < $nbRounds; $i++)
                        {
                            /* Winner is 0 */
                            if ($inputBracket['results'][0][($nbRounds - $i)][$idxCheck][0] > $inputBracket['results'][0][($nbRounds - $i)][$idxCheck][1])
                            {
                                $idxCheck = $idxCheck * 2;
                            }
                            /* Winner is 1 */
                            else
                            {
                                $idxCheck = $idxCheck * 2 + 1;
                            }
                        } 
                        
                        /* Save the winner */
						$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);
                        $participants   = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournament['tournament_id']), array('join' => $fetchOption));
                    
                        /* Winner is 0 */
                        if ($inputBracket['results'][0][($nbRounds - $i)][$idxCheck][0] > $inputBracket['results'][0][($nbRounds - $i)][$idxCheck][1])
                        {
                            $winner_id          = $participants[$inputBracket['teams'][$idxCheck][0]]['user_id'];
                            $winner_username    = $participants[$inputBracket['teams'][$idxCheck][0]]['username'];
                        }
                        /* Winner is 1 */
                        else
                        {
                            $winner_id          = $participants[$inputBracket['teams'][$idxCheck][1]]['user_id'];
                            $winner_username    = $participants[$inputBracket['teams'][$idxCheck][1]]['username'];
                        }       
                    }
                }
                break;
            case 'round_robin':
                /* Go through the matches and see if we are finished */
                $allMatchesFinished = 1;
                foreach($inputBracket['matches'] AS $match)
                {
                    if ($match['a']['score'] == "" || $match['b']['score'] == "")
                    {
                        $allMatchesFinished = 0;
                    }
                }
                
                /* All matches finished ? */
                if ($allMatchesFinished)
                {
                    /* Gotta sort that $team array after randomizing it in order to randomize the winner in case of multiple ones */
                    $results = $inputBracket['teams'];
                    shuffle($results);
                    usort($results, "self::sortRoundRobinTournamentTeams");

					$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);

                    /* Save the winner */
                    $participants   = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournament['tournament_id']), array('join' => $fetchOption));
                    
                    $winner_id          = $participants[$results[0]['pid']]['user_id'];
                    $winner_username    = $participants[$results[0]['pid']]['username'];
                }
                break;
        }
        
        
        /* Update the tournament */
		$this->_getDb()->query("
                UPDATE xfa_tourn_bracket
                SET bracket = " . $this->_getDb()->quote(json_encode($inputBracket)) . "
                WHERE tournament_id = " . $this->_getDb()->quote($tournament['tournament_id']) . "
        ");
        
        /* Notify users that tournament was started */     
        $visitor = XenForo_Visitor::getInstance();

		$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);

		$participants = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournament['tournament_id']), array('join' => $fetchOption));
          
		foreach ($participants AS $user)
		{
            if (XenForo_Model_Alert::userReceivesAlert($user, 'xfa_tourn', 'scores_update'))    		
            {
    			XenForo_Model_Alert::alert(
    				$user['user_id'],
    				$visitor['user_id'],
    				$visitor['username'],
    				'xfa_tourn',
    				$tournament['tournament_id'],
    				'scores_update'
    			);
			}
		}         
        
        if ($winner_id != 0)
        {
    		$dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Tournament');
    
    		$visitor = XenForo_Visitor::getInstance();
    		$dw->setExistingData($tournament['tournament_id']);
    		$dw->set('winner_id', $winner_id);
    		$dw->set('winner_username', $winner_username);
    		$dw->save(); 
        }
    }
    
    private static function sortRoundRobinTournamentTeams($a, $b)
    {
        /* Check by points first */
        if ($a['p'] < $b['p']) return 1;
        if ($a['p'] > $b['p']) return -1;
        
        /* Check by wins */
        if ($a['w'] < $b['w']) return 1;
        if ($a['w'] > $b['w']) return -1;
        
        /* Check by ties */
        if ($a['t'] < $b['t']) return 1;
        if ($a['t'] > $b['t']) return -1;
        
        /* Check by losses (inverse logic) */
        if ($a['l'] > $b['l']) return 1;
        if ($a['l'] < $b['l']) return -1;
        
        /* Check by ratio */
        if ($a['r'] == $b['r']) return 0;
        
        return ($a['r'] < $b['r'] ? 1 : -1);
    }
    
    public function getTopWinners($nbWinners,$categoryId)
    {
        $fetchOptions = array('page' => 1, 'perPage' => $nbWinners);
        
		$limitOptions   = $this->prepareLimitFetchOptions($fetchOptions);
		if (isset($categoryId)) {

			$query = sprintf('
				SELECT user.*, user_profile.*, (SELECT COUNT(1)
		   											  FROM xfa_tourn_tournament
                   									  WHERE  winner_id = user.user_id and team_mode=0 and tournament_category_id = %1$d) as xfa_tourn_wins
				FROM xf_user AS user
				LEFT JOIN xf_user_profile AS user_profile ON
					(user_profile.user_id = user.user_id)
				WHERE user.user_id in ( SELECT winner_id
										         FROM xfa_tourn_tournament
                   								 WHERE  winner_id = user.user_id and team_mode= 0 and tournament_category_id = %1$d)
			', $categoryId);
		}
		else {
			$query = '
				SELECT user.*, user_profile.*, (SELECT COUNT(1)
		   											  FROM xfa_tourn_tournament
                   									  WHERE  winner_id = user.user_id and team_mode=0) as xfa_tourn_wins
				FROM xf_user AS user
				LEFT JOIN xf_user_profile AS user_profile ON
					(user_profile.user_id = user.user_id)
				WHERE user.user_id in ( SELECT winner_id
										         FROM xfa_tourn_tournament
                   								 WHERE  winner_id = user.user_id and team_mode= 0)
			';
		}
		return $this->fetchAllKeyed($this->limitQueryResults(
			$query, $limitOptions['limit'], $limitOptions['offset']
		), 'user_id');
	}

	public function getTopTeamWinners($nbWinners, $categoryId)
	{
		$fetchOptions = array('page' => 1, 'perPage' => $nbWinners);

		$limitOptions   = $this->prepareLimitFetchOptions($fetchOptions);
		$query = null;
		if (isset($categoryId)) {

			$query = sprintf('SELECT *, (SELECT COUNT(1)
		   											  FROM xfa_tourn_tournament
                   									  WHERE  winner_id = team.team_id and team_mode=1 and tournament_category_id = %1$d) as count
							  FROM xf_team as team
							  WHERE team_id in ( SELECT winner_id
										         FROM xfa_tourn_tournament
                   								 WHERE  winner_id = team.team_id and team_mode=1 and tournament_category_id = %1$d)', $categoryId);
		}
		else {
			$query ='SELECT *, (SELECT COUNT(1)
		   											  FROM xfa_tourn_tournament
                   									  WHERE  winner_id = team.team_id and team_mode=1 ) as count
							  FROM xf_team as team
							  WHERE team_id in ( SELECT winner_id
										         FROM xfa_tourn_tournament
                   								 WHERE  winner_id = team.team_id and team_mode=1 )';
		}
		return $this->fetchAllKeyed($this->limitQueryResults(
			$query, $limitOptions['limit'], $limitOptions['offset']
		), 'team_id');
	}
	
	public function getParticipantsAndBracket($tournament)
	{
    	/* Get participants */
		$fetchOption = ($tournament['team_mode'] ==1 ? XFA_Tournament_Model_Participant::FETCH_TEAM : XFA_Tournament_Model_Participant::FETCH_USER);
    	$participants = $this->_getParticipantModel()->getParticipants(array('tournament_id' => $tournament['tournament_id']), array('join' =>$fetchOption ));
    	
    	/* Bracket not available, create a fake one */
    	if ($tournament['type'] == 'round_robin')
    	{
        	if (!$tournament['bracket'])
        	{
            	$bracketData = '{ teams: [';
            	for($i = 0; $i < $tournament['slots']; $i ++)
            	{
                	$players[] = $i;
            	    $bracketData .= '{id:"' . ($i + 1) . '", name: "' . (new XenForo_Phrase('xfa_tourn_player_x', array('player' => $i)))->render() . '"},';
                }
                $bracketData .= '],';
                $bracketData .= 'matches:' . json_encode($this->roundRobin($players));
                $bracketData .= '}';
        	}
        	else
        	{
            	$bracketData = json_decode($tournament['bracket'], true);
            	
            	/* Need to replace the id by the actual name */
            	foreach($bracketData['teams'] AS &$team)
            	{
                	$team['name'] = $participants[$team['pid']]['username'];
            	}
            	
                $bracketData = json_encode($bracketData);
        	}
    	}
    	else
    	{
        	if (!$tournament['bracket'])
        	{
            	if ($tournament['type'] == 'double_el')
            	{
            	    $bracketData = array('teams' => array(), 'results' => array(array(array(array())),array(),array()));
                }
                else
                {
                    $bracketData = array('teams' => array(), 'results' => array());    
                }
                
            	for($i = 0; $i < $tournament['slots']; $i += 2)
            	{
            	    $bracketData['teams'][$i/2][0] = "";
            	    $bracketData['teams'][$i/2][1] = "";
                }
        	}
        	else
        	{
            	$bracketData = json_decode($tournament['bracket'], true);

            	/* Need to replace the id by the actual name */
            	foreach($bracketData['teams'] AS &$team)
            	{
                	$team[0] = $participants[$team[0]]['username'];
                	$team[1] = $participants[$team[1]]['username'];
            	}
        	}
        	
        	$bracketData = json_encode($bracketData);
        }
    	
    	return array(
        	$participants,
        	$bracketData
    	);
	}
	
	public function getUsersWithNewAlert()
	{
		return $this->fetchAllKeyed('
			SELECT xf_user.*
			FROM xf_user
			WHERE xfa_tourn_new_alert=1
		', 'user_id');    	
	}
			
	protected function _getParticipantModel()
	{
        return $this->getModelFromCache('XFA_Tournament_Model_Participant');
	}   
}