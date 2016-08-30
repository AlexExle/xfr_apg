<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_DataWriter_Participant extends XenForo_DataWriter
{       
    protected function _getFields() {
		return array(
			'xfa_tourn_participant' => array(
				'participant_id'             => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'tournament_id'    => array('type' => self::TYPE_UINT, 'default' => 0),
				'user_id'                   => array('type' => self::TYPE_UINT, 'required' => true),
				'username'                  => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 50,
					'requiredError' => 'please_enter_valid_name'
				)
			)
		);
    }    
    
    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'participant_id'))
        {
            return false;
        }
     
        return array('xfa_tourn_participant' => $this->_getParticipantModel()->getParticipantById($id));
    }    
    
    protected function _getUpdateCondition($tableName)
    {
        return 'participant_id = ' . $this->_db->quote($this->getExisting('participant_id'));
    }
        
    protected function _getParticipantModel()
    {
        return $this->getModelFromCache('XFA_Tournament_Model_Participant');
	}   
	
	protected function _postSave()
	{	
    	if ($this->isInsert() && $tournDw = $this->_getTournamentDwForUpdate())
    	{
            $tournDw->updateUserCount(1);
            $tournDw->save(); 	
        }
    }	
    
	protected function _postDelete()
	{	
    	if($tournDw = $this->_getTournamentDwForUpdate())
    	{
            $tournDw->updateUserCount(-1);
            $tournDw->save(); 	
        }
    }	
    
	protected function _getTournamentDwForUpdate()
	{
		$dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Tournament', XenForo_DataWriter::ERROR_SILENT);
		if ($dw->setExistingData($this->get('tournament_id')))
		{
			return $dw;
		}
		else
		{
			return false;
		}
	}    
}