<?php

class Brivium_Credits_DataWriter_Event extends XenForo_DataWriter
{
	/**
	 * Action that represents whether the action cache will be automatically
	 * rebuilt. Defaults to true.
	 *
	 * @var string
	 */
	const OPTION_REBUILD_CACHE = 'rebuildCache';

	/**
	 * Title of the phrase that will be created when a call to set the
	 * existing data fails (when the data doesn't exist).
	 *
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'BRC_requested_event_not_found';

	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'xf_brivium_credits_event' => array(
				'event_id'			=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'action_id'			=> array('type' => self::TYPE_STRING, 'maxLength' => 100, 'required' => true,),
				'currency_id'		=> array('type' => self::TYPE_UINT,   'required' => true,),
				'user_groups'	 	=> array('type' => self::TYPE_UNKNOWN, 'verification' => array('$this', '_verifyUserGroups')),
				'forums'	 		=> array('type' => self::TYPE_UNKNOWN, 'verification' => array('$this', '_verifyForums')),
				'amount'			=> array('type' => self::TYPE_FLOAT,   	'default' => 0),
				'sub_amount'		=> array('type' => self::TYPE_FLOAT,	'default' => 0),
				'multiplier'		=> array('type' => self::TYPE_FLOAT,   	'default' => 0),
				'sub_multiplier'	=> array('type' => self::TYPE_FLOAT,   	'default' => 0),
				'active'			=> array('type' => self::TYPE_BOOLEAN, 	'default' => 1),
				'alert'				=> array('type' => self::TYPE_BOOLEAN, 	'default' => 0),
				'moderate'			=> array('type' => self::TYPE_BOOLEAN, 	'default' => 0),
				'times'				=> array('type' => self::TYPE_UINT,		'default' => 0),
				'max_time'			=> array('type' => self::TYPE_UINT,		'default' => 0),
				'apply_max'			=> array('type' => self::TYPE_UINT,		'default' => 0),
				'extra_min'			=> array('type' => self::TYPE_FLOAT,	'default' => 0),
				'extra_max'			=> array('type' => self::TYPE_FLOAT,	'default' => 0),
				'extra_min_handle'  => array('type' => self::TYPE_UINT,	'default' => 0),
				'target'			=> array('type' => self::TYPE_STRING, 	'allowedValues' => array('user', 'user_action', 'both'), 'default' => 'user'),
				'allow_negative'	=> array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'negative_handle'   => array('type' => self::TYPE_STRING, 'default' => ''),
				'extra_data'		=> array('type' => self::TYPE_SERIALIZED, 'default' => ''),
			)
		);
	}


	/**
	* Gets the actual existing data out of data that was passed in. See parent for explanation.
	*
	* @param mixed
	*
	* @return array|false
	*/
	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'event_id'))
		{
			return false;
		}
		return array('xf_brivium_credits_event' => $this->_getEventModel()->getEventById($id));
	}
	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'event_id = ' . $this->_db->quote($this->getExisting('event_id'));
	}

	/**
	 * Gets the default actions for this data writer.
	 */
	protected function _getDefaultOptions()
	{
		return array(
			self::OPTION_REBUILD_CACHE => true,
		);
	}


	/**
	 * Verification method for forums
	 *
	 * @param string $serializedData
	 */
	protected function _verifyForums(&$serializedData)
	{
		if ($serializedData === null)
		{
			$serializedData = '';
			return true;
		}
		return XenForo_DataWriter_Helper_Denormalization::verifySerialized($serializedData, $this, 'forums');
	}
	/**
	 * Verification method for user_groups
	 *
	 * @param string $serializedData
	 */
	protected function _verifyUserGroups(&$serializedData)
	{
		if ($serializedData === null)
		{
			$serializedData = '';
			return true;
		}
		return XenForo_DataWriter_Helper_Denormalization::verifySerialized($serializedData, $this, 'user_groups');
	}

	/**
	 * Sets the group relationships for this action.
	 *
	 * @param array $relations List of group relations, format: [group id] => display order.
	 */
	public function setRelations(array $relations)
	{
		$this->_relations = $relations;
	}

	/**
	 * Pre-save handling.
	 */
	protected function _preSave()
	{
		$actionId = $this->get('action_id');
		$actionObj = XenForo_Application::get('brcActionHandler');
		$handler = $actionObj->getActionHandler($actionId);
		if(!$handler){
			$this->error(new XenForo_Phrase('BRC_requested_action_not_found'), 'action_id');
		}
		$handler->verifyEvent($this->getMergedData(), $this);
	}

	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		if ($this->getOption(self::OPTION_REBUILD_CACHE))
		{
			$this->_getEventModel()->rebuildEventCache();
		}

	}

	/**
	 * Post-delete handling.
	 */
	protected function _postDelete()
	{
		if ($this->getOption(self::OPTION_REBUILD_CACHE))
		{
			$this->_getEventModel()->rebuildEventCache();
		}
	}

	/**
	 * Load event model from cache.
	 *
	 * @return Brivium_Credits_Model_Event
	 */
	protected function _getEventModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Event');
	}
	/**
	 * Load action model from cache.
	 *
	 * @return Brivium_Credits_Model_Action
	 */
	protected function _getActionModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Action');
	}
	/**
	 * Lazy load the template model object.
	 *
	 * @return  XenForo_Model_Template
	 */
	protected function _getTemplateModel()
	{
		return $this->getModelFromCache('XenForo_Model_Template');
	}

}