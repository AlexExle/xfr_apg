<?php

/**
 * @package Brivium_Credits
 */
abstract class Brivium_Credits_ActionHandler_Abstract
{
	/**
	 * Array to cache model objects
	 *
	 * @var array
	 */
	protected static $_modelCache = array();
	protected $_creditModel = null;

	protected $_addOnId = 'Brivium_Credits';
	protected $_addOnTitle = 'Brivium - Credits Premium';
	protected $_editTemplate = 'BRC_action_edit_default';
	protected $_displayOrder = 0;
	protected $_allowMultipleEvent = true;
	protected $_contentRoute = '';
	protected $_contentIdKey = '';
	protected $_extendedClasses = array();

	public function getAction()
	{
		$action = array(
			'action_id'	=> $this->getActionId(),
			'title'	=> $this->getActionTitle(),
			'description'	=> $this->getDescription(),
			'title_phrase'	=> $this->getActionTitlePhrase(),
			'description_phrase'	=> $this->getDescriptionPhrase(),
			'edit_template'	=> $this->getEditTemplate(),
			'addon_id'	=> $this->getAddOnId(),
			'add_on_title'	=> $this->getAddOnTitle(),
			'display_order'	=> $this->getDisplayOrder(),
			'allow_multiple_event'	=> $this->getAllowMultipleEvent(),
			'transaction_complete_alert'	=> $this->getTransactionCompleteAlert(),
			'action_active'	=> $this->isActive(),
		);
		$this->_getAction($action);
		return $action;
	}

	abstract public function getActionId();

	public function getTransactionCompleteAlert()
	{
		return 'BRC_alert_transaction_complete';
	}

	public function getActionTitle()
	{
		return new XenForo_Phrase($this->getActionTitlePhrase());
	}

	abstract public function getActionTitlePhrase();

	public function isActive()
	{
		$disableActions = XenForo_Application::getOptions()->BRC_disabledActions;
		return (!$disableActions || !in_array($this->getActionId(), $disableActions));
	}

	public function getDescription()
	{
		return new XenForo_Phrase($this->getDescriptionPhrase());
	}

	abstract public function getDescriptionPhrase();

	protected function _getAction(&$action)
	{
	}

	protected function _canUseAction()
	{
		return true;
	}

