<?php

/**
* Data writer for currency.
*
* @package Brivium_Credits
*/
class Brivium_Credits_DataWriter_Currency extends XenForo_DataWriter
{
	/**
	 * Title of the phrase that will be created when a call to set the
	 * existing data fails (when the data doesn't exist).
	 *
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'BRC_requested_currency_not_found';
	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'xf_brivium_credits_currency' => array(
				'currency_id'		=> array('type' => self::TYPE_UINT, 	'autoIncrement' => true),
				'title'				=> array('type' => self::TYPE_STRING, 	'required' => true, 'maxLength' => 100,'requiredError' => 'please_enter_valid_title'),
				'description'		=> array('type' => self::TYPE_STRING, 	'default' => ''),
				'column'			=> array('type' => self::TYPE_STRING, 	'required' => true, 'maxLength' => 100,'verification' 	=> array('$this', '_verifyColumn')),
				'code'				=> array('type' => self::TYPE_STRING,  	'default' => '', 'noTrim'=>true),
				'symbol_left'		=> array('type' => self::TYPE_STRING, 	'default' => '','maxLength' => 50, 'noTrim'=>true),
				'symbol_right'		=> array('type' => self::TYPE_STRING, 	'default' => '','maxLength' => 50, 'noTrim'=>true),
				'decimal_place'		=> array('type' => self::TYPE_UINT, 	'default' => 0),
				'negative_handle'   => array('type' => self::TYPE_STRING, 	'allowedValues' => array('reset', 'hide', 'show'), 'default' => 'show'),
				'user_groups'		=> array('type' => self::TYPE_UNKNOWN, 	'verification' 	=> array('$this', '_verifyUserGroups')),
				'max_time'			=> array('type' => self::TYPE_UINT, 	'default' => 0),
				'earn_max'			=> array('type' => self::TYPE_FLOAT, 	'default' => 0),
				'in_bound'			=> array('type' => self::TYPE_BOOLEAN, 	'default' => 1),
				'out_bound'			=> array('type' => self::TYPE_BOOLEAN, 	'default' => 1),
				'value'				=> array('type' => self::TYPE_FLOAT, 	'default' => 1,'maxLength' => 15),
				'withdraw'			=> array('type' => self::TYPE_BOOLEAN, 	'default' => 0),
				'withdraw_min'		=> array('type' => self::TYPE_FLOAT,   	'default' => 0),
				'withdraw_max'		=> array('type' => self::TYPE_FLOAT,   	'default' => 0),
				'active'			=> array('type' => self::TYPE_BOOLEAN, 	'default' => 1),
				//'image_type'		=> array('type' => self::TYPE_STRING, 	'default' => '', 'maxLength' => 25,),
				'display_order'		=> array('type' => self::TYPE_UINT, 	'default' => 0),
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
		if (!$currencyId = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('xf_brivium_credits_currency' => $this->_getCurrencyModel()->getCurrencyById($currencyId));
	}

	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'currency_id = ' . $this->_db->quote($this->getExisting('currency_id'));
	}


	protected function _verifyUserGroups(&$userGroups)
	{
		if ($userGroups === null)
		{
			$userGroups = '';
			return true;
		}

		return XenForo_DataWriter_Helper_Denormalization::verifySerialized($userGroups, $this, 'user_groups');
	}
	protected function _verifyColumn($column)
	{
		if(!$column){
			$this->error(new XenForo_Phrase('BRC_please_enter_valid_column'), 'column');
			return false;
		}
		if (preg_match('/[^a-zA-Z0-9_]/', $column))
		{
			$this->error(new XenForo_Phrase('BRC_please_enter_an_colum_using_only_alphanumeric'), 'column');
			return false;
		}
		if ($column !== $this->getExisting('column'))
		{
			if ($this->_getCurrencyModel()->getCurrencyByColumn($column))
			{
				$this->error(new XenForo_Phrase('BRC_columns_must_be_unique'), 'column');
				return false;
			}
		}
		if(!$this->_checkIfExist('xf_user',$column)){
			$this->_addColumn('xf_user', $column, " decimal(19,6) NOT NULL DEFAULT '0.000000'");
		}
		return true;
	}
	protected function _checkIfExist($table, $field)
	{
		if ($this->_db->fetchRow('SHOW columns FROM `' . $table . '` WHERE Field = ?', $field)) {
			return true;
		}
		else {
			return false;
		}
	}

	protected function _addColumn($table, $field, $attr)
	{
		if (!$this->_checkIfExist($table, $field)) {
			return $this->_db->query("ALTER TABLE `" . $table . "` ADD `" . $field . "` " . $attr);
		}
	}
	/**
	 * Internal post-save handler
	 */
	protected function _postSave()
	{
		//if($this->isChange('column')){
			//return $this->_db->query("ALTER TABLE  `xf_user` CHANGE  `".$this->getExisting('column')."`  `".$this->get('column')."`");
		//}
		$this->_getCurrencyModel()->rebuildCurrencyCaches();
	}
	/**
	 * Internal pre-delete handler.
	 */
	protected function _preDelete()
	{
		$currencyModel = $this->_getCurrencyModel();
		$currencies = $currencyModel->getAllCurrencies();

		if (sizeof($currencies) <= 1)
		{
			$this->error(new XenForo_Phrase('BRC_it_is_not_possible_to_delete_last_currency'));
		}

		if ($this->get('currency_id') == XenForo_Application::get('options')->BRC_defaultCurrencyId)
		{
			$this->error(new XenForo_Phrase('BRC_it_is_not_possible_to_remove_default_currency'));
		}
	}
	/**
	 * Post-delete behaviors.
	 */
	protected function _postDelete()
	{
		$db = $this->_db;
		$currencyid = $this->get('currency_id');
		$currencyidQuoted = $db->quote($currencyid);

		$db->delete('xf_brivium_credits_event', "currency_id = $currencyidQuoted");
		$db->delete('xf_brivium_credits_transaction', "currency_id = $currencyidQuoted");
		$db->delete('xf_brivium_credits_stats', "currency_id = $currencyidQuoted");

		$this->getModelFromCache('Brivium_Credits_Model_Event')->rebuildEventCache();
		$this->_getCurrencyModel()->rebuildCurrencyCaches();
	}

	/**
	 * Gets the currency model.
	 *
	 * @return Brivium_Credits_Model_Currency
	 */
	protected function _getCurrencyModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Currency');
	}
}