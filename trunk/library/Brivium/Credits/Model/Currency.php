<?php

class Brivium_Credits_Model_Currency extends XenForo_Model
{
	public function getCurrencyById($id, $fetchMaster = false)
	{
		return $this->_getDb()->fetchRow('
				SELECT *
				FROM xf_brivium_credits_currency
				WHERE currency_id = ?
			', array($id));
	}

	public function getCurrencyByColumn($column)
	{
		return $this->_getDb()->fetchRow('
				SELECT *
				FROM xf_brivium_credits_currency
				WHERE `column` = ?
			',array( $column));
	}

	public function getAllCurrencies()
	{
		if (($currencies = $this->_getLocalCacheData('allBrcCurrencies')) === false)
		{
			$currencies = $this->fetchAllKeyed('
				SELECT *
				FROM xf_brivium_credits_currency
				ORDER BY display_order
			', 'currency_id');

			$this->setLocalCacheData('allBrcCurrencies', $currencies);
		}

		return $currencies;
	}

	public function getActiveCurrencies()
	{
		if (($currencies = $this->_getLocalCacheData('allBrcCurrencies')) === false)
		{
			$currencies = $this->fetchAllKeyed('
				SELECT *
				FROM xf_brivium_credits_currency
				WHERE active = 1
				ORDER BY display_order
			', 'currency_id');

			$this->setLocalCacheData('allBrcCurrencies', $currencies);
		}

		return $currencies;
	}
	/**
	* Helper to get the default currency
	*
	* @return array
	*/
	public function getDefaultCurrency()
	{
		return array(
			'currency_id' => 0,
			'title' => '',
			'description' => '',
			'column' => '',
			'code' => '',
			'symbol_left' => '',
			'symbol_right' => '',
			'decimal_place' => 0,
			'negative_handle' => 'show',
			'user_groups' => array(),
			'max_time' => 0,
			'earn_max' => 0,
			'in_bound' => 1,
			'out_bound' => 1,
			'value' => 1,
			'active' => 1,
			'display_order' => 0,
		);
	}

	/**
	 * Returns an array of all currencies, suitable for use in ACP template syntax as options source.
	 *
	 * @param array $currencyTree
	 *
	 * @return array
	 */
	public function getCurrenciesForOptionsTag($selectedIds = array(), $allBrcCurrencies= null)
	{
		if ($allBrcCurrencies === null)
		{
			$allBrcCurrencies = $this->getAllCurrencies();
		}

		$currencies = array();
		foreach ($allBrcCurrencies AS $id => $currency)
		{
			$currencies[$id] = array(
				'value' => $id,
				'label' => $currency['title'],
				'selected' => (in_array($id,$selectedIds)),
			);
		}

		return $currencies;
	}


	/**
	 * Gets all currencies in the format expected by the currency cache.
	 *
	 * @return array Format: [currency id] => info, with phrase cache as array
	 */
	public function getAllCurrenciesForCache()
	{
		$this->resetLocalCacheData('allBrcCurrencies');

		$currencies = $this->getAllCurrencies();
		return $currencies;
	}

	/**
	 * Rebuilds the full currency cache.
	 *
	 * @return array Format: [currency id] => info, with phrase cache as array
	 */
	public function rebuildCurrencyCache()
	{
		$this->resetLocalCacheData('allBrcCurrencies');

		$currencies = $this->getAllCurrenciesForCache();
		$currencies = $this->prepareCurrencies($currencies);

		$this->_getDataRegistryModel()->set('brcCurrencies', $currencies);

		return $currencies;
	}

	/**
	 * Rebuilds all currency caches.
	 */
	public function rebuildCurrencyCaches()
	{
		$this->rebuildCurrencyCache();
	}

	/**
	 * Prepares an ungrouped list of currencies for display.
	 *
	 * @param array $currencies Format: [] => currency info
	 *
	 * @return array
	 */
	public function prepareCurrencies($currencies = array())
	{
		if(empty($currencies))return array();
		foreach ($currencies AS &$currency)
		{
			$currency = $this->prepareCurrency($currency);
		}
		return $currencies;
	}

	/**
	 * Prepares a currency for display.
	 *
	 * @param array $currency
	 *
	 * @return array
	 */
	public function prepareCurrency($currency = array())
	{
		$data1 = @unserialize($currency['user_groups']);
		if ($currency['user_groups'] && $data1 !== false)
		{
			$currency['user_groups'] = unserialize($currency['user_groups']);
		}else{
			$currency['user_groups'] = array();
		}
		return $currency;
	}

	public function getCurrencyOptionsArray($selectedId = null, $currencies= null)
	{
		$options = array();
		if (!$currencies)
		{
			$currencies = $this->getAllCurrencies();
		}

		foreach ($currencies AS $currencyId => $currency)
		{
			$options[$currency['currency_id']] = array(
				'value' => $currency['currency_id'],
				'label' => $currency['title'],
				'selected' => ($currency['currency_id'] == $selectedId),
				'depth' => 0,
			);
		}

		return $options;
	}
}