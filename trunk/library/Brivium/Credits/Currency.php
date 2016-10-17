<?php

/**
 * Helper methods to generate currency sensitive output.
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_Currency
{
	protected $_code = '';
	/**
	 * Default currency.
	 *
	 * @var array
	 */
	protected $_currency = array();

	protected $_currencies = array();


	/**
	 * Constructor. Sets up the accessor using the provided currencies.
	 *
	 * @param array $currencies Collection of currencies. Keys represent currency names.
	 */
	public function __construct(array $currencies)
	{
		$this->setCurrencies($currencies);
	}

	/**
	 * Gets an currency. If the currency exists and is an array, then...
	 * If the currency is not an array, then the value of the currency is returned (provided no sub-currency is specified).
	 * Otherwise, null is returned.
	 *
	 * @param string $currencyId Id of the currency
	 *
	 * @return null|mixed Null if the currency doesn't exist (see above) or the currency's value.
	 */
	public function get($currencyId)
	{
		if (!isset($this->_currencies[$currencyId]))
		{
			return null;
		}

		$currency = $this->_currencies[$currencyId];

		if (is_array($currency))
		{
			return $currency;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Gets all currencies in their raw form.
	 *
	 * @return array
	 */
	public function getCurrencies()
	{
		return $this->_currencies;
	}

	/**
	 * Sets the collection of currencies manually.
	 *
	 * @param array $currencies
	 */
	public function setCurrencies(array $currencies)
	{
		$this->_currencies = $currencies;
	}

	/**
	 * Magic getter for first-order currencies.
	 * @param string $currency
	 *
	 * @return null|mixed
	 */
	public function __get($currency)
	{
		return $this->get($currency);
	}

	/**
	 * Returns true if the named currency exists.
	 *
	 * @param string $currency
	 *
	 * @return boolean
	 */
	public function __isset($currency)
	{
		return ($this->get($currency) !== null);
	}
	/**
	 * Formats the given number for a currency.
	 *
	 * @param float|integer $number Number to format
	 * @param int|null $currencyId Currency to override default
	 *
	 * @return string Formatted number
	 */
	public function currencyFormat($number = 0, $negate=false, $currencyId, $rich = false)
	{
		if(!$negate || $negate==='false')
		{
			$negate= false;
		}
		if ($currencyId && isset($this->_currencies[$currencyId]))
		{
			$currency = $this->_currencies[$currencyId];
		}else{
			return $number;
		}
		$negative = false;
		$formated = '';
		if (is_numeric($number)) {
			if ($number < 0)
			{
				$negative = true;
				$number *= -1;
			}
			$number = XenForo_Locale::numberFormat($number, $currency['decimal_place']);
		}else{
			$number = XenForo_Locale::numberFormat(0, $currency['decimal_place']);
		}
		$formated = ($negate && $negative)?' - ':'';

		if (!empty($currency['symbol_left']) && is_scalar($currency['symbol_left'])) {
      		$formated .= html_entity_decode($currency['symbol_left']);
    	}
		$formated .= $number;
		if (!empty($currency['symbol_right']) && is_scalar($currency['symbol_right'])) {
      		$formated .= html_entity_decode($currency['symbol_right']);
    	}
		if($rich){
			return $currency?'<span class="brc_currency_style_' . $currencyId . '">' . $formated . '</span>':$formated;
		}
		return $formated;
	}
}