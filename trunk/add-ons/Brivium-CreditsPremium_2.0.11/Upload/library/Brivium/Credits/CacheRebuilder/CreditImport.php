<?php

class Brivium_Credits_CacheRebuilder_CreditImport extends XenForo_CacheRebuilder_Abstract
{
	/**
	 * Gets rebuild message.
	 */
	public function getRebuildMessage()
	{
		return new XenForo_Phrase('BRC_import_credits');
	}

	/**
	 * Shows the exit link.
	 */
	public function showExitLink()
	{
		return true;
	}

	/**
	 * Rebuilds the data.
	 *
	 * @see XenForo_CacheRebuilder_Abstract::rebuild()
	 */
	public function rebuild($position = 0, array &$options = array(), &$detailedMessage = '')
	{
		$options['batch'] = isset($options['batch']) ? $options['batch'] : 500;
		$options['batch'] = max(1, $options['batch']);

		/* @var $userModel XenForo_Model_User */
		$userModel = XenForo_Model::create('XenForo_Model_User');
		$creditModel = XenForo_Model::create('Brivium_Credits_Model_Credit');

		$userIds = $userModel->getUserIdsInRange($position, $options['batch']);
		if (sizeof($userIds) == 0)
		{
			return true;
		}

		$inputHandler = new XenForo_Input($options);
		$input = $inputHandler->filter(array(
			'money_type' => XenForo_Input::STRING,
			'remove_trophy_points' => XenForo_Input::UINT,
			'currency_id' => XenForo_Input::UINT,
			'type' => XenForo_Input::STRING,
			'currency' => XenForo_Input::ARRAY_SIMPLE
		));
		$fields = array();

		$brcCurrenciesObj = XenForo_Application::get('brcCurrencies');
		$addOnModel = XenForo_Model::create('XenForo_Model_AddOn');

		if($input['money_type']=='bdbank'){
			if (!$addOnModel->getAddOnVersion('bdbank')) {
				//[bd] Banking Addon required;
				return true;
			}
			if($input['currency_id']){
				$currency = $brcCurrenciesObj->get($input['currency_id']);
			}else{
				return true;
			}
			$fields[XenForo_Application::get("options")->bdbank_field] = array(
				'type' => $input['type'],
				'field' => XenForo_Application::get("options")->bdbank_field,
				'column' => $currency['column'],
			);
		}else if($input['money_type']=='adcredit'){

			if(empty($input['currency']) || !is_array($input['currency'])){
				return true;
			}
			$adCurrencies = $creditModel->getAllAdCreditCurrencies();
			if(!$adCurrencies){
				return;
			}
			foreach($input['currency'] AS $currencyId=>$currencyOptions){
				if(empty($adCurrencies[$currencyId]) || empty($currencyOptions['currency_id'])){
					continue;
				}
				$currency = $brcCurrenciesObj->get($currencyOptions['currency_id']);

				if(!$currency){
					continue;
				}
				$fields[$adCurrencies[$currencyId]['currency_id']] = array(
					'type' => !empty($currencyOptions['type'])?$currencyOptions['type']:'merge',
					'field' => $adCurrencies[$currencyId]['currency_id'],
					'column' => $currency['column'],
				);
			}


		}else if($input['money_type']=='trophy_points'){
			if($input['currency_id']){
				$currency = $brcCurrenciesObj->get($input['currency_id']);
			}else{
				return true;
			}
			$fields['trophy_points'] = array(
				'type' => $input['type'],
				'field' => 'trophy_points',
				'column' => $currency['column'],
			);
		}else if($input['money_type']=='my_points'){
			if (!$addOnModel->getAddOnVersion('myPoints')) {
				//My Points Addon required;
				return true;
			}
			if($input['currency_id']){
				$currency = $brcCurrenciesObj->get($input['currency_id']);
			}else{
				return true;
			}
			$fields['mypoints_currency'] = array(
				'type' => $input['type'],
				'field' => 'mypoints_currency',
				'column' => $currency['column'],
			);
		}else{
			return true;
		}
		if(empty($fields)){
			return true;
		}

		XenForo_Db::beginTransaction();
		foreach ($userIds AS $userId)
		{
			$position = $userId;
			/* @var $userDw XenForo_DataWriter_User */
			$userDw = XenForo_DataWriter::create('XenForo_DataWriter_User', XenForo_DataWriter::ERROR_SILENT);
			if ($userDw->setExistingData($userId))
			{
				foreach($fields AS $field){
					if (is_null($userDw->get($field['column'])) || is_null($userDw->get($field['field']))){
						return true;
					}else{
						if($input['remove_trophy_points'] && $input['money_type']=='trophy_points'){
							$userDw->set('trophy_points', 0);
						}
						if($field['type']=='merge'){
							$userDw->set($field['column'], $userDw->get($field['column']) + $userDw->get($field['field']));
						}else{
							$userDw->set($field['column'], $userDw->get($field['field']));
						}
					}
				}
				if($userDw->hasChanges()){
					$userDw->save();
				}
			}
		}

		XenForo_Db::commit();

		$detailedMessage = XenForo_Locale::numberFormat($position);

		return $position;
	}
}