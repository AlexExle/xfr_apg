<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_DataWriter_Category extends XenForo_DataWriter
{
    protected function _getFields() {
		return array(
			'xfa_tourn_category' => array(
				'tournament_category_id'    => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'category_title'            => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 100,
					'requiredError' => 'please_enter_valid_title'
				),
				'category_description'      => array('type' => self::TYPE_STRING, 'default' => ''),
				'display_order'             => array('type' => self::TYPE_UINT, 'default' => 1),
				'tournament_count'          => array('type' => self::TYPE_UINT_FORCED, 'default' => 0),
				'last_update'               => array('type' => self::TYPE_UINT, 'default' => 0),
				'last_tournament_title'     => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 100),
				'last_tournament_id'        => array('type' => self::TYPE_UINT, 'default' => 0),
				'thread_node_id'        => array('type' => self::TYPE_UINT, 'default' => 0),
				'thread_prefix_id'      => array('type' => self::TYPE_UINT, 'default' => 0)
			)
		);
    }
    
    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'tournament_category_id'))
        {
            return false;
        }
     
        return array('xfa_tourn_category' => $this->_getCategoryModel()->getCategoryById($id));
    }    
    
    protected function _getUpdateCondition($tableName)
    {
        return 'tournament_category_id = ' . $this->_db->quote($this->getExisting('tournament_category_id'));
    }
        
    protected function _getCategoryModel()
    {
        return $this->getModelFromCache('XFA_Tournament_Model_Category');
	}   
	
	
	public function tournamentUpdate(XFA_Tournament_DataWriter_Tournament $tournament)
	{
        /* Category change */
		if ($tournament->isUpdate() && $tournament->isChanged('tournament_category_id'))
		{
			$this->updateTournamentCount(1);

			$oldCat = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Category', XenForo_DataWriter::ERROR_SILENT);
			if ($oldCat->setExistingData($tournament->getExisting('tournament_category_id')))
			{
				$oldCat->tournamentRemoved($tournament);
				$oldCat->save();
			}
		}

        /* Need to update tournament last update ? */
		if ($tournament->get('last_update') >= $this->get('last_update'))
		{
			$this->set('last_update', $tournament->get('last_update'));
			$this->set('last_tournament_title', $tournament->get('title'));
			$this->set('last_tournament_id', $tournament->get('tournament_id'));
		}
	}

	public function tournamentRemoved(XFA_Tournament_DataWriter_Tournament $tournament)
	{
		$this->updateTournamentCount(-1);

		if ($this->get('last_tournament_id') == $tournament->get('tournament_id'))
		{
			$this->updateLastUpdate();
		}
	}	
	
	public function updateLastUpdate()
	{
		$tournament = $this->_db->fetchRow($this->_db->limit("
				SELECT *
				FROM xfa_tourn_tournament
				WHERE tournament_category_id = ?
				ORDER BY last_update DESC
			", 1
		), $this->get('tournament_category_id'));
		if (!$tournament)
		{
			$this->set('tournament_count', 0);
			$this->set('last_update', 0);
			$this->set('last_tournament_title', '');
			$this->set('last_tournament_id', 0);
		}
		else
		{
			$this->set('last_update', $tournament['last_update']);
			$this->set('last_tournament_title', $tournament['title']);
			$this->set('last_tournament_id', $tournament['tournament_id']);
		}
	}

	public function updateTournamentCount($adjust = null)
	{
		if ($adjust === null)
		{
			$this->set('tournament_count', $this->_db->fetchOne("
				SELECT COUNT(*)
				FROM xfa_tourn_tournament
				WHERE tournament_category_id = ?
			", $this->get('tournament_category_id')));
		}
		else
		{
			$this->set('tournament_count', $this->get('tournament_count') + $adjust);
		}
	}

	public function rebuildCounters()
	{
		$this->updateLastUpdate();
		$this->updateTournamentCount();
	}		
}