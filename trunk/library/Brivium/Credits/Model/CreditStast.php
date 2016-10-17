<?php

class Brivium_Credits_Model_CreditStast extends XenForo_Model
{
	public function getStatisticRecord($actionId, $currencyId, $statsType='')
	{
		$db = $this->_getDb();
		$whereAction = '';
		if($actionId){
			$whereAction = " AND action_id = " . $db->quote($actionId);
		}
		return $db->fetchRow('
			SELECT SUM(total_earn) AS total_earn, SUM(total_spend) AS total_spend, start_date, stats_date
			FROM xf_brivium_credits_stats
			WHERE currency_id = ? AND stats_type = ? '.$whereAction.'
		', array($currencyId, $statsType));
	}

	public function getFirstStatisticDate($actionId, $currencyId, $statsType='')
	{
		$db = $this->_getDb();
		$whereAction = '';
		if($actionId){
			$whereAction = " AND action_id = " . $db->quote($actionId);
		}
		return $actionStast = $db->fetchOne('
			SELECT start_date
			FROM xf_brivium_credits_stats
			WHERE currency_id = ? AND stats_type = ? '.$whereAction.'
			ORDER BY  start_date ASC
			LIMIT 1
		', array($currencyId, $statsType));
	}

	public function prepareStatisticRecordConditions(array $conditions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (!empty($conditions['action_id']))
		{
			if (is_array($conditions['action_id']))
			{
				$sqlConditions[] = 'credits_stats.action_id IN (' . $db->quote($conditions['action_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'credits_stats.action_id = ' . $db->quote($conditions['action_id']);
			}
		}

		if (!empty($conditions['currency_id']))
		{
			$sqlConditions[] = 'credits_stats.currency_id = ' . $db->quote($conditions['currency_id']);
		}
		if (isset($conditions['stats_type']))
		{
			$sqlConditions[] = 'credits_stats.stats_type = ' . $db->quote($conditions['stats_type']);
		}
		if (!empty($conditions['stats_date']) && is_array($conditions['stats_date']))
		{
			list($operator, $cutOff) = $conditions['stats_date'];

			$this->assertValidCutOffOperator($operator);
			$sqlConditions[] = "credits_stats.stats_date $operator " . $db->quote($cutOff);
		}
		return $this->getConditionsForClause($sqlConditions);
	}

	public function getStatisticRecords(array $conditions)
	{
		$whereConditions = $this->prepareStatisticRecordConditions($conditions);

		return $this->fetchAllKeyed('
				SELECT credits_stats.*
				FROM xf_brivium_credits_stats AS credits_stats
				WHERE ' . $whereConditions
		, 'action_id');
	}

	public function getTransactionSum($currencyId, $startDate, array $amountCondition)
	{
		$db = $this->_getDb();
		list($operator, $cutOff) = $amountCondition;

		$this->assertValidCutOffOperator($operator);
		$sqlConditions = "amount $operator " . $db->quote($cutOff);
		return $db->fetchPairs('
			SELECT action_id, SUM(amount)
			FROM xf_brivium_credits_transaction
			WHERE currency_id = ?
				AND ' .$sqlConditions . '
				AND transaction_date > ?
			GROUP BY action_id
		', array($currencyId, $startDate));
	}

	public function replaceCreditStats($actionId, $earn, $spend, $startDate, $statsDate, $currencyId, $statsType)
	{
		$db = $this->_getDb();
		$db->query("
			REPLACE INTO `xf_brivium_credits_stats`
				(`action_id`, `total_earn`, `total_spend`, `start_date`, `stats_date`, `currency_id`, `stats_type`)
			VALUES
				(?, ?, ?, ?, ?, ?, ?);
		", array($actionId, $earn, $spend, $startDate, $statsDate, $currencyId, $statsType));
	}

	public function updateTransactionCreditStasts($actionId, $currencyId, $earn, $spend, $transactionDate)
	{
		$this->_getDb()->query("
			UPDATE xf_brivium_credits_stats
			SET `total_earn` = IF(`total_earn` - ?, `total_earn` - ?, 0),
				`total_spend` = IF(`total_spend` - ?, `total_spend` - ?, 0)
			WHERE stats_date > ? AND action_id = ? AND currency_id = ?
		", array($earn, $earn, $spend, $spend, $transactionDate, $actionId, $currencyId));
	}

	public function updateCreditStasts()
	{
		$db = $this->_getDb();
		$now = XenForo_Application::$time;

		$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
		$currencyIds = array_keys($currencies);


		$db->delete('xf_brivium_credits_stats', ' currency_id NOT IN (' .$db->quote($currencyIds). ')');

		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();
		$transactionModel = $this->_getTransactionModel();
		foreach($currencies AS $currencyId=>$currency){
			$conditions = array(
				'currency_id' => $currencyId,
				'stats_type' => 'daily'
			);
			$dailyStats = $this->getStatisticRecords($conditions);
			$conditions['stats_type'] = '';
			$totalStats = $this->getStatisticRecords($conditions);

			$dayStartTimestamps = XenForo_Locale::getDayStartTimestamps();
			$dayTime = $dayStartTimestamps['today'];
			$earnDays = $this->getTransactionSum($currencyId, $dayTime, array('>', 0));
			$spendDays = $this->getTransactionSum($currencyId, $dayTime, array('<', 0));

			$lastCheck = 0;

			$earnTotals = $this->getTransactionSum($currencyId, $lastCheck, array('>', 0));
			$spendTotals = $this->getTransactionSum($currencyId, $lastCheck, array('<', 0));

			foreach($actions AS $actionId=>$action){
				$spendDay = 0;
				$earnDay = 0;
				$spendTotal = 0;
				$earnTotal = 0;

				if(!empty($spendDays[$actionId])){
					$spendDay = $spendDays[$actionId];
				}
				if(!empty($earnDays[$actionId])){
					$earnDay = $earnDays[$actionId];
				}
				if(!empty($earnTotals[$actionId])){
					$earnTotal = $earnTotals[$actionId];
				}
				if(!empty($spendTotals[$actionId])){
					$spendTotal = $spendTotals[$actionId];
				}
				$this->replaceCreditStats($actionId, $earnDay, $spendDay, $dayTime, $now, $currencyId, 'daily');

				$startDate = $transactionModel->getFirstTransactionDate(array('currency_id'=>$currencyId, 'action_id'=>$actionId));
				if(!$startDate){
					$startDate = XenForo_Application::$time;
				}
				$this->replaceCreditStats($actionId, $earnTotal, $spendTotal, $startDate, $now, $currencyId, '');
			}
		}
	}

	public function canViewCreditStatistics(array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'viewStatistic');
	}

	public function deleteAllStast($currencyId)
	{
		$sqlConditions = array();
		$db = $this->_getDb();
		if($currencyId){
			$sqlConditions[] = 'currency_id = ' . $db->quote($currencyId);
		}
		if($sqlConditions){
			$whereConditions = $this->getConditionsForClause($sqlConditions);
			$this->_getDb()->query('DELETE FROM `xf_brivium_credits_stats` WHERE ' . $whereConditions);
		}else{
			$this->_getDb()->query('TRUNCATE TABLE xf_brivium_credits_stats');
		}
	}

	protected function _getTransactionModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Transaction');
	}
}