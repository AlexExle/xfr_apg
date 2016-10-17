<?php

class Brivium_Credits_ControllerAdmin_Currency extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('BRC_currency');
	}

	public function actionIndex()
	{
		$currencyModel = $this->_getCurrencyModel();
		$currencies = $currencyModel->getAllCurrencies();
		$viewParams = array(
			'currencies' => $currencies,
			'total' => count($currencies),
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Currency_List', 'BRC_currency_list', $viewParams);
	}

	public function _getCurrencyAddEditResponse(array $currency)
	{
		$currency['user_groups'] = $currency['user_groups']?$currency['user_groups']:array(0=>0);
		$listUserGroups = XenForo_Model::create('XenForo_Model_UserGroup')->getAllUserGroups();
		$userGroups[0] = array(
			'label' =>  sprintf('(%s)', new XenForo_Phrase('all_user_groups')),
			'value' => 0,
			'selected' => !$currency['user_groups']
		);
		foreach ($listUserGroups AS $userGroupId => $userGroup)
		{
			if($userGroupId!=0)
			$userGroups[$userGroupId] = array(
				'label' => $userGroup['title'],
				'value' => $userGroup['user_group_id'],
				'selected' => in_array($userGroup['user_group_id'] , $currency['user_groups'])
			);
		}
		$viewParams = array(
			'currency' => $currency,
			'userGroups' => $userGroups,
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Currency_Edit', 'BRC_currency_edit', $viewParams);
	}

	public function actionAdd()
	{
		$currency = $this->_getCurrencyModel()->getDefaultCurrency();
		return $this->_getCurrencyAddEditResponse($currency);
	}

	public function actionEdit()
	{
		$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);
		$currency = $this->_getCurrencyOrError($currencyId);
		$currency = $this->_getCurrencyModel()->prepareCurrency($currency);
		return $this->_getCurrencyAddEditResponse($currency);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		if ($this->_input->filterSingle('delete', XenForo_Input::STRING))
		{
			// user clicked delete
			return $this->responseReroute('Brivium_Credits_ControllerAdmin_Currencies', 'deleteConfirm');
		}

		$input = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'description' => XenForo_Input::STRING,
			'column' => XenForo_Input::STRING,

			'decimal_place' => XenForo_Input::UINT,

			'negative_handle' => XenForo_Input::STRING,
			'user_groups' => XenForo_Input::ARRAY_SIMPLE,
			'max_time' => XenForo_Input::UINT,
			'earn_max' => XenForo_Input::UNUM,

			'in_bound' => XenForo_Input::UINT,
			'out_bound' => XenForo_Input::UINT,

			'value' => XenForo_Input::UNUM,
			'active' => XenForo_Input::UINT,

			'withdraw' => XenForo_Input::UINT,
			'withdraw_min' => XenForo_Input::UNUM,
			'withdraw_max' => XenForo_Input::UNUM,

			'display_order' => XenForo_Input::UINT,

		));
		$input['code'] = $this->_input->filterSingle('code', XenForo_Input::STRING, array('noTrim'=>true));

		$input['symbol_left'] = $this->_input->filterSingle('symbol_left', XenForo_Input::STRING, array('noTrim'=>true));
		$input['symbol_right'] = $this->_input->filterSingle('symbol_right', XenForo_Input::STRING, array('noTrim'=>true));
		$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);

		$writer = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Currency');
		if ($currencyId)
		{
			$writer->setExistingData($currencyId);
			/*
			if($this->_input->filterSingle('delete_currency_icon', XenForo_Input::UINT) && $writer->getExisting('image_type')){
				$this->getModelFromCache('Brivium_Credits_Model_Image')->deleteImage($writer->getExisting('currency_id') , $writer->getExisting('image_type'));
				$input['image_type'] = '';
			}
			*/
			unset($input['column']);
		}
		$writer->bulkSet($input);
		$writer->save();
		$currencyId = $writer->get('currency_id');

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('brc-currencies') . $this->getLastHash($currencyId)
		);
	}

	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Currency');
			$dw->setExistingData($this->_input->filterSingle('currency_id', XenForo_Input::UINT));
			$dw->delete();
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('brc-currencies')
			);
		}
		else // show confirmation dialog
		{
			$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);
			$currency = $this->_getCurrencyOrError($currencyId);

			$writer = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Currency', XenForo_DataWriter::ERROR_EXCEPTION);
			$writer->setExistingData($currency);
			$writer->preDelete();
			$viewParams = array(
				'currency' => $currency
			);
			return $this->responseView('Brivium_Credits_ViewAdmin_Currency_Delete', 'BRC_currency_delete', $viewParams);
		}

	}

	/**
	 * Gets the specified currency or throws an error.
	 *
	 * @param integer $currencyId
	 * @param boolean $allowMaster Allow the master currency (0) to be fetched
	 *
	 * @return array
	 */
	protected function _getCurrencyOrError($currencyId)
	{
		$currency = $this->_getCurrencyModel()->getCurrencyById($currencyId);
		if (!$currency)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('BRC_requested_currency_not_found'), 404));
		}

		return $currency;
	}
	/**
	 * Gets the currency model.
	 *
	 * @return Brivium_Credits_Model_Currency
	 */
	protected function _getCurrencyModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Currency');
	}

}