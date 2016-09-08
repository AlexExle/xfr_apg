<?php

class Brivium_Credits_Payment_PayPal_Model_Payment extends XenForo_Model
{
	/**
	 * Gets any log records that apply to the specified transaction.
	 *
	 * @param string $transactionId
	 *
	 * @return array [log id] => info
	 */
	public function getLogByTransactionId($transactionId)
	{
		if ($transactionId === '')
		{
			return array();
		}
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_brivium_credits_paypal_log
			WHERE  transaction_id = ?
		', array( $transactionId));
	}

	public function getTransactionLogById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT user.*, log.*
				FROM xf_brivium_credits_paypal_log AS log
				LEFT JOIN xf_user AS user ON (user.user_id = log.user_id)
			WHERE log.payment_log_id = ?
		', $id);
	}
	/**
	 * Gets any log record that indicates a transaction has been processed.
	 *
	 * @param string $transactionId
	 *
	 * @return array|false
	 */
	public function getProcessedTransactionLog($transactionId)
	{
		if ($transactionId === '')
		{
			return array();
		}

		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_brivium_credits_paypal_log
			WHERE transaction_id = ?
				AND transaction_type IN (\'payment\', \'cancel\')
			ORDER BY log_date
		', 'payment_log_id', $transactionId);
	}

	public function logPayment($data)
	{
		$this->_getDb()->insert('xf_brivium_credits_paypal_log', $data);
		return $this->_getDb()->lastInsertId();
	}

	public function sendEmailPayment($user, $dataCredit, $event, $paypalData)
	{
		$currency = !empty($event['currency_id'])?XenForo_Application::get('brcCurrencies')->$event['currency_id']:array();

		if($user && $currency && !empty($currency['active']) && isset($user[$currency['column']]) && $dataCredit)
		{
			$currentCredit = $user[$currency['column']] + $dataCredit['amount'];
			$params = array(
				'user'				=> $user,
				'dataCredit'		=> $dataCredit,
				'currentCredit'		=> $currentCredit,
				'currency'			=> $currency,
				'paypalData'		=> $paypalData,
				'transactionTime'	=> XenForo_Application::$time,
				'boardTitle'		=> XenForo_Application::get('options')->boardTitle
			);
			$mail = XenForo_Mail::create('BRCP_user_email_payment_info', $params, $user['language_id']);
			return $mail->send($user['email'], $user['username']);
		}
		return false;
	}
	/**
	 * Prepares a list of transaction log conditions.
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return string
	 */
	public function prepareTransactionLogConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		if (!empty($conditions['transaction_id']))
		{
			if (is_array($conditions['transaction_id']))
			{
				$sqlConditions[] = 'log.transaction_id IN (' . $db->quote($conditions['transaction_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'log.transaction_id = ' . $db->quote($conditions['transaction_id']);
			}
		}

		if (!empty($conditions['subscriber_id']))
		{
			if (is_array($conditions['subscriber_id']))
			{
				$sqlConditions[] = 'log.subscriber_id IN (' . $db->quote($conditions['subscriber_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'log.subscriber_id = ' . $db->quote($conditions['subscriber_id']);
			}
		}

		if (!empty($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				$sqlConditions[] = 'user.user_id IN (' . $db->quote($conditions['user_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'user.user_id = ' . $db->quote($conditions['user_id']);
			}
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function getTransactionLogs(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause = $this->prepareTransactionLogConditions($conditions, $fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			"
				SELECT user.*, log.*
				FROM xf_brivium_credits_paypal_log AS log
				LEFT JOIN xf_user AS user ON (user.user_id = log.user_id)
				WHERE " . $whereClause . "
				ORDER BY log.log_date DESC
			", $limitOptions['limit'], $limitOptions['offset']
		), 'payment_log_id');
	}

	public function countTransactionLogs(array $conditions = array())
	{
		$fetchOptions = array();
		$whereClause = $this->prepareTransactionLogConditions($conditions, $fetchOptions);

		// joins are needed for conditions
		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM xf_brivium_credits_paypal_log AS log
			LEFT JOIN xf_user AS user ON (user.user_id = log.user_id)
			WHERE " . $whereClause . "
		");
	}


}