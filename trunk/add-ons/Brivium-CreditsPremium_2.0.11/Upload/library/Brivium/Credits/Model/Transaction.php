<?php

class Brivium_Credits_Model_Transaction extends XenForo_Model
{
	const FETCH_TRANSACTION_CREATE    			= 0x01;
	const FETCH_TRANSACTION_USERACTION    		= 0x02;
	const FETCH_TRANSACTION_CURRENCY    		= 0x04;
	const FETCH_TRANSACTION_FULL    			= 0x07;

	public function getAllTransactions(array $fetchOptions = array())
	{
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		$joinOptions = $this->prepareTransactionFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults('
				SELECT transaction.*
					' . $joinOptions['selectFields'] . '
				FROM xf_brivium_credits_transaction AS transaction
				' . $joinOptions['joinTables'] . '
				' . $whereClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'user_id');
	}

	/**
	*	get Category by its id
	* 	@param integer $transactionId
	* 	@param array $fetchOptions Collection of options related to fetching
	*	@return array|false Category info
	*/
	public function getTransactionById($transactionId, $fetchOptions = array()){
		$joinOptions = $this->prepareTransactionFetchOptions($fetchOptions);
		return $this->_getDb()->fetchRow('
			SELECT transaction.*
			' .$joinOptions['selectFields']. '
			FROM xf_brivium_credits_transaction AS transaction
			' .$joinOptions['joinTables']. '
			WHERE transaction.transaction_id = ?
			LIMIT 0,1
		',$transactionId);
	}

	public function getTransactionByKey($transactionKey, $fetchOptions = array()){
		$joinOptions = $this->prepareTransactionFetchOptions($fetchOptions);
		return $this->_getDb()->fetchRow('
			SELECT transaction.*
			' .$joinOptions['selectFields']. '
			FROM xf_brivium_credits_transaction AS transaction
			' .$joinOptions['joinTables']. '
			WHERE transaction.transaction_key = ?
			LIMIT 0,1
		',$transactionKey);
	}

	/**
	*	Gets multi transactions.
	*
	*	@param array $transactionIds
	*	@param array $fetchOptions Collection of options related to fetching
	*
	*	@return array Format: [transaction id] => info
	*/
	public function getTransactionsByIds(array $transactionIds)
	{
		if (!$transactionIds)
		{
			return array();
		}
		return $this->fetchAllKeyed('
			SELECT transaction.*
			FROM xf_brivium_credits_transaction AS transaction
			WHERE transaction.transaction_id IN (' . $this->_getDb()->quote($transactionIds) . ')
		', 'transaction_id');
	}

	/**
	 * Prepares a collection of transaction fetching related conditions into an SQL clause
	 *
	 * @param array $conditions List of conditions
	 * @param array $fetchOptions Modifiable set of fetch options (may have joins pushed on to it)
	 *
	 * @return string SQL clause (at least 1=1)
	 */
	public function prepareTransactionConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (!empty($conditions['transaction_id']))
		{
			if (is_array($conditions['transaction_id']))
			{
				$sqlConditions[] = 'transaction.transaction_id IN (' . $db->quote($conditions['transaction_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'transaction.transaction_id = ' . $db->quote($conditions['transaction_id']);
			}
		}
		if (!empty($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				$sqlConditions[] = 'transaction.user_id IN (' . $db->quote($conditions['user_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'transaction.user_id = ' . $db->quote($conditions['user_id']);
			}
		}
		if (!empty($conditions['currency_id']))
		{
			if (is_array($conditions['currency_id']))
			{
				$sqlConditions[] = 'transaction.currency_id IN (' . $db->quote($conditions['currency_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'transaction.currency_id = ' . $db->quote($conditions['currency_id']);
			}
		}
		if (!empty($conditions['not_action_id']))
		{
			if (is_array($conditions['not_action_id']))
			{
				$sqlConditions[] = 'transaction.action_id NOT IN (' . $db->quote($conditions['not_action_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'transaction.action_id <> ' . $db->quote($conditions['not_action_id']);
			}
		}

		if (isset($conditions['is_revert']))
		{
			$sqlConditions[] = 'transaction.is_revert = ' . ($conditions['is_revert'] ? 1 : 0);
		}
		if (isset($conditions['moderate']))
		{
			$sqlConditions[] = 'transaction.moderate = ' . ($conditions['moderate'] ? 1 : 0);
		}
		if (!empty($conditions['action_id']))
		{
			$sqlConditions[] = 'transaction.action_id = ' . $db->quote($conditions['action_id']);
		}
		if (!empty($conditions['transaction_key']))
		{
			$sqlConditions[] = 'transaction.transaction_key = ' . $db->quote($conditions['transaction_key']);
		}
		if (!empty($conditions['content_id']))
		{
			$sqlConditions[] = 'transaction.content_id = ' . $db->quote($conditions['content_id']);
		}
		if (!empty($conditions['content_type']))
		{
			$sqlConditions[] = 'transaction.content_type = ' . $db->quote($conditions['content_type']);
		}
		if (!empty($conditions['event_id']))
		{
			$sqlConditions[] = 'transaction.event_id = ' . $db->quote($conditions['event_id']);
		}
		if (!empty($conditions['user_action_id']))
		{
			$sqlConditions[] = 'transaction.user_action_id = ' . $db->quote($conditions['user_action_id']);
		}
		if (!empty($conditions['amount']) && is_array($conditions['amount']))
		{
			list($operator, $cutOff) = $conditions['amount'];

			$this->assertValidCutOffOperator($operator);
			$sqlConditions[] = "transaction.amount $operator " . $db->quote($cutOff);
		}
		if (!empty($conditions['transaction_date']) && is_array($conditions['transaction_date']))
		{
			list($operator, $cutOff) = $conditions['transaction_date'];

			$this->assertValidCutOffOperator($operator);
			$sqlConditions[] = "transaction.transaction_date $operator " . $db->quote($cutOff);
		}
		if (!empty($conditions['start']))
		{
			$sqlConditions[] = 'transaction.transaction_date >= ' . $db->quote($conditions['start']);
		}

		if (!empty($conditions['end']))
		{
			$sqlConditions[] = 'transaction.transaction_date <= ' . $db->quote($conditions['end']);
		}
		if (!empty($conditions['currency_active']))
		{
			$sqlConditions[] = 'currency.active = 1';
			$this->addFetchOptionJoin($fetchOptions, self::FETCH_TRANSACTION_CURRENCY);
		}
		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareTransactionFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$orderBy = '';
		$orderBySecondary = '';
		if (!empty($fetchOptions['order']))
		{
			switch ($fetchOptions['order'])
			{
				case 'action_id':
				case 'user_id':
				case 'owner_id':
				case 'amount':
					$orderBy = 'transaction.' . $fetchOptions['order'];
					$orderBySecondary = ', transaction.transaction_date DESC';
					break;
				default:
					$orderBy = 'transaction.transaction_date';
			}
			if (!isset($fetchOptions['orderDirection']) || $fetchOptions['orderDirection'] == 'desc')
			{
				$orderBy .= ' DESC';
			}
			else
			{
				$orderBy .= ' ASC';
			}
			$orderBy .= $orderBySecondary;
		}

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_TRANSACTION_CREATE)
			{
				$selectFields .= ', user_create.username AS username';
				$joinTables .= '
					LEFT JOIN xf_user AS user_create ON
						(user_create.user_id = transaction.user_id)';
			}
			if($fetchOptions['join'] & self::FETCH_TRANSACTION_USERACTION)
			{
				$selectFields .= ', user_action.username AS user_action_name';
				$joinTables .= '
					LEFT JOIN xf_user AS user_action ON
						(user_action.user_id = transaction.user_action_id)';
			}
			if($fetchOptions['join'] & self::FETCH_TRANSACTION_CURRENCY)
			{
				$selectFields .= ', currency.active AS currency_active';
				$joinTables .= '
					LEFT JOIN xf_brivium_credits_currency AS currency ON
						(currency.currency_id = transaction.currency_id)';
			}

		}
		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables,
			'orderClause'  => ($orderBy ? "ORDER BY $orderBy" : '')
		);
	}

	/**
	 * Gets transactions that match the given conditions.
	 *
	 * @param array $conditions Conditions to apply to the fetching
	 * @param array $fetchOptions Collection of options that relate to fetching
	 *
	 * @return array Format: [transaction id] => info
	 */
	public function getTransactions(array $conditions, array $fetchOptions = array())
	{
		$whereConditions = $this->prepareTransactionConditions($conditions, $fetchOptions);

		$sqlClauses = $this->prepareTransactionFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		return $this->fetchAllKeyed($this->limitQueryResults(			'
				SELECT transaction.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_brivium_credits_transaction AS transaction
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
				' . $sqlClauses['orderClause'] . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'transaction_id');
	}

	/**
	 * Gets the count of transactions with the specified criteria.
	 *
	 * @param array $conditions Conditions to apply to the fetching
	 *
	 * @return integer
	 */
	public function countTransactions(array $conditions)
	{
		$fetchOptions = array();
		$whereClause = $this->prepareTransactionConditions($conditions, $fetchOptions);

		$joinOptions = $this->prepareTransactionFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_brivium_credits_transaction AS transaction
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause .'
			LIMIT 0,1'
		);
	}

	public function getLastTransactionDate(array $conditions)
	{
		$fetchOptions = array();
		$whereClause = $this->prepareTransactionConditions($conditions, $fetchOptions);

		$joinOptions = $this->prepareTransactionFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT transaction_date
			FROM xf_brivium_credits_transaction AS transaction
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause.'
			ORDER BY transaction_date DESC
			LIMIT 0,1
			'
		);
	}

	public function getFirstTransactionDate(array $conditions)
	{
		$fetchOptions = array();
		$whereClause = $this->prepareTransactionConditions($conditions, $fetchOptions);

		$joinOptions = $this->prepareTransactionFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT transaction_date
			FROM xf_brivium_credits_transaction AS transaction
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause.'
			ORDER BY transaction_date ASC
			LIMIT 0,1
			'
		);
	}

	public function getTopTransactions(array $conditions, array $fetchOptions = array())
	{
		$whereConditions = $this->prepareTransactionConditions($conditions, $fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		if(!empty($fetchOptions['orderDirection']) && $fetchOptions['orderDirection']=='DESC'){
			$direction = 'DESC';
		}else{
			$direction = 'ASC';
		}
		return $this->fetchAllKeyed($this->limitQueryResults('
				SELECT transaction.user_id, user_create.username, user_create.avatar_date, SUM(transaction.amount) AS credits
				FROM xf_brivium_credits_transaction AS transaction
					LEFT JOIN xf_user AS user_create ON
						(user_create.user_id = transaction.user_id)
				WHERE ' . $whereConditions . '
				GROUP BY transaction.user_id
				ORDER BY credits '. $direction .'
			', $limitOptions['limit'], $limitOptions['offset']
		), 'user_id');
	}

	public function countTopTransactions(array $conditions)
	{
		$fetchOptions = array();
		$whereClause = $this->prepareTransactionConditions($conditions, $fetchOptions);

		$joinOptions = $this->prepareTransactionFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_brivium_credits_transaction AS transaction
				LEFT JOIN xf_user AS user_create ON
					(user_create.user_id = transaction.user_id)
			WHERE ' . $whereClause . '
			GROUP BY transaction.user_id
			ORDER BY credits ASC
			LIMIT 0,1'
		);
	}

	public function prepareTransactions(array $transactions)
	{
		if(!$transactions) return array();
		foreach($transactions AS &$transaction){
			$transaction = $this->prepareTransaction($transaction);
		}
		return $transactions;
	}

	public function prepareTransaction(array $transaction)
	{
		if(!$transaction) return array();

		$handler = XenForo_Application::get('brcActionHandler')->getActionHandler($transaction['action_id']);
		if($handler){
			$transaction = $handler->prepareTransaction($transaction);
		}else{
			$actionNamePhrase = 'BRC_undefined_action';
			$transaction['action'] = new XenForo_Phrase($actionNamePhrase);
			if ($transaction['extra_data'] && !(@unserialize($transaction['extra_data']) === false && $transaction['extra_data'] != serialize(false)))
			{
				$transaction['extraData'] = @unserialize($transaction['extra_data']);
				if (!is_array($transaction['extraData']))
				{
					$transaction['extraData'] = array();
				}
				if(!empty($transaction['extraData']['reverted'])){
					$transaction['action'] = new XenForo_Phrase($actionNamePhrase . '_reverted');
				}
			}

			if(!$transaction['user_action_id']){
				$transaction['user_action_name'] = new XenForo_Phrase('BRC_system');
			}

			$type = 'earned';
			if ($transaction['amount'] < 0)
			{
				$type = 'spent';
			}

			$transaction['amount_phrase'] = new XenForo_Phrase('BRC_transaction_' . $type, array('amount'=>XenForo_Application::get('brcCurrencies')->currencyFormat($transaction['amount'], false, $transaction['currency_id'])));
		}

		return $transaction;
	}

	public function canViewOtherTransactions(array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'viewOtherTransaction');
	}

	public function deleteAllTransaction($currencyId, $userIds)
	{
		$sqlConditions = array();
		$db = $this->_getDb();
		if($currencyId){
			$sqlConditions[] = 'currency_id = ' . $db->quote($currencyId);
		}
		if($userIds){
			$sqlConditions[] = 'user_id IN (' . $db->quote($userIds) . ')';
		}
		if($sqlConditions){
			$whereConditions = $this->getConditionsForClause($sqlConditions);
			$this->_getDb()->query('DELETE FROM `xf_brivium_credits_transaction` WHERE ' . $whereConditions);
		}else{
			$this->_getDb()->query('TRUNCATE TABLE xf_brivium_credits_transaction');
		}
	}
}