<?php

/**
 * Thread prefix controller.
 */
class Brivium_CreResIntegration_ControllerAdmin_ResourceLog extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		if ($this->_input->inRequest('deactive_selected'))
		{
			return $this->responseReroute(__CLASS__, 'deactive-purchased');
		}
		if ($this->_input->inRequest('active_selected'))
		{
			return $this->responseReroute(__CLASS__, 'active-purchased');
		}
		$input = $this->_getFilterParams();
		$dateInput = $this->_input->filter(array(
			'start' => XenForo_Input::DATE_TIME,
			'end' => XenForo_Input::DATE_TIME,
		));
		$purchasedModel = $this->_getResourcePurchasedModel();
		
		$pageParams = array();
		if ($input['order'])
		{
			$pageParams['order'] = $input['order'];
		}
		if ($input['start'])
		{
			$pageParams['start'] = $input['start'];
		}
		if ($input['end'])
		{
			$pageParams['end'] = $input['end'];
		}

		if ($input['resource_id'])
		{
			$pageParams['resource_id'] = $input['resource_id'];
		}
		if ($input['currency_id'])
		{
			$pageParams['currency_id'] = $input['currency_id'];
		}
		$userId = 0;
		if ($input['username'])
		{
			if ($user = $this->getModelFromCache('XenForo_Model_User')->getUserByName($input['username']))
			{
				$userId = $user['user_id'];
				$pageParams['username'] = $input['username'];
			}
			else
			{
				$input['username'] = '';
			}
		}
		$resourceId = $input['resource_id'];
		if ($input['resource_title'])
		{
			if ($resource = $this->getModelFromCache('XenResource_Model_Resource')->getResourceByTitle($input['resource_title']))
			{
				$resourceId = $resource['resource_id'];
				$pageParams['resource_title'] = $input['resource_title'];
			}
			else
			{
				$input['resource_title'] = '';
			}
		}

		$conditions = array(
			'resource_id' => $resourceId,
			'currency_id' => $input['currency_id'],
			'user_id' => $userId,
			'start' => $dateInput['start'],
			'end' => $dateInput['end'],
		);
		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$perPage = 50;

		$fetchOptions = array(
			'page' => $page,
			'perPage' => $perPage,
			'join' =>  Brivium_CreResIntegration_Model_Purchased::FETCH_PURCHASED_FULL
		);
		switch ($input['order'])
		{
			case 'resource_price':
				$fetchOptions['order'] = 'resource_price';
				break;

			case 'purchased_date';
			default:
				$input['order'] = 'purchased_date';
				$fetchOptions['order'] = 'purchased_date';
				break;
		}

		$purchaseds = $purchasedModel->getResourcePurchaseds($conditions, $fetchOptions);
		$purchaseds = $purchasedModel->prepareResourcePurchaseds($purchaseds);
		
		$viewParams = array(
			'currencies' => XenForo_Application::get('brcCurrencies')->getCurrencies(),
			'purchaseds' => $purchaseds,

			'order' => $input['order'],
			'currencyId' => $input['currency_id'],
			'resourceId' => $input['resource_id'],
			'resourceTitle' => $input['resource_title'],
			'username' => $input['username'],
			'start' => $input['start'],
			'end' => $input['end'],

			'datePresets' => XenForo_Helper_Date::getDatePresets(),

			'page' => $page,
			'perPage' => $perPage,
			'pageParams' => $pageParams,
			'total' =>	$purchasedModel->countResourcePurchaseds($conditions)
		);

		return $this->responseView('Brivium_CreResIntegration_ViewAdmin_ResourceLog', 'BRCRI_resource_log', $viewParams);
	}
	protected function _getFilterParams()
	{
		return $this->_input->filter(array(
			'order' => XenForo_Input::STRING,
			'currency_id' => XenForo_Input::UINT,
			'resource_id' => XenForo_Input::UINT,
			'username' => XenForo_Input::STRING,
			'resource_title' => XenForo_Input::STRING,
			'start' => XenForo_Input::STRING,
			'end' => XenForo_Input::STRING
		));
	}
	
	
	public function actionDeactivePurchased()
	{
		$purchasedModel = $this->_getResourcePurchasedModel();
		
		$filterParams = $this->_getFilterParams();

		$purchasedIds = $this->_input->filterSingle('resource_purchased_ids', array(XenForo_Input::UINT, 'array' => true));

		if ($purchasedId = $this->_input->filterSingle('resource_purchased_id', XenForo_Input::UINT))
		{
			$purchasedIds[] = $purchasedId;
		}
		if ($this->isConfirmedPost())
		{
			foreach ($purchasedIds AS $purchasedId)
			{
				$dw = XenForo_DataWriter::create('Brivium_CreResIntegration_DataWriter_Purchased');
				$dw->setExistingData($purchasedId);
				$dw->set('active',0);
				$dw->save();
			}
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('credit-resource-log', null, $filterParams)
			);
		}
		else // show confirmation dialog
		{
			$fetchOptions = array(
				'join' =>  Brivium_CreResIntegration_Model_Purchased::FETCH_PURCHASED_FULL
			);
			$viewParams = array(
				'purchasedIds' => $purchasedIds,
				'filterParams' => $filterParams
			);
			
			if (count($purchasedIds) == 1)
			{
				list($purchasedId) = $purchasedIds;
				$purchaseds = $purchasedModel->getResourcePurchasedById($purchasedId,$fetchOptions);
				if($purchaseds)
				$viewParams['purchased'] = $purchasedModel->prepareResourcePurchased($purchaseds);
			}
			return $this->responseView('Brivium_Credits_ViewAdmin_Credits_DeleteResourcePurchased', 'BRCRI_purchased_deactive', $viewParams);
		}
	}
	
	public function actionActivePurchased()
	{
		$purchasedModel = $this->_getResourcePurchasedModel();
		$filterParams = $this->_getFilterParams();
		$purchasedIds = $this->_input->filterSingle('resource_purchased_ids', array(XenForo_Input::UINT, 'array' => true));
		if ($purchasedId = $this->_input->filterSingle('resource_purchased_id', XenForo_Input::UINT))
		{
			$purchasedIds[] = $purchasedId;
		}
		if ($this->isConfirmedPost())
		{
			foreach ($purchasedIds AS $purchasedId)
			{
				$dw = XenForo_DataWriter::create('Brivium_CreResIntegration_DataWriter_Purchased');
				$dw->setExistingData($purchasedId);
				$dw->set('active',1);
				$dw->save();
			}
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('credit-resource-log', null, $filterParams)
			);
		}
		else // show confirmation dialog
		{
			$fetchOptions = array(
				'join' =>  Brivium_CreResIntegration_Model_Purchased::FETCH_PURCHASED_FULL
			);
			$viewParams = array(
				'purchasedIds' => $purchasedIds,
				'filterParams' => $filterParams
			);
			if (count($purchasedIds) == 1)
			{
				list($purchasedId) = $purchasedIds;
				$purchaseds = $purchasedModel->getResourcePurchasedById($purchasedId,$fetchOptions);
				if($purchaseds)
				$viewParams['purchased'] = $purchasedModel->prepareResourcePurchased($purchaseds);
			}
			return $this->responseView('Brivium_Credits_ViewAdmin_Credits_DeleteResourcePurchased', 'BRCRI_purchased_active', $viewParams);
		}
	}
	
	
	/**
	 * Searches for a resource by the left-most prefix of a name (for auto-complete(.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionSearchName()
	{
		$q = $this->_input->filterSingle('q', XenForo_Input::STRING);

		if ($q !== '')
		{
			$resources = $this->getModelFromCache('XenResource_Model_Resource')->getResources(
				array('title' => array($q , 'r')),
				array('limit' => 10)
			);
		}
		else
		{
			$resources = array();
		}
		$viewParams = array(
			'resources' => $resources
		);
		return $this->responseView(
			'Brivium_CreResIntegration_ViewAdmin_Resource_SearchName',
			'',
			$viewParams
		);
	}
	
	
	/**
	 * @return Brivium_CreResIntegration_Model_Purchased
	 */
	protected function _getResourcePurchasedModel()
	{
		return $this->getModelFromCache('Brivium_CreResIntegration_Model_Purchased');
	}
	/**
	 * @return XenResource_Model_Version
	 */
	protected function _getVersionModel()
	{
		return $this->getModelFromCache('XenResource_Model_Version');
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
	/**
	 * @return XenResource_Model_Category
	 */
	protected function _getCategoryModel()
	{
		return $this->getModelFromCache('XenResource_Model_Category');
	}
}