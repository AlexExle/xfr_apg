<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Model_Participant extends XenForo_Model
{
	const FETCH_USER        = 0x01;
	const FETCH_TEAM        = 0x02;
	
	public function getParticipantById($participantId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareParticipantFetchOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT participant.*
				' . $joinOptions['selectFields'] . '
			FROM xfa_tourn_participant AS participant
				' . $joinOptions['joinTables'] . '
			WHERE participant.participant_id = ?
		', $participantId);
	}
	
	public function getParticipantByIds($participantIds, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareParticipantFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT participant.*
				' . $joinOptions['selectFields'] . '
			FROM xfa_tourn_participant AS participant
				' . $joinOptions['joinTables'] . '
			WHERE participant.participant_id IN (' . $this->_getDb()->quote($participantIds) . ')
		', 'participant_id');
	}
	
	public function getParticipantByTournamentAndUserId($tournamentId, $userId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareParticipantFetchOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT participant.*
				' . $joinOptions['selectFields'] . '
			FROM xfa_tourn_participant AS participant
				' . $joinOptions['joinTables'] . '
			WHERE participant.tournament_id = ? AND participant.user_id = ?
		', array($tournamentId, $userId)); 	
	}


	public function getTeamParticipantsByTournamentAndUserId($tournamentId, $userId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareParticipantFetchOptions($fetchOptions);

		return $this->_getDb()->fetchAll('
			SELECT participant.*
				' . $joinOptions['selectFields'] . '
			FROM xfa_tourn_participant AS participant
				' . $joinOptions['joinTables'] . '
			WHERE participant.tournament_id = ? AND participant.user_id in 
				(SELECT team_id FROM xf_team as t WHERE EXISTS
								( SELECT team_id FROM xf_team_member WHERE xf_team_member.team_id = t.team_id and xf_team_member.user_id = ? ))
		', array($tournamentId, $userId));
	}


	public function getParticipants(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause    = $this->prepareParticipantConditions($conditions, $fetchOptions);

		$joinOptions    = $this->prepareParticipantFetchOptions($fetchOptions);
		$limitOptions   = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT participant.*
					' . $joinOptions['selectFields'] . '
				FROM xfa_tourn_participant AS participant
				' . $joinOptions['joinTables'] . '
				WHERE ' . $whereClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'participant_id');
	}
 
	public function prepareParticipantConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();
		
		if (!empty($conditions['tournament_id']))
		{
			if (is_array($conditions['tournament_id']))
			{
				$sqlConditions[] = 'participant.tournament_id IN (' . $db->quote($conditions['tournament_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'participant.tournament_id = ' . $db->quote($conditions['tournament_id']);
			}
		}	
		
		return $this->getConditionsForClause($sqlConditions);
	}	

	public function prepareParticipantFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$db = $this->_getDb();

		if (!empty($fetchOptions['join']))
		{			
			if ($fetchOptions['join'] & self::FETCH_USER)
			{
				$selectFields .= ',
					user.*, user_profile.*, IF(user.username IS NULL, participant.username, user.username) AS username';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = participant.user_id)
					LEFT JOIN xf_user_profile AS user_profile ON
						(user_profile.user_id = participant.user_id)';
			}
			if ($fetchOptions['join'] & self::FETCH_TEAM)
			{
				$selectFields .= ',
					team.team_id as team_id, team.title as title, team.team_avatar_date, team.team_category_id, team.team_date, team.last_updated,  IF(team.title IS NULL, participant.username, team.title) AS username';
				$joinTables .= '
					LEFT JOIN xf_team AS team ON
						(team.team_id = participant.user_id)
					';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}	
	
	public function removeTournamentParticipants($tournamentId, $participants)
	{
    	foreach($participants AS $participant)
    	{
            $dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Participant');
            $dw->setExistingData($participant);
            $dw->delete();
        }
	}
}