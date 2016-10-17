<?php

class Ice_EmbedStreams_DataWriter_Stream extends XenForo_DataWriter
{
	
	protected function _getFields(){
		return array(
			'xf_ice_livestreams' => array(
				'stream_id' => array('type'=> self::TYPE_UINT, 'autoIncrement' => true),
				'username' => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 40, 'requiredError' => 'Please enter valid XF username'),
				'stream_username' => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 40, 'requiredError' => 'Please enter valid stream site username / id'),
				'stream_type' => array('type'=>self::TYPE_STRING, 'required' => true, 'requiredError' => 'Invalid stream type'),
				'live' => array('type'=>self::TYPE_STRING, 'default' => '0', 'required' => false),
				'display_order' => array('type' => self::TYPE_UINT, 'required' => true, 'requiredError' => 'Please enter valid display order'),			
			)	
		);
	}
	
	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'stream_id'))
		{
			return false;
		}
	
		return array('xf_ice_livestreams' => $this->getModelFromCache('Ice_EmbedStreams_Model_Streams')->getStream($id));
	
	}
	
	protected function _getUpdateCondition($tableName)
	{
		return 'stream_id = ' . $this->_db->quote($this->getExisting('stream_id'));
	}
	
}