	public function triggerEvent($event, $user, array $triggerData = array(), &$errorString='', &$isBreak=false)
	{
		$action = $this->getAction();
		if(empty($action['action_active']))
		{
			$errorString = new XenForo_Phrase('BRC_this_action_is_not_active_yet');
			return false;
		}
		$userId = $user['user_id'];
 		$amountUpdate = 0;
 		$now = XenForo_Application::$time;
		$times 		= $triggerData['times']?$triggerData['times']:$event['times'];
		$applyMax 	= $triggerData['apply_max']?$triggerData['apply_max']:$event['apply_max'];
		$maxTime 	= $triggerData['max_time']?$triggerData['max_time']:$event['max_time'];
		$moderate 	= $triggerData['moderate']?$triggerData['moderate']:$event['moderate'];
		$userAction 	= $triggerData['user_action'];

		$creditModel = $this->_getCreditModel();

		$allowNegative 		= $triggerData['allow_negative'];
		if(!$allowNegative){
			$allowNegative = !empty($event['allow_negative'])?$event['allow_negative']:false;
		}

		$currencyObj = XenForo_Application::get('brcCurrencies');

		$eventId = $event['event_id'];
		$actionId = $action['action_id'];
		$currency = $currencyObj->get($event['currency_id']);
		// check event is actived
		if(empty($currency['active']))
		{
			$errorString = new XenForo_Phrase('BRC_this_currency_is_not_active_yet');
			return false;
		}
		// check event is actived
		if(empty($event['active']))
		{
			$errorString = new XenForo_Phrase('BRC_this_event_is_not_active_yet');
			return false;
		}
		if(!isset($user[$currency['column']]))
		{
			$errorString = new XenForo_Phrase('BRC_field_x_was_not_recognised',array('field'=>$currency['column']));
			return false;
		}
		if(!$triggerData['ignore_include'] && !$this->canTriggerEvent($event, $user, $triggerData['node_id'])){
			$errorString = new XenForo_Phrase('do_not_have_permission');
			return false;
		}

		$multiplier = $triggerData['multiplier'];
		$this->prepareTriggerData($event, $user, $currency, $triggerData, $errorString);
		if($errorString){
			return false;
		}

		$amountUpdate = $triggerData['amount'];
		if($amountUpdate==0){
			$amountUpdate = $event['amount'];
		}

		if($triggerData['multi_amount']){
			$amountUpdate *= $triggerData['multi_amount'];
		}

		if(!$triggerData['ignore_min_handle'] && $event['extra_min'] && $multiplier < $event['extra_min'] && $event['extra_min_handle']){
			if($event['extra_min_handle']==1){
				$multiplier = 0;
			}else if($event['extra_min_handle']==2){
				$errorString = new XenForo_Phrase('BRC_below_minimum_handling');
				return false;
			}else if($event['extra_min_handle']==3)
			{
				$errorMinimumHandle = $triggerData['errorMinimumHandle'];
				if(!$errorMinimumHandle || !$errorMinimumHandle instanceof XenForo_Phrase){
					$errorMinimumHandle = new XenForo_Phrase('BRC_below_minimum_handling');;
				}else{
					if($event['extra_min']%1==0){
						$event['extra_min'] = intval($event['extra_min']);
					}
					$errorMinimumHandle->setParams(array('min'=>$event['extra_min']));
				}
				if(!$triggerData['is_bulk']){
					throw new XenForo_Exception($errorMinimumHandle, true);
				}else{
					$isBreak = true;
					return false;
				}
			}
		}

		if($event['extra_max'] && $multiplier > $event['extra_max']){
			$multiplier = $event['extra_max'];
		}
		if(!$amountUpdate){
			$errorString = new XenForo_Phrase('BRC_not_valid_amount');
			return false;
		}
		if($multiplier && $multiplier > 0 && $event['multiplier']){
			$amountUpdate = $amountUpdate + $event['multiplier']*$multiplier;
		}

		$amountUpdate = $this->processUpdateAmount($event['currency_id'], $amountUpdate, $user);

		if(!$allowNegative && $amountUpdate < 0 && (($user[$currency['column']] + $amountUpdate) < 0)){

			if(!empty($event['negative_handle']) && $event['negative_handle']=='prevent_action'){
				if(!$triggerData['is_bulk']){
					throw new XenForo_Exception(new XenForo_Phrase('BRC_not_enough_amount', array('amount' => $currencyObj->currencyFormat($amountUpdate, false, $event['currency_id']))), true);
				}else{
					$isBreak = true;
					return false;
				}
			}else{
				$errorString = new XenForo_Phrase('BRC_not_enough_amount',array('amount' => $currencyObj->currencyFormat($amountUpdate,false,$event['currency_id'])));
				$isBreak = true;
				return false;
			}
		}
		if(!$triggerData['ignore_maximum']){
			if($applyMax > 0){
				$startTime = 0;
				if($maxTime > 0){
					$conditions = array(
						'event_id' => $eventId,
						'start' => ($now - $maxTime),
						'user_action_id' => $userAction['user_id'],
					);
					$maxTimes = $creditModel->countEventsTriggerByUser($actionId, $userId, $conditions);
					if($maxTimes >= $applyMax){
						$errorString = new XenForo_Phrase('BRC_you_trigger_x_times_in_period_of_time_for_y', array('time' => $applyMax, 'event'=>$action['title']));
						return false;
					}
				}
			}
			$dayStartTimestamps = XenForo_Locale::getDayStartTimestamps();
			$conditions = array(
				'event_id' => $eventId,
				'start' => $dayStartTimestamps['today'],
				'user_action_id' => $userAction['user_id'],
			);
			if($times > 0 && $times <= $creditModel->countEventsTriggerByUser($actionId, $userId, $conditions)){
				$errorString = new XenForo_Phrase('BRC_you_can_trigger_x_times_per_day_for_y',array('time'=>$times, 'event'=>$action['title']));
				return false;
			}
		}
		$extraData = $triggerData['extraData'];
		$extraData['amount'] = $amountUpdate;
		$extraData['multiAmount'] = $triggerData['multi_amount'];
		$extraData['moderate'] = $moderate;
		$extraData['updateUser'] = $triggerData['updateUser'];
		$alert = false;
		if(empty($triggerData['ignore_permission']) && XenForo_Model_Alert::userReceivesAlert($user, 'credits', $actionId) && $event['alert']){
			$alert = true;
		}
		$this->_addUserTransaction(
			$event['action_id'], $eventId, $user, $triggerData['user_action'],
			$triggerData['content_id'], $triggerData['content_type'], $now,
			$amountUpdate, $currency, $multiplier,
			$triggerData['message'], $moderate, $triggerData['transaction_state'], $extraData,
			$alert, $triggerData['updateUser']
		);
		return true;
	}

