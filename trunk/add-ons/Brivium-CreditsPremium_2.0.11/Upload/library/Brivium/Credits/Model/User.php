<?php

class Brivium_Credits_Model_User extends XFCP_Brivium_Credits_Model_User
{
	public function getUsersInRange(array $conditions, array $fetchOptions = array())
	{
		$whereClause = $this->prepareUserConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareUserOrderOptions($fetchOptions, 'user.username');
		$joinOptions = $this->prepareUserFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT user.*
					' . $joinOptions['selectFields'] . '
				FROM xf_user AS user
				' . $joinOptions['joinTables'] . '
				WHERE ' . $whereClause . '
				ORDER BY user.user_id ASC
			', $limitOptions['limit'], $limitOptions['offset']
		), 'user_id');
	}

	public function prepareUserConditions(array $conditions, array &$fetchOptions)
	{
		$result = parent::prepareUserConditions($conditions, $fetchOptions);
		$sqlConditions = array($result);

		if (!empty($conditions['dob_day'])) {
			$sqlConditions[] = 'user_profile.dob_day = ' . $this->_getDb()->quote($conditions['dob_day']);
		}
		if (!empty($conditions['dob_month'])) {
			$sqlConditions[] = 'user_profile.dob_month = ' . $this->_getDb()->quote($conditions['dob_month']);
		}
		if (!empty($conditions['brc_user_id_start'])) {
			$sqlConditions[] = 'user.user_id > ' . $this->_getDb()->quote($conditions['brc_user_id_start']);
		}
		if (count($sqlConditions) > 1) {
			return $this->getConditionsForClause($sqlConditions);
		} else {
			return $result;
		}
	}

	public function prepareUserOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		if(!empty($fetchOptions['order']) && $fetchOptions['order']=='brc_user_id'){

		}
		$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
		$choices = array();
		foreach($currencies AS $currency){
			$choices[$currency['column']] = 'user.`'.$currency['column'].'`';
		}
		$result = $this->getOrderByClause($choices, $fetchOptions, '');
		if($result){
			return $result;
		}else{
			return parent::prepareUserOrderOptions($fetchOptions, $defaultOrderSql);
		}
	}
}