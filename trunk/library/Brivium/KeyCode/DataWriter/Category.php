<?php

class Brivium_KeyCode_DataWriter_Category extends XenForo_DataWriter
{

	/**
	 * Title of the phrase that will be created when a call to set the
	 * existing data fails (when the data doesn't exist).
	 *
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'BRKC_requested_category_not_found';

	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'xf_brivium_keycode_category' 	=> array(
				'code_category_id'   	=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'title'     			=> array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 200),
				'price'   				=> array('type' => self::TYPE_FLOAT, 'default' => 0),
				'currency_id'   		=> array('type' => self::TYPE_UINT, 'default' => 0),
				'max_code_options'  	=> array('type' => self::TYPE_UNKNOWN, 'verification' => array('$this', '_verifyMaxCodeOptions')),
				'user_groups'   		=> array('type' => self::TYPE_SERIALIZED,   'default' => ''),
				'active'   				=> array('type' => self::TYPE_BOOLEAN, 	'default' => 1),
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
		if (!$id = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}
		return array('xf_brivium_keycode_category' => $this->_getCodeModel()->getCategoryById($id));
	}

	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'code_category_id = ' . $this->_db->quote($this->getExisting('code_category_id'));
	}

	protected function _verifyMaxCodeOptions(&$options)
	{
		if ($options === null)
		{
			$options = '';
			return true;
		}

		return XenForo_DataWriter_Helper_Denormalization::verifySerialized($options, $this, 'max_code_options');
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
		$categoryId = $this->get('code_category_id');
		$this->_db->delete('xf_brivium_keycode_code', 'code_category_id = ' . $this->_db->quote($categoryId));
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