	protected function _addUserTransaction($actionId, $eventId, $user, $triggerUser,
		$contentId, $contentType, $transactionDate,
		$amount, $currency, $multiplier, $message,
		$moderate, $transactionState, $extraData,$alert, $updateUser = false)
	{
		$this->_getCreditModel()->addUserTransaction($actionId, $eventId, $user, $triggerUser,
		$contentId, $contentType, $transactionDate,
		$amount, $currency, $multiplier, $message,
		$moderate, $transactionState, $extraData,$alert, $updateUser
		);
	}

	public function prepareTriggerData($event, &$user, $currency, &$triggerData, &$errorString)
	{
		return true;
	}

	public function processUpdateAmount($currencyId, $amount, &$user)
	{
 		return $amount;
	}

	public function getAllowMultipleEvent()
	{
 		return $this->_allowMultipleEvent;
	}

	public function getAddOnId()
	{
 		return $this->_addOnId;
	}

	public function getAddOnTitle()
	{
 		return $this->_addOnTitle;
	}

	public function getEditTemplate()
	{
 		return $this->_editTemplate;
	}

	public function getDisplayOrder()
	{
 		return $this->_displayOrder;
	}

	public function canUseAction(&$errorString='')
	{
		$addOns = XenForo_Application::get('addOns');
		$addOnId = $this->getAddOnId();
		if(!empty($addOns[$addOnId])){
			return $this->_canUseAction();
		}
		return false;
	}

	public function validateActionParams(&$paymentParams, &$errorString='')
	{
		return true;
	}

	public function preparedOption(&$preparedOption)
	{
		return true;
	}

	public function validate(array &$options)
	{
		return true;
	}

	public function canTriggerEvent(array $event, array $user, $nodeId = 0)
	{
		if(empty($user['user_id'])){
			$user = XenForo_Visitor::getInstance()->toArray();
		}

		$check = $event['event_id'];
		if(empty($event['currency_id'])){
			return false;
		}

		$currency = XenForo_Application::get('brcCurrencies')->get($event['currency_id']);
		if(empty($event['active']) || empty($currency['active']))
		{
			return false;
		}
		if(empty($event['user_groups']) || $event['user_groups']==array(0=>0)){
			$event['user_groups'] = array();
		}
		if(empty($currency['user_groups']) || $currency['user_groups']==array(0=>0)){
			$currency['user_groups'] = array();
		}
		if(empty($event['forums']) || $event['forums']==array(0=>0)){
			$event['forums'] = array();
		}

		$includeGroups = array();
		if($currency['user_groups'] && !$event['user_groups']){
			$includeGroups = $currency['user_groups'];
		}else if($event['user_groups'] && !$currency['user_groups']){
			$includeGroups = $event['user_groups'];
		}else if(!empty($event['user_groups']) && !empty($currency['user_groups'])){
			$includeGroups = array_intersect($event['user_groups'], $currency['user_groups']);
			if(!$includeGroups){
				return false;
			}
		}

		$includeForums = $event['forums'];

		if(empty($includeGroups) && empty($includeForums)){
			return $check;
		}
		if(!empty($includeGroups)){
			$check = false;
			$inGroups = '';
			if(isset($user['user_group_id'])){
				$inGroups = $user['user_group_id'];
			}

			if (!empty($user['secondary_group_ids']))
			{
				$inGroups .= ','.$user['secondary_group_ids'];
			}

			$groupCheck = explode(',', $inGroups);

			unset($inGroups);

			foreach ($groupCheck AS $groupId)
			{
				if (in_array($groupId, $includeGroups))
				{
					$check = $event['event_id'];
					break;
				}
			}
		}
		if($check && !empty($includeForums) && $nodeId && !in_array($nodeId, $includeForums)){
			$check = false;
		}
		return $check;
	}

	/**
	 * Fetches a model object from the local cache
	 *
	 * @param string $modelName
	 *
	 * @return XenForo_Model
	 */
	public function getModelFromCache($modelName)
	{
		if (!isset(self::$_modelCache[$modelName]))
		{
			self::$_modelCache[$modelName] = XenForo_Model::create($modelName);
		}

		return self::$_modelCache[$modelName];
	}

	public function getDefaultEvent()
	{
		$event = array(
			'event_id' => '',
			'forums' => array(),
			'user_groups' => array(),
			'action_id' => $this->getActionId(),
			'currency_id' => 0,
			'active' => 1,
			'alert' => 1,
			'display_order' => 1,
			'times' => 0,
			'extra_min_handle' => 0,
			'allow_negative' => 1,
			'negative_handle' => '',
			'extra_data' => array(),
		);
		return $this->_getDefaultEvent($event);
	}

	protected function _getDefaultEvent($event)
	{
		return $event;
	}

	protected function _prepareEvent(&$event = array())
	{
		return $event;
	}

	protected function _prepareEventEditParams(&$event, $viewParams = array())
	{
		$nodeModel = XenForo_Model::create('XenForo_Model_Node');
		if(empty($event['forums'])){
			$event['forums'] = array();
		}
		$forums = $nodeModel->getNodeOptionsArray($nodeModel->getAllNodes(),0, sprintf('(%s)', new XenForo_Phrase('all_forums')));
		foreach ($forums AS &$node)
		{
			if (!empty($node['node_type_id']) && $node['node_type_id'] != 'Forum')
			{
				$node['disabled'] = 'disabled';
			}
			unset($node['node_type_id']);
			$node['selected'] = in_array($node['value'] , $event['forums']);
		}
		$viewParams['forums'] = $forums;
		return $viewParams;
	}

	public function prepareEventEditParams(&$event, $viewParams = array())
	{
		$listUserGroups = XenForo_Model::create('XenForo_Model_UserGroup')->getAllUserGroups();
		$userGroups[0] = array(
			'label' =>  sprintf('(%s)', new XenForo_Phrase('all_user_groups')),
			'value' => 0,
			'selected' => in_array(0 , $event['user_groups'])
		);
		foreach ($listUserGroups AS $userGroupId => $userGroup)
		{
			if($userGroupId != 0){
				$userGroups[$userGroupId] = array(
					'label' => $userGroup['title'],
					'value' => $userGroup['user_group_id'],
					'selected' => in_array($userGroup['user_group_id'] , $event['user_groups'])
				);
			}
		}
		$viewParams['userGroups'] = $userGroups;
		$addOns = XenForo_Application::get('addOns');
		$viewParams['creditVersion'] = !empty($addOns['Brivium_Credits'])?$addOns['Brivium_Credits']:0;
		$viewParams = $this->_prepareEventEditParams($event, $viewParams);
		return $viewParams;
	}

	protected function _verifyEvent($event, Brivium_Credits_DataWriter_Event $eventWriter)
	{
		return true;
	}

	public function verifyEvent($event, Brivium_Credits_DataWriter_Event $eventWriter)
	{
		$this->_verifyEvent($event, $eventWriter);
		return true;
	}

	public function prepareEvent($event = array())
	{
		if(empty($event))return array();

		$data1 = @unserialize($event['forums']);
		if ($event['forums'] && $data1 !== false)
		{
			$event['forums'] = @unserialize($event['forums']);
		}else{
			$event['forums'] = array();
		}
		$data2 = @unserialize($event['user_groups']);
		if ($event['user_groups'] && $data2 !== false)
		{
			$event['user_groups'] = @unserialize($event['user_groups']);
		}else{
			$event['user_groups'] = array();
		}
		if(!empty($event['extra_data'])){
			$data3 = @unserialize($event['extra_data']);
			if (!empty($event['extra_data']) && $data3 !== false)
			{
				$event['extra_data'] = @unserialize($event['extra_data']);
			}else{
				$event['extra_data'] = array();
			}
		}else{
			$event['extra_data'] = array();
		}


		$event = $this->_prepareEvent($event);
		$action = $this->getAction();
		$event = array_merge($event,$action);
		return $event;
	}

	public function prepareTransaction(array $transaction)
	{
		if(!$transaction) return array();

		$actionNamePhrase = $this->getActionTitlePhrase();

		$transaction['action'] = new XenForo_Phrase($actionNamePhrase);
		if ($transaction['extra_data'] && !(@unserialize($transaction['extra_data']) === false && $transaction['extra_data'] != serialize(false)))
		{
			$transaction['extraData'] = @unserialize($transaction['extra_data']);
			if (!is_array($transaction['extraData']))
			{
				$transaction['extraData'] = array();
			}
			if(!empty($transaction['extraData']['reverted'])){
				$transaction['action'] = new XenForo_Phrase($actionNamePhrase . '_reverted');
			}
		}else{
			$transaction['extraData'] = array();
		}

		if(!$transaction['user_action_id']){
			$transaction['user_action_name'] = new XenForo_Phrase('BRC_system');
		}

		$type = 'earned';
		if ($transaction['amount'] < 0)
		{
			$type = 'spent';
		}

		$transaction['amount_phrase'] = new XenForo_Phrase('BRC_transaction_' . $type, array('amount'=>XenForo_Application::get('brcCurrencies')->currencyFormat($transaction['amount'], false, $transaction['currency_id'])));

		$transaction['content_link'] = $this->getContentLink($transaction, $transaction['extraData']);

		return $transaction;
	}

	public function getRevertedPhrase()
	{
		return new XenForo_Phrase($this->getActionTitlePhrase() . '_reverted');
	}

	public function getContentLink(array $contentData, array $extraParams = array())
	{
		if ($this->_contentRoute)
		{
			$data = $this->getContentDataFromContentId($contentData);
			if(!$this->_contentIdKey || $data){
				return XenForo_Link::buildPublicLink($this->_contentRoute, $data);
			}
		}
		return false;
	}

	public function getContentDataFromContentId($contentData)
	{
		if(!empty($contentData['content_id'])){
			return array($this->_contentIdKey => $contentData['content_id']);
		}
		return array();
	}

	final public function getExtendedClasses($listenerClasses)
	{
		if($this->isActive() && $this->_extendedClasses){
			foreach($this->_extendedClasses AS $classType=>$classes){
				if(!isset($listenerClasses[$classType])){
					$listenerClasses[$classType] = array();
				}
				foreach($classes AS $className=>$extend){
					if(!isset($listenerClasses[$classType][$className])){
						$listenerClasses[$classType][$className] = array();
					}
					$listenerClasses[$classType][$className][$extend] = $extend;
				}
			}
		}
		return $listenerClasses;
	}

	/**
	 * Gets the action model.
	 *
	 * @return Brivium_Credits_Model_Credit
	 */
	protected function _getCreditModel()
	{
		return is_null($this->_creditModel)?$this->getModelFromCache('Brivium_Credits_Model_Credit'):$this->_creditModel;
	}

	public function setCreditModel($creditModel)
	{
		$this->_creditModel = $creditModel;
	}
}