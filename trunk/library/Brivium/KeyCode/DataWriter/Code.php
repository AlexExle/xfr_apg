<?php

class Brivium_KeyCode_DataWriter_Code extends XenForo_DataWriter
{

	/**
	 * Title of the phrase that will be created when a call to set the
	 * existing data fails (when the data doesn't exist).
	 *
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'BRKC_requested_code_not_found';

	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'xf_brivium_keycode_code' 	=> array(
				'code_id'       => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'code'     		=> array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 200,
										'verification' => array('$this', '_verifyCode'), 'requiredError' => 'BRKC_please_enter_valid_code'
				),
				'code_category_id'   => array('type' => self::TYPE_UINT, 'default' => 0),
				'create_date'   => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'user_id'    	=> array('type' => self::TYPE_UINT,  'default' => 0),
				'date_receive'  => array('type' => self::TYPE_UINT, 'default' => 0),
				'code_state'    => array('type' => self::TYPE_STRING, 'maxLength' => 30,'default' => 'show'),
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
		if (!$id = $this->_getExistingPrimaryKey($data, 'code_id'))
		{
			return false;
		}
		return array('xf_brivium_keycode_code' => $this->_getCodeModel()->getCodeById($id));
	}

	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'code_id = ' . $this->_db->quote($this->getExisting('code_id'));
	}

	
	/**
	 * Verifies that the code does not already exist.
	 *
	 * @param $optionId
	 *
	 * @return boolean
	 */
	protected function _verifyCode(&$code)
	{

		if (!$this->getExisting('code_id'))
		{
			$codes = $this->_getCodeModel()->getCodeByCode($code);
			if ($codes && ($codes['code_category_id'] == $this->get('code_category_id')))
			{
				$this->error(new XenForo_Phrase('BRKC_code_must_be_unique'), 'code_id');
				return false;
			}
		}

		return true;
	}
	
	/**
	 * Pre-save handling.
	 */
	protected function _preSave()
	{
	}

	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{

	}

	/**
	 * Post-delete handling.
	 */
	protected function _postDelete()
	{
	}

	/**
	 * Load action model from cache.
	 *
	 * @return Brivium_KeyCode_Model_Code
	 */
	protected function _getCodeModel()
	{
		return $this->getModelFromCache('Brivium_KeyCode_Model_Code');
	}
}