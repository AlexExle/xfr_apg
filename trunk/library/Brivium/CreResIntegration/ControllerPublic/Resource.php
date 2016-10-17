<?php

class Brivium_CreResIntegration_ControllerPublic_Resource extends XFCP_Brivium_CreResIntegration_ControllerPublic_Resource
{
	protected function _getResourceAddOrEditResponse(array $resource, array $category, array $attachments = array())
	{
		$response = parent::_getResourceAddOrEditResponse($resource, $category, $attachments);
		if(!empty($response->params) && !empty($resource['resource_category_id'])){
			$excludeCategories = XenForo_Application::get('options')->BRRCI_excludeCategories;
			if($excludeCategories){
				if (in_array($resource['resource_category_id'], $excludeCategories))
				{
					return $response;
				}
			}
			$currencies = $this->_getCreditHelper()->assertCurrenciesValidAndViewable('ResourcePurchased');

			if($currencies){
				$response->params['brcCurrencies'] = $currencies;
			}
		}
		return $response;
	}

	protected function _getResourceViewWrapper($selectedTab, array $resource, array $category,
		XenForo_ControllerResponse_View $subView
	)
	{
		$response = parent::_getResourceViewWrapper($selectedTab, $resource, $category, $subView);
		if(!empty($response->params['resource']['resource_id']) && !empty($response->params['category'])){
			$resource = $response->params['resource'];
			$category = $response->params['category'];
			if(!$response->params['canByPassDownload'] = $this->_getResourceModel()->canDownloadWithoutPurchase($resource, $category)){
				$response->params['resource']['purchased'] = $this->_getResourcePurchasedModel()->checkResourcePurchased($resource['resource_id'], XenForo_Visitor::getUserId());
			}
		}
		return $response;
	}

	public function actionPurchased()
	{
		$this->_assertRegistrationRequired();
		$userId = XenForo_Visitor::getInstance()->getUserId();

		$resourceModel = $this->_getResourceModel();
		$categoryModel = $this->_getCategoryModel();

		$conditions = array('purchased_user_id' => $userId);
		$conditions += $categoryModel->getPermissionBasedFetchConditions();

		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$perPage = XenForo_Application::get('options')->resourcesPerPage;

		$visitor = XenForo_Visitor::getInstance();

		$categories = $categoryModel->getViewableCategories();
		$conditions['resource_category_id'] = array_keys($categories);

		$aggregate = $resourceModel->getAggregateResourceData($conditions);
		if (!$aggregate['total_resources'])
		{
			return $this->responseError(new XenForo_Phrase('BRCRI_you_did_not_purchase_any_resource'));
		}

		$resources = $resourceModel->getResources(
			$conditions,
			array(
				'join' => XenResource_Model_Resource::FETCH_CATEGORY |
					XenResource_Model_Resource::FETCH_VERSION |
					XenResource_Model_Resource::FETCH_USER,
				'permissionCombinationId' => $visitor['permission_combination_id'],
				'order' => 'last_update',
				'direction' => 'desc',
				'page' => $page,
				'perPage' => $perPage,
				'BRCRI_join' => true,
			)
		);

		$this->_getCategoryModel()->bulkSetCategoryPermCache(
			$visitor['permission_combination_id'], $resources, 'category_permission_cache'
		);

		foreach ($resources AS $key => $resource)
		{
			if (!$resourceModel->canViewResourceAndContainer($resource, $resource))
			{
				unset($resources[$key]);
			}
		}

		$resources = $resourceModel->prepareResources($resources);
		$inlineModOptions = $resourceModel->getInlineModOptionsForResources($resources);

		$viewParams = array(
			'resources' => $resources,
			'inlineModOptions' => $inlineModOptions,

			'page' => $page,
			'perPage' => $perPage,

			'aggregate' => $aggregate,

			'ratingAvg' => $resourceModel->getRatingAverage(
				$aggregate['rating_sum'], $aggregate['rating_count']
			),

			'fromProfile' => false
		);

		return $this->responseView('XenResource_ViewPublic_Author_View', 'BRCRI_resource_purchased_list', $viewParams);
	}

	public function actionPurchase()
	{
		$this->_assertRegistrationRequired();


		$addOns = XenForo_Application::get('addOns');
		if(!empty($addOns['Brivium_Credits']) && $addOns['Brivium_Credits'] >= 1000000){
			$creditVersion = $addOns['Brivium_Credits'];
		}else{
			return $this->responseError(new XenForo_Phrase('BRCRI_credit_premium_addon_required'));
		}
		$fetchOptions = array();
		list($resource, $category) = $this->_getResourceHelper()->assertResourceValidAndViewable(null, $fetchOptions);
		if ($resource['is_fileless'])
		{
			return $this->responseError(new XenForo_Phrase('fileless_resources_cannot_be_downloaded'));
		}

		$versionModel = $this->_getVersionModel();

		$versionId = $this->_input->filterSingle('version', XenForo_Input::UINT);
		$version = $versionModel->getVersionById($versionId, array(
			'join' => XenResource_Model_Version::FETCH_FILE
		));

		if (!$version || $version['resource_id'] != $resource['resource_id'])
		{
			return $this->responseNoPermission();
		}
		$GLOBALS['BRCRI_purchaseProcess'] = true;
		if (!$versionModel->canDownloadVersion($version, $resource, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
		unset($GLOBALS['BRCRI_purchaseProcess']);
		$visitor = XenForo_Visitor::getInstance()->toArray();
		$visitorId = $visitor['user_id'];
		$purchasedModel = $this->_getResourcePurchasedModel();

		if($resource['credit_price']< 0 || $visitorId==$resource['user_id'] || !empty($resource['is_fileless'])){
			return $this->responseError(new XenForo_Phrase('BRCRI_cant_purchase_this_resource'));
		}

		if($purchasedModel->checkResourcePurchased($resource['resource_id'], $visitorId)){
			return $this->responseError(new XenForo_Phrase('BRCRI_already_purchased_this_resource'));
		}
		if($this->_getResourceModel()->canDownloadWithoutPurchase($resource, $category)){
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				XenForo_Link::buildPublicLink('resources', $resource)
			);
		}

		$currencyId = $resource['brcri_currency_id'];

		$creditModel = $this->_getCreditModel();
		list($event, $currency) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('ResourcePurchased',$currencyId);
		$currencyObj = XenForo_Application::get('brcCurrencies');

		if($creditVersion >= 2000000){
			$actionObj = XenForo_Application::get('brcActionHandler');
			$events = $actionObj->getActionEvents('ResourceGetPurchased', array('currency_id' => $currency['currency_id']));
			$allowEventId = $actionObj->checkTriggerActionEvents($events, $resource);
			$eventTax = array();
			if($allowEventId && isset($events[$allowEventId])){
				$eventTax = $events[$allowEventId];
			}
		}else{
			$eventTax = XenForo_Application::get('brcEvents')->getByCurrency('ResourceGetPurchased',$currencyId);

			if($eventTax && $creditModel->requireInclude($eventTax, $resource)){
				$eventTax = $eventTax;
			}else{
				$eventTax = array();
			}
		}

		if($eventTax){
			list($userTax, $userActionTax) = $creditModel->processTax($resource['credit_price'], $eventTax);
		}else{
			$userTax = 0;
			$userActionTax = 0;
		}
		$userTaxedAmount = $resource['credit_price'] + $userTax ;
		$remain = $visitor[$currency['column']] - $userTaxedAmount;

		if ( $remain < 0) {
			return $this->responseError(new XenForo_Phrase('BRC_not_enough_amount',array('amount' => $currencyObj->currencyFormat($userTaxedAmount,false,$currencyId))));
		}

		if ($this->isConfirmedPost())
		{
			if (!$purchasedModel->purchaseResource($resource, $category, $visitor, $event, $userTax, $userActionTax, $error))
			{
				return $this->responseError($error);
			}
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				XenForo_Link::buildPublicLink('resources', $resource)
			);
		}else{
			$viewParams = array(
				'category' => $category,
				'resource' => $resource,
				'remain' => $remain,
			);
			return $this->responseView('Brivium_CreResIntegration_ViewPublic_ResourceDownload','BRCRI_purchase_confirm',$viewParams);
		}
	}

	public function actionDownload()
	{
		$userId = XenForo_Visitor::getUserId();
		$versionId = $this->_input->filterSingle('version', XenForo_Input::UINT);
		$fetchOptions = array(
			'watchUserId' => $userId
		);
		list($resource, $category) = $this->_getResourceHelper()->assertResourceValidAndViewable(null, $fetchOptions);
		$purchasedModel = $this->_getResourcePurchasedModel();
		if(!$this->_getResourceModel()->canDownloadWithoutPurchase($resource, $category)){
			if($resource['credit_price']!=0 && $userId!=$resource['user_id'] && empty($resource['is_fileless']) && !$purchasedModel->checkResourcePurchased($resource['resource_id'], $userId)){
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
					XenForo_Link::buildPublicLink('resources/purchase', $resource,array('version'=>$versionId))
				);
			}
		}

		return parent::actionDownload();
	}

	public function actionSave()
	{
		$resourceCategoryId = $this->_input->filterSingle('resource_category_id', XenForo_Input::UINT);
		$excludeCategories = XenForo_Application::get('options')->BRRCI_excludeCategories;
		if(!$excludeCategories || !in_array($resourceCategoryId, $excludeCategories)){
			$GLOBALS['BRCRI_ControllerPublic_Resource'] = $this;
		}
		return parent::actionSave();;
	}

	protected function _getResourceListFetchOptions()
	{
		$fetchOptions = parent::_getResourceListFetchOptions();
		$fetchOptions['purchaseUserId']  = XenForo_Visitor::getUserId();
		return $fetchOptions;
	}
	protected function _getCreditHelper()
	{
		return $this->getHelper('Brivium_Credits_ControllerHelper_Credit');
	}
	/**
	 * Gets the action model.
	 *
	 * @return Brivium_Credits_Model_Credit
	 */
	protected function _getCreditModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Credit');
	}
	protected function _getResourcePurchasedModel()
	{
		return $this->getModelFromCache('Brivium_CreResIntegration_Model_Purchased');
	}

	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}