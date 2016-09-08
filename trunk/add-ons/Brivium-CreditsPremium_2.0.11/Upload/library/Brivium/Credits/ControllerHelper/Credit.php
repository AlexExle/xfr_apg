<?php

class Brivium_Credits_ControllerHelper_Credit extends XenForo_ControllerHelper_Abstract
{
	/**
	 * The current browsing user.
	 *
	 * @var XenForo_Visitor
	 */
	protected $_visitor;
	protected $_currencyObj;
	protected $_actionObj;

	/**
	 * Additional constructor setup behavior.
	 */
	protected function _constructSetup()
	{
		$this->_visitor = XenForo_Visitor::getInstance()->toArray();
		$this->_currencyObj = XenForo_Application::get('brcCurrencies');
		$this->_actionObj = XenForo_Application::get('brcActionHandler');
	}
	public function assertCurrenciesValidAndViewable($actionId, $user = array(), $nodeId = 0)
	{
		if(!$actionId){
			return array();
		}
		$actionHandler = $this->_actionObj->getActionHandler($actionId);
		if(!$actionHandler){
			return array();
		}
		$currencies = $this->_currencyObj->getCurrencies();

		foreach($currencies AS $currencyId=>$currency){
			$events = $this->_actionObj->getActionEvents($actionId, array('currency_id' => $currency['currency_id']));
			if(!$this->_actionObj->checkTriggerActionEvents($events, $user, $nodeId)){
				unset($currencies[$currencyId]);
			}
		}
		return $currencies;
	}

	public function assertEventAndCurrencyValidAndViewable($actionId, $currencyId, $user = array(), $nodeId = 0)
	{
		if(!$currencyId){
			throw $this->_controller->getNoPermissionResponseException();
		}
		$currency = $this->_currencyObj->$currencyId;

		$events = $this->_actionObj->getActionEvents($actionId, array('currency_id' => $currency['currency_id']));
		$allowEventId = $this->_actionObj->checkTriggerActionEvents($events, $user, $nodeId);
		if(!$allowEventId || !isset($events[$allowEventId])){
			throw $this->_controller->getNoPermissionResponseException();
		}
		$event = $events[$allowEventId];


		if(!isset($this->_visitor[$currency['column']])){
			throw $this->_controller->getErrorOrNoPermissionResponseException(new XenForo_Phrase('BRC_field_x_was_not_recognised',array('field'=>$currency['column'])));
		}
		return array($event, $currency);
	}


	public function getWrapper($selectedGroup, $selectedLink, XenForo_ControllerResponse_View $subView)
	{
		$currencyDisplay = XenForo_Application::get('options')->get('BRC_currencyDisplay');
		if(isset($currencyDisplay[0]) && ($currencyDisplay[0]==''||$currencyDisplay[0]==0)){
			$currencyDisplay = array();
		}
		$creditModel = $this->_controller->getModelFromCache('Brivium_Credits_Model_Credit');

		$canExchange = $creditModel->canExchange();

		$viewParams = array(
			'currencyDisplay' => $currencyDisplay,
			'selectedGroup' => $selectedGroup,
			'selectedLink' => $selectedLink,
			'canExchange'=> $canExchange,
			'selectedKey' => "$selectedGroup/$selectedLink",
			'currencies' => XenForo_Application::get('brcCurrencies')->getCurrencies(),
			'canWithdraw' => $this->_actionObj->canTriggerActionEvents('withdraw'),
			'canTransfer' => $this->_actionObj->canTriggerActionEvents('transfer'),
			'canViewRanking' => $creditModel->canViewRanking(),
			'canStealCredits' => $creditModel->canStealCredits(),
		);

		$wrapper = $this->_controller->responseView('Brivium_Credits_ViewPublic_Credit_Wrapper', 'BRC_credit_wrapper', $viewParams);
		$wrapper->subView = $subView;

		return $wrapper;
	}

	public static function wrap(XenForo_Controller $controller, $selectedGroup, $selectedLink, XenForo_ControllerResponse_View $subView)
	{
		$helper = new self($controller);
		return $helper->getWrapper($selectedGroup, $selectedLink, $subView);
	}
}