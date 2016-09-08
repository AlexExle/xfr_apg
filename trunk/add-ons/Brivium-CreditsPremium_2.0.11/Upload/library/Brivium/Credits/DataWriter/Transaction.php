<?php

/**
* Data writer for transaction.
*
* @package Brivium_Credits
*/
class Brivium_Credits_DataWriter_Transaction extends XenForo_DataWriter
{
	const OPTION_ALLOW_CREDIT_CHANGE = 'creditChange';
	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'xf_brivium_credits_transaction' => array(
				'transaction_id'	=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'transaction_key'	=> array('type' => self::TYPE_STRING, 'maxLength' => 50, 'default' => ''),
				'action_id'			=> array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 100),
				'event_id'			=> array('type' => self::TYPE_UINT, 'required' => true),
				'currency_id'		=> array('type' => self::TYPE_UINT, 'required' => true),
				'user_id'			=> array('type' => self::TYPE_UINT, 'required' => true),
				'user_action_id'	=> array('type' => self::TYPE_UINT, 'default' => 0),
				'content_id'		=> array('type' => self::TYPE_UINT, 'default' => 0),
				'content_type'		=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 25),
				'owner_id'			=> array('type' => self::TYPE_UINT, 'default' => 0),
				'multiplier'		=> array('type' => self::TYPE_UINT, 'default' => 0),
				'transaction_date'	=> array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'amount'			=> array('type' => self::TYPE_FLOAT, 'default' => 0),
				'negate'			=> array('type' => self::TYPE_UINT, 'default' => 0),
				'message'			=> array('type' => self::TYPE_STRING, 'default' => ''),
				'sensitive_data'	=> array('type' => self::TYPE_STRING, 'default' => ''),
				'moderate'			=> array('type' => self::TYPE_UINT, 'default' => 0),
				'is_revert'			=> array('type' => self::TYPE_UINT, 'default' => 0),
				'transaction_state'	=> array('type' => self::TYPE_STRING, 'maxLength' => 30),
				'extra_data'		=> array('type' => self::TYPE_UNKNOWN, 'verification' => array('$this', '_verifyExtraData')),
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
		if (!$transactionId = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('xf_brivium_credits_transaction' => $this->_getTransactionModel()->getTransactionById($transactionId));
	}

	/**
	 * Verification method for extra data
	 *
	 * @param string $extraData
	 */
	protected function _verifyExtraData(&$extraData)
	{
		if ($extraData === null)
		{
			$extraData = '';
			return true;
		}

		return XenForo_DataWriter_Helper_Denormalization::verifySerialized($extraData, $this, 'extra_data');
	}
	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'transaction_id = ' . $this->_db->quote($this->getExisting('transaction_id'));
	}

	/**
	 * Gets the default options for this data writer.
	 */
	protected function _getDefaultOptions()
	{
		return array(
			self::OPTION_ALLOW_CREDIT_CHANGE => true,
		);
	}

	/**
	 * Update notified user's total number of unread alerts
	 */
	protected function _postSave()
	{
		$amount = $this->get('amount');
		if($this->isUpdate() && $this->isChanged('moderate') && $this->get('action_id')!='withdraw' && ($amount > 0) && self::OPTION_ALLOW_CREDIT_CHANGE){

			$userId = $this->get('user_id');
			if($userId){
				$currency = XenForo_Application::get('brcCurrencies')->get($this->get('currency_id'));
				if($this->get('moderate')){
					$update = array(
						$userId => ' `'.$currency['column'].'` = `'.$currency['column'].'` - ' . $this->_db->quote($amount),
					);
				}else{
					$update = array(
						$userId => ' `'.$currency['column'].'` = `'.$currency['column'].'` + ' . $this->_db->quote($amount),
					);
				}
				$this->getModelFromCache('Brivium_Credits_Model_Credit')->_updateUserCredits($update);
			}
		}
	}

	/**
	 * Post-delete behaviors.
	 */
	protected function _postDelete()
	{
		if(XenForo_Application::getOptions()->BRC_returnCreditDeleteTransaction &&  ((!$this->get('moderate') && $this->get('amount') > 0) || $this->get('amount') < 0)){
			$amount  	= $this->get('amount')*(-1);
			$userId 	= $this->get('user_id');
			if($userId){
				$currency = XenForo_Application::get('brcCurrencies')->get($this->get('currency_id'));
				$update = array(
					$userId => ' `'.$currency['column'].'` = `'.$currency['column'].'` + ' . $this->_db->quote($amount),
				);

				$this->getModelFromCache('Brivium_Credits_Model_Credit')->_updateUserCredits($update);
				if($this->get("amount") > 0){
					$spend = 0;
					$earn = $this->get("amount");
				}else{
					$earn = 0;
					$spend = $this->get("amount");
				}
				$this->getModelFromCache('Brivium_Credits_Model_CreditStast')->updateTransactionCreditStasts($this->get('action_id'), $this->get('currency_id'), $earn, $spend, $this->get("transaction_date"));
			}
		}
		$this->getModelFromCache('XenForo_Model_Alert')->deleteAlerts('credit', $this->get('event_id'));
	}

	/**
	 * Gets the transaction model.
	 *
	 * @return Brivium_Credits_Model_Transaction
	 */
	protected function _getTransactionModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Transaction');
	}
}