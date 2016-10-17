<?php

class Brivium_KeyCode_ControllerAdmin_Category extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		$codeModel = $this->_getCodeModel();
		$categories = $codeModel->getAllCategories();
		$viewParams = array(
			'categories' => $categories,
		);
		return $this->responseView('Brivium_KeyCode_ViewAdmin_KeyCode_ListCodes', 'BRKC_category_list', $viewParams);
	}
	/*========================= Category ================================*/

	public function actionAdd()
	{
		return $this->responseReroute('Brivium_KeyCode_ControllerAdmin_Category', 'edit');
	}

	public function actionEdit()
	{
		$categoryId = $this->_input->filterSingle('code_category_id', XenForo_Input::UINT);
		$codeModel = $this->_getCodeModel();

		if($categoryId){
			$category = $codeModel->getCategoryById($categoryId);
			$categoryUserGroups = @unserialize($category['user_groups']);
			$category['max_code_options'] = @unserialize($category['max_code_options']);
		}else{
			$category = array(
				'code_category_id' => 0,
				'price' => 0,
				'max_code_options' => array(),
				'user_groups' => array(),
				'active' => true,
			);
			$categoryUserGroups = array();
		}
		$listUserGroups = XenForo_Model::create('XenForo_Model_UserGroup')->getAllUserGroups();
		$userGroups[0] = array(
			'label' =>  sprintf('(%s)', new XenForo_Phrase('all_user_groups')),
			'value' => 0,
			'selected' => empty($categoryUserGroups)
		);
		foreach ($listUserGroups AS $userGroupId => $userGroup)
		{
			if($userGroupId!=0){
				$userGroups[$userGroupId] = array(
					'label' => $userGroup['title'],
					'value' => $userGroup['user_group_id'],
					'selected' => in_array($userGroup['user_group_id'] , $categoryUserGroups)
				);
			}
		}

		$creditVersion = $codeModel->checkCreditVersion();
		if(!$creditVersion){
			return $this->responseError(new XenForo_Phrase('BRKC_credit_addon_required'));
		}
		$currencies = array();
		if($creditVersion >= 2000000){
			$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
			$actionHandler = XenForo_Application::get('brcActionHandler');
			foreach($currencies AS $currencyId=>$currency){
				if(!$actionHandler->getActionEventsByCurrencyId('BRKC_PurchaseKeyCode',$currency['currency_id'])){
					unset($currencies[$currencyId]);
				}
			}			if(!$currencies){
				return $this->responseError(new XenForo_Phrase('BRKC_you_must_create_credit_event_for_key_code'));
			}
		}else if($creditVersion >= 1000000){

			$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
			$eventObject = XenForo_Application::get('brcEvents');
			foreach($currencies AS $currencyId=>$currency){
				if(!$eventObject->getByCurrency('BRKC_PurchaseKeyCode',$currency['currency_id'])){
					unset($currencies[$currencyId]);
				}
			}
			if(!$currencies){
				return $this->responseError(new XenForo_Phrase('BRKC_you_must_create_credit_event_for_key_code'));
			}
		}else{
			$action = XenForo_Application::get('brcActions')->BRKC_PurchaseKeyCode;
			if(!$action)
			{
				return $this->responseError(new XenForo_Phrase('BRC_this_action_is_not_active_yet'));
			}
			//$currencies = XenForo_Application::get('brcCurrencies');
		}

		$viewParams = array(
			'userGroups' => $userGroups,
			'currencies' => $currencies,
			'category' => $category,
		);

		return $this->responseView('Brivium_KeyCode_ViewAdmin_KeyCode_Category', 'BRKC_category_edit', $viewParams);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$categoryId = $this->_input->filterSingle('code_category_id', XenForo_Input::UINT);
		$dwInput = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'price' => XenForo_Input::UINT,
			'currency_id' => XenForo_Input::UINT,
			'active' => XenForo_Input::UINT,
		));
		$maxCodeOptions = $this->_input->filterSingle('max_code_options', XenForo_Input::ARRAY_SIMPLE);
		if(empty($maxCodeOptions['max_total']) && empty($maxCodeOptions['max_day'])){
			$maxCodeOptions = array();
		}
		$dwInput['max_code_options'] = $maxCodeOptions;
		$dwInput['user_groups'] = $this->_input->filterSingle('user_groups', array(XenForo_Input::UINT, 'array' => true));
		$dwInput['user_groups'] = serialize($dwInput['user_groups']);
		$dw = XenForo_DataWriter::create('Brivium_KeyCode_DataWriter_Category');
		if ($categoryId)
		{
			$dw->setExistingData($categoryId);
		}

		$dw->bulkSet($dwInput);
		$dw->save();

		$redirectType = ($categoryId
			? XenForo_ControllerResponse_Redirect::RESOURCE_CREATED
			: XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED);

		return $this->responseRedirect(
			$redirectType,
			XenForo_Link::buildAdminLink('brkc-categories')
		);
	}

	public function actionDelete()
	{
		$categoryId = $this->_input->filterSingle('code_category_id', XenForo_Input::UINT);

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Brivium_KeyCode_DataWriter_Category');
			$dw->setExistingData($categoryId);
			$dw->delete();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('brkc-categories')
			);
		}
		else // show confirmation dialog
		{
			$codeModel = $this->_getCodeModel();
			$category = $codeModel->getCategoryById($categoryId);
			$viewParams = array(
				'category' => $category
			);

			return $this->responseView('Brivium_KeyCode_ViewAdmin_KeyCode_DeleteCategory', 'BRKC_category_delete', $viewParams);
		}
	}
	/**
	 * Gets the action model.
	 *
	 * @return Brivium_KeyCode_Model_Code
	 */
	protected function _getCodeModel()
	{
		return $this->getModelFromCache('Brivium_KeyCode_Model_Code');
	}
}