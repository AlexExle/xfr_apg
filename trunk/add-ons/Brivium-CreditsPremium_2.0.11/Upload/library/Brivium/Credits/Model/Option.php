<?php

/**
 * Options model.
 *
 * @package XenForo_Options
 */
class Brivium_Credits_Model_Option extends XFCP_Brivium_Credits_Model_Option
{
	public function prepareOption(array $option)
	{
		if($option['option_id']=='userTitleLadderField'){
			$currencies = $this->getModelFromCache('Brivium_Credits_Model_Currency')->getAllCurrencies();
			$currencyParams = array($option['edit_format_params']);
			foreach($currencies AS $currency){
				if(!empty($currency['currency_id']) && !empty($currency['title']))
				$currencyParams[] = $currency['column'] . '='.$currency['title'];
			}
			if($currencyParams){
				$option['edit_format_params'] = implode("\n", $currencyParams);
			}
		}
		return parent::prepareOption($option);
	}
}