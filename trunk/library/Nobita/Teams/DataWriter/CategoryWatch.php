<?php

class Nobita_Teams_DataWriter_CategoryWatch extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_team_category_watch' => array(
				'user_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'team_category_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'notify_on' => array('type' => self::TYPE_STRING, 'allowedValues' => array('', 'team'), 'default' => 'team'),
				'send_alert' => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'send_email' => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'include_children' => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!is_array($data))
		{
			return false;
		}
		else if (isset($data['user_id'], $data['team_category_id']))
		{
			$userId = $data['user_id'];
			$nodeId = $data['team_category_id'];
		}
		else if (isset($data[0], $data[1]))
		{
			$userId = $data[0];
			$nodeId = $data[1];
		}
		else
		{
			return false;
		}

		return array('xf_team_category_watch' => $this->_getCategoryWatchModel()->getUserCategoryWatchByCategoryId($userId, $nodeId));
	}

	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'user_id = ' . $this->_db->quote($this->getExisting('user_id'))
			. ' AND team_category_id = ' . $this->_db->quote($this->getExisting('team_category_id'));
	}

	/**
	 * @return Nobita_Teams_Model_CategoryWatch
	 */
	protected function _getCategoryWatchModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_CategoryWatch');
	}
}
