<?php

/**
* Data writer for Purchased.
*
* @package Brivium_Credits
*/
class Brivium_CreResIntegration_DataWriter_Purchased extends XenForo_DataWriter
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
			'xf_resource_purchased' => array(
				'resource_purchased_id'	=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'resource_id'			=> array('type' => self::TYPE_UINT, 'required' => true),
				'user_id'				=> array('type' => self::TYPE_UINT, 'required' => true),
				'resource_version_id'	=> array('type' => self::TYPE_UINT, 'default' => 0),
				'purchased_date'		=> array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'resource_price'		=> array('type' => self::TYPE_FLOAT, 'default' => 0),
				'active'				=> array('type' => self::TYPE_UINT, 'default' => 1),
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
		if (!$purchasedId = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('xf_resource_purchased' => $this->_getResourcePurchasedModel()->getResourcePurchasedById($purchasedId));
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
		return 'resource_purchased_id = ' . $this->_db->quote($this->getExisting('resource_purchased_id'));
	}

	/**
	 * Update notified user's total number of unread alerts
	 */
	protected function _postSave()
	{
	}

	/**
	 * Post-delete behaviors.
	 */
	protected function _postDelete()
	{
	}
	
	/**
	 * Gets the purchased model.
	 *
	 * @return Brivium_CreResIntegration_Model_Purchased
	 */
	protected function _getResourcePurchasedModel()
	{
		return $this->getModelFromCache('Brivium_CreResIntegration_Model_Purchased');
	}
	
	
	
}