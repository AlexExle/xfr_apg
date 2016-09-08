<?php

class Brivium_Credits_ControllerPublic_CreditPayment extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex(){
		$viewParams = array();
		return $this->responseView(
			'Brivium_Credits_ViewAdmin_Credits_Payment',
			'BRC_credit_payment',
			$viewParams
		);
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
	protected function _preDispatch($action)
	{
		$this->_assertRegistrationRequired();
		if (!$this->_getCreditModel()->canUseCredits($error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}
	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
	/**
	 * Gets the transaction model.
	 *
	 * @return Brivium_Credits_Model_Transaction
	 */
	protected function _getTransactionModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Transaction');
	}
	/**
	 * Gets the action model.
	 *
	 * @return Brivium_Credits_Model_Action
	 */
	protected function _getActionModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Action');
	}

	/**
	 * Gets the credit pages wrapper.
	 *
	 * @param string $selectedGroup
	 * @param string $selectedLink
	 * @param XenForo_ControllerResponse_View $subView
	 *
	 * @return XenForo_ControllerResponse_View
	 */
	protected function _getWrapper($selectedGroup, $selectedLink, XenForo_ControllerResponse_View $subView)
	{
		$creditHelper = new Brivium_Credits_ControllerHelper_Credit($this);
		return $creditHelper->getWrapper($selectedGroup, $selectedLink, $subView);
	}

}