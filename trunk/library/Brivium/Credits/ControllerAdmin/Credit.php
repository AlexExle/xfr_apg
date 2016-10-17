<?php

class Brivium_Credits_ControllerAdmin_Credit extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('BRC_index');
	}

	public function actionIndex()
	{
		$limit = XenForo_Application::get('options')->BRC_memberPerTop;
		$userModel = $this->_getUserModel();
		$transactionModel = $this->_getTransactionModel();
		$stastModel = $this->_getCreditStastModel();

		$criteria = array(
			'user_state' => 'valid',
			'is_banned' => 0
		);
		$brcCurrencies = XenForo_Application::get('brcCurrencies');
		$currencies = $brcCurrencies->getCurrencies();
		$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);
		$actionId 	= $this->_input->filterSingle('action_id', XenForo_Input::STRING);

		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();

		$action = array();
		if($actionId){
			if(!empty($actions[$actionId])){
				$action = $actions[$actionId];
			}else{
				return $this->responseError(new XenForo_Phrase('BRC_requested_action_not_found'));
			}
		}

		$currency = array();
		if($currencyId){
			$currency = $brcCurrencies->$currencyId;
		}else if($currencies){
			$currency = reset($currencies);
		}
		if(!$currency){
			return $this->responseError(new XenForo_Phrase('BRC_requested_currency_not_found'));
		}
		$currencyId = $currency['currency_id'];
		$fetchOptions = array(
			'limit' => $limit,
			'order' => $currency['column'],
		);
		// richest user
		$fetchOptions['direction'] = 'desc';
		$richest = $userModel->getUsers($criteria, $fetchOptions);

		// poorest user
		$fetchOptions['direction'] = 'asc';
		$poorest = $userModel->getUsers($criteria, $fetchOptions);

		$boardTotals = $this->getModelFromCache('XenForo_Model_DataRegistry')->get('boardTotals');
		if (!$boardTotals)
		{
			$boardTotals = $this->getModelFromCache('XenForo_Model_Counters')->rebuildBoardTotalsCounter();
		}

		$creditModel = $this->_getCreditModel();
		$totalCredits 	= $creditModel->totalCredits($currency['column']);
		$totalUsers 	= $boardTotals['users'];

		$conditions  = array('currency_id' => $currencyId);
		$statisticRecord = $stastModel->getStatisticRecord($actionId, $currencyId);
		$firstDay = $stastModel->getFirstStatisticDate($actionId, $currencyId);
		$todayStatisticRecord = $stastModel->getStatisticRecord($actionId, $currencyId,'daily');

		$earnedPerday = 0;
		$spentPerday = 0;
		if($statisticRecord && $firstDay){
			$day = (XenForo_Application::$time - $firstDay);
			if($day <= 86400){
				$day = 1;
			}else{
				$day = ($day - ($day%86400))/86400;
			}
			$earnedPerday = $statisticRecord['total_earn'] / $day;
			$spentPerday = $statisticRecord['total_spend'] / $day;
		}

		$viewParams = array(
			'action' 			=> $action,
			'actionId' 			=> $actionId,
			'actions' 			=> $actions,
			'currencies'   		=> $currencies,
			'currency'   		=> $currency,

			'totalCredits'  	=> $totalCredits,
			'totalUsers'   		=> $totalUsers,

			'earnedPerday'  	=> $earnedPerday,
			'spentPerday'   	=> $spentPerday,

			'statisticRecord'   => $statisticRecord,
			'todayStatisticRecord' => $todayStatisticRecord,
			'firstDay'  		 => $firstDay,

			'richest'  		 	=> $richest,
			'poorest'   		=> $poorest,
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_Index', 'BRC_credits_index', $viewParams);
	}

	public function actionTopUsers()
	{
		$transactionModel = $this->_getTransactionModel();
		$brcCurrencies = XenForo_Application::get('brcCurrencies');
		$currencies = $brcCurrencies->getCurrencies();

		$input = $this->_getFilterParams();

		$dateInput = $this->_input->filter(array(
			'start' => XenForo_Input::DATE_TIME,
			'end' => XenForo_Input::DATE_TIME,
		));

		$transactionModel = $this->_getTransactionModel();

		$moderate = $this->_input->filterSingle('moderate', XenForo_Input::UINT);

		$pageParams = array();
		if ($input['top_type'])
		{
			$pageParams['top_type'] = $input['top_type'];
		}
		if ($input['start'])
		{
			$pageParams['start'] = $input['start'];
		}
		if ($input['end'])
		{
			$pageParams['end'] = $input['end'];
		}
		if ($input['action_id'])
		{
			$pageParams['action_id'] = $input['action_id'];
		}
		if ($input['currency_id'])
		{
			$pageParams['currency_id'] = $input['currency_id'];
		}

		$currencyId = $input['currency_id'];
		$actionId 	= $input['action_id'];
		$topType 	= $input['top_type'];

		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();

		$action = array();
		if($actionId){
			if(!empty($actions[$actionId])){
				$action = $actions[$actionId];
			}else{
				return $this->responseError(new XenForo_Phrase('BRC_requested_action_not_found'));
			}
		}

		$currency = array();
		if($currencyId){
			$currency = $brcCurrencies->$currencyId;
		}else if($currencies){
			$currency = reset($currencies);
		}
		if(!$currency){
			return $this->responseError(new XenForo_Phrase('BRC_requested_currency_not_found'));
		}
		$currencyId = $currency['currency_id'];
		$conditions = array(
			'action_id' => $actionId,
			'currency_id' => $currencyId,
			'start' => $dateInput['start'],
			'end' => $dateInput['end'],
		);
		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$perPage = 50;

		$fetchOptions = array(
			'perPage' => $perPage,
			'page' => $page,
		);
		if($topType=='earn' || !$topType){
			$topType = 'earn';
			$conditions['amount'] = array('>', 0);

			$fetchOptions['orderDirection'] = 'DESC';
			$topUsers = $transactionModel->getTopTransactions($conditions, $fetchOptions);
		}else{
			$conditions['amount'] = array('<', 0);
		}
		$topUsers = $transactionModel->getTopTransactions($conditions, $fetchOptions);
		$totalCredits = 0;
		foreach($topUsers AS $topUser){
			$totalCredits += $topUser['credits'];
		}

		$total = $transactionModel->countTopTransactions($conditions);
		$linkParams = array(
			'currency_id' => $currency['currency_id'],
			'action_id' => $actionId,
			'top_type' => $topType,
		);

		$viewParams = array(
			'currency' 		=> $currency,
			'currencies' 	=> $currencies,
			'topUsers' 		=> $topUsers,
			'totalCredits' 		=> $totalCredits,
			'actionId' 		=> $actionId,
			'actions' 		=> $actions,
			'topType' 		=> $topType,

			'start' => $input['start'],
			'end' => $input['end'],

			'datePresets' => XenForo_Helper_Date::getDatePresets(),

			'linkParams' 	=> $linkParams,
			'page' 			=> $page,
			'perPage' 	=> $perPage,
			'total' 	=> $total,
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_Index', 'BRC_credit_top_list', $viewParams);
	}

	public function actionListActions()
	{
		$this->assertAdminPermission('BRC_action');

		$actionHandler = XenForo_Application::get('brcActionHandler');
		$actions = $actionHandler->getActions();
		$events = $this->_getEventModel()->getAllEvents();
		$eventAction = array();
		foreach($events AS $event){
			if(!empty($event['action_id'])){
				if(!isset($eventAction[$event['action_id']])){
					$eventAction[$event['action_id']] = 0;
				}
				$eventAction[$event['action_id']] += 1;
			}
		}
		foreach($actions AS $actionId=>&$action){
			$action['event_count'] = !empty($eventAction[$actionId])?$eventAction[$actionId]:0;
		}
		$viewParams = array(
			'actions' => $actions,
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_ListActions', 'BRC_action_list', $viewParams);
	}

	public function actionActionToggle()
	{
		$this->_assertPostOnly();

		$disabledActions = $this->_input->filterSingle('disabledActions', XenForo_Input::ARRAY_SIMPLE);


		$this->getModelFromCache('XenForo_Model_Option')->updateOptions(
			array('BRC_disabledActions' => $disabledActions
		));

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('credits/list-actions')
		);
	}

	public function actionUserCredits()
	{
		$criteria = array();

		$filter = $this->_input->filterSingle('_filter', XenForo_Input::ARRAY_SIMPLE);
		if ($filter && isset($filter['value']))
		{
			$criteria['username2'] = array($filter['value'], empty($filter['prefix']) ? 'lr' : 'r');
			$filterView = true;
		}
		else
		{
			$filterView = false;
		}
		$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
		$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);
		$currency = array();
		if($currencyId){
			$currency = XenForo_Application::get('brcCurrencies')->$currencyId;
		}else if($currencies){
			$currency = reset($currencies);
		}
		if(empty($currency))return $this->responseError(new XenForo_Phrase('BRC_requested_currency_not_found'));

		$order = $currency['column'];
		$direction = $this->_input->filterSingle('direction', XenForo_Input::STRING);

		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$usersPerPage = 20;

		$fetchOptions = array(
			'perPage' => $usersPerPage,
			'page' => $page,

			'order' => $order,
			'direction' => $direction
		);

		$userModel = $this->_getUserModel();
		foreach (array('username', 'username2', 'email') AS $field)
		{
			if (isset($criteria[$field]) && is_string($criteria[$field]))
			{
				$criteria[$field] = trim($criteria[$field]);
			}
		}

		$totalUsers = $userModel->countUsers($criteria);
		if (!$totalUsers)
		{
			return $this->responseError(new XenForo_Phrase('no_users_matched_specified_criteria'));
		}

		$users = $userModel->getUsers($criteria, $fetchOptions);
		$orderDirectionEx = ($direction == 'desc' ? 'asc' : 'desc');
		$viewParams = array(
			'currency' => $currency,
			'currencies' => $currencies,
			'users' => $users,
			'totalUsers' => $totalUsers,
			'orderDirectionEx' => $orderDirectionEx,
			'order' => $order,
			'direction' => $direction,

			'linkParams' => array('criteria' => $criteria, 'order' => $order, 'direction' => $direction, 'currency_id' => $currency['currency_id']),
			'linkParamsSelect' => array('criteria' => $criteria, 'order' => $order, 'direction' => $direction),
			'page' => $page,
			'usersPerPage' => $usersPerPage,

			'filterView' => $filterView,
			'filterMore' => ($filterView && $totalUsers > $usersPerPage)
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_User_List', 'BRC_user_list', $viewParams);
	}

	/*========================= Transfer ================================*/
	public function actionTransfer()
	{
		$actionObj = XenForo_Application::get('brcActionHandler');
		$actionHandler = $actionObj->getActionHandler('transfer');
		if(!$actionHandler){
			return $this->responseError(new XenForo_Phrase('BRC_no_events_have_been_created_for_action_yet'));
		}
		$creditModel = $this->_getCreditModel();
		$data = $this->_input->filter(array(
			'receiver' => XenForo_Input::STRING,
			'currency_id' => XenForo_Input::UINT,
		));
		$type = 'user';
		$from = 'self';
		$listUserGroups = XenForo_Model::create('XenForo_Model_UserGroup')->getAllUserGroups();
		foreach ($listUserGroups AS $userGroupId => $userGroup)
		{
			if($userGroupId!=0)
			$userGroups[$userGroupId] = array(
				'label' => $userGroup['title'],
				'value' => $userGroup['user_group_id'],
			);
		}
		$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
		$eventsObj = XenForo_Application::get('brcEvents');
		foreach($currencies AS $currencyId=>$currency){
			$events = $actionObj->getActionEvents('transfer', array('currency_id' => $currency['currency_id']));
			if($eventId =$actionObj->checkTriggerActionEvents($events)){
				$currency['event_id'] = $eventId;
			}else{
				unset($currencies[$currencyId]);
			}
		}

		$viewParams = array(
			'receiver' => $data['receiver'],
			'currencyId' => $data['currency_id'],
			'type' => $type,
			'currencies' => $currencies,
			'from' => $from,
			'userGroups' => $userGroups,
		);

		return $this->responseView(
			'Brivium_Credits_ViewAdmin_Credits_Transfer',
			'BRC_transfer',
			$viewParams
		);
	}

	public function actionDoTransfer()
	{
		$this->_assertPostOnly();
		$data = $this->_input->filter(array(
			'type' => XenForo_Input::STRING,
			'from' => XenForo_Input::STRING,
			'user_group' => XenForo_Input::UINT,
			'anonymous' => XenForo_Input::UINT,
			'receiver' => XenForo_Input::STRING,
			'amount' => XenForo_Input::FLOAT,
			'comment' => XenForo_Input::STRING,
			'redirect' => XenForo_Input::STRING,
			'currency_id' => XenForo_Input::UINT,
		));

		$redirect = ($data['redirect'] ? $data['redirect'] : $this->getDynamicRedirect());

		$visitor = XenForo_Visitor::getInstance()->toArray();


		/* @var $userModel XenForo_Model_user */
		$userModel = $this->getModelFromCache('XenForo_Model_User');
		$userGroupModel = $this->getModelFromCache('XenForo_Model_UserGroup');
		$creditModel = $this->_getCreditModel();

		list($event, $currency) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('transfer',$data['currency_id']);
		$currencyObj = XenForo_Application::get('brcCurrencies');

		$userIds = array();
		if($data['type'] =='usergroup'){
			$users = $userGroupModel->getUserIdsInUserGroup($data['user_group']);
			if(is_array($users) && !$userIds = array_keys($users)){
				$userIds = array();
			}
		}else{
			$receiverUsernames = explode(',',$data['receiver']);
			$receivers = $userModel->getUsersByNames($receiverUsernames);
			if(is_array($receivers) && !$userIds = array_keys($receivers)){
				$userIds = array();
			}
			if (in_array($visitor['user_id'],$userIds) && $data['from'] =='self') {
				return $this->responseError(new XenForo_Phrase('BRC_transfer_self'));
			}
		}

		$neededMoney = 0;
		$creditModel->setIsBulk(true);
		$creditModel->setIsWaitSubmit(true);
		foreach($userIds AS $userId){
			$errorString = '';
			$noError = true;
			if($data['from'] =='self'){
				$selfDataCredit = array(
					'user_action_id' 	=>	$userId,
					'user' 	=>	$visitor,
					'amount' 			=>	-$data['amount'],
					'message' 			=>	$data['anonymous']?(new XenForo_Phrase('BRC_anonymous_transfer') .': '. $data['comment']):$data['comment'],
					'currency_id'		=>	$currency['currency_id'],
					'ignoreInclude' 	=>	true,
					'extraData' 		=>	array('type'	=>	'sender',)
				);
				$noError = $creditModel->updateUserCredit('transfer', $visitor['user_id'], $selfDataCredit, $errorString);
				//if($errorString)return $this->responseError($errorString);
				$visitorId = XenForo_Visitor::getUserId();
			}else{
				$visitorId = 0;
			}
			if($noError && !$errorString)
			{
				$neededMoney += $data['amount'];
				$errorString = '';
				$dataCredit = array(
					'user_action_id' 	=>	$data['anonymous']?$userId:$visitorId,
					'amount' 			=>	$data['amount'],
					'message' 			=>	$data['anonymous']?(new XenForo_Phrase('BRC_anonymous_transfer') .': '. $data['comment']):$data['comment'],
					'currency_id'		=>	$currency['currency_id'],
					'ignoreInclude' 	=>	true,
					'extraData' 		=>	array('type'=>'receiver', 'anonymous'	=>	$data['anonymous'])
				);
				$creditModel->updateUserCredit('transfer', $userId, $dataCredit, $errorString);
			}
			//if($errorString)return $this->responseError($errorString);
		}
		if($data['from'] =='self' && ($visitor[$currency['column']] - $neededMoney) < 0){
			return $this->responseError(new XenForo_Phrase('BRC_not_enough_transfer',array('amount' => $currencyObj->currencyFormat($neededMoney,false,$currency['currency_id']))));
		}

		$creditModel->commitUpdate();
		$viewParams = array();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirect,
			new XenForo_Phrase('BRC_transaction_processed_successfully')
		);
	}

	/**
	 * Remove credits of all users.
	 *
	 */
	public function actionResetCredit()
	{
		if ($this->isConfirmedPost())
		{
			$deleteTransaction = $this->_input->filterSingle('delete_transaction', XenForo_Input::UINT);
			$type = $this->_input->filterSingle('type', XenForo_Input::STRING);
			$amount = $this->_input->filterSingle('amount', XenForo_Input::UINT);
			$creditModel = $this->_getCreditModel();
			$data = $this->_input->filter(array(
				'user_group' => XenForo_Input::UINT,
				'username' => XenForo_Input::STRING,
				'amount' => XenForo_Input::FLOAT,
			));

			$userModel = $this->getModelFromCache('XenForo_Model_User');
			$userGroupModel = $this->getModelFromCache('XenForo_Model_UserGroup');
			$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);
			$userIds = array();
			if($type =='usergroup'){
				$users = $userGroupModel->getUserIdsInUserGroup($data['user_group']);
				if(is_array($users) && !$userIds = array_keys($users)){
					$userIds = array();
				}
			}else if($type =='user'){
				$receiver = $userModel->getUserByName($data['username']);
				if (empty($receiver)) {
					return $this->responseError(new XenForo_Phrase('requested_user_not_found'));
				}
				$userIds[] = $receiver['user_id'];
			}
			$columns = array();
			if($currencyId){
				$currency = XenForo_Application::get('brcCurrencies')->$currencyId;
				$columns[$currency['column']] = $amount;
			}else{
				$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
				foreach($currencies AS $currency){
					$columns[$currency['column']] = $amount;
				}
			}
			if($userIds ||  $type =='all'){
				$creditModel->resetCredit($columns,$userIds);
				if($deleteTransaction){
					$this->_getTransactionModel()->deleteAllTransaction($currencyId, $userIds);
					if(!$userIds){
						$this->_getCreditStastModel()->deleteAllStast($currencyId);
					}

				}
			}
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('credits/user-credits'),
				new XenForo_Phrase('BRC_transaction_processed_successfully')
			);
		}
		else
		{
			$userGroups = array();
			$listUserGroups = XenForo_Model::create('XenForo_Model_UserGroup')->getAllUserGroups();
			foreach ($listUserGroups AS $userGroupId => $userGroup)
			{
				if($userGroupId!=0)
				$userGroups[$userGroupId] = array(
					'label' => $userGroup['title'],
					'value' => $userGroup['user_group_id'],
				);
			}
			$viewParams = array(
				'userGroups' => $userGroups,
				'currencies' => XenForo_Application::get('brcCurrencies')->getCurrencies(),
			);
			return $this->responseView('Brivium_Credits_ViewAdmin_Credits_ResetCredit', 'BRC_reset_credit', $viewParams);
		}
	}

	/*========================= Import Export ================================*/
	public function actionImportCredits()
	{
		$addOnModel = $this->_getAddOnModel();
		$importBdBank = $addOnModel->getAddOnVersion('bdbank')?true:false;

		$importMyPoints = $addOnModel->getAddOnVersion('myPoints')?true:false;
		$importAdCredit = $addOnModel->getAddOnVersion('adcredit_core')?true:false;
		$viewParams = array(
			'importBdBank' => $importBdBank,
			'importMyPoints' => $importMyPoints,
			'importAdCredit' => $importAdCredit,
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_Import', 'BRC_import_credits', $viewParams);
	}

	public function actionImportTrophyPoints()
	{
		$viewParams = array(
			'type' => 'merge',
			'currencies' => XenForo_Application::get('brcCurrencies')->getCurrencies(),
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_Import_TrophyPoints', 'BRC_import_credits_trophy_points', $viewParams);
	}

	public function actionImportAdCredit()
	{
		$adCurrencies = $this->_getCreditModel()->getAllAdCreditCurrencies();
		if(!$adCurrencies){
			return $this->responseError('[AD] Credits: Core Addon required');
		}
		$viewParams = array(
			'type' => 'merge',
			'adCurrencies' => $adCurrencies,
			'currencies' => XenForo_Application::get('brcCurrencies')->getCurrencies(),
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_Import_TrophyPoints', 'BRC_import_credits_ad_credit', $viewParams);
	}

	public function actionImportMyPoints()
	{
		$viewParams = array(
			'type' => 'merge',
			'currencies' => XenForo_Application::get('brcCurrencies')->getCurrencies(),
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_Import_MyPoints', 'BRC_import_credits_my_points', $viewParams);
	}

	public function actionImportBdBanking()
	{
		$viewParams = array(
			'type' => 'merge',
			'currencies' => XenForo_Application::get('brcCurrencies')->getCurrencies(),
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_Import_MyPoints', 'BRC_import_credits_bdbank', $viewParams);
	}

	protected function _getFilterParams()
	{
		return $this->_input->filter(array(
			'top_type' => XenForo_Input::STRING,
			'order' => XenForo_Input::STRING,
			'action_id' => XenForo_Input::STRING,
			'currency_id' => XenForo_Input::UINT,
			'username' => XenForo_Input::STRING,
			'start' => XenForo_Input::STRING,
			'end' => XenForo_Input::STRING
		));
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

	protected function _getEventModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Event');
	}

	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}

	/**
	 * @return XenForo_Model_AddOn
	 */
	protected function _getAddOnModel()
	{
		return $this->getModelFromCache('XenForo_Model_AddOn');
	}

	/**
	 * Gets the admin template model.
	 *
	 * @return XenForo_Model_AdminTemplate
	 */
	protected function _getAdminTemplateModel()
	{
		return $this->getModelFromCache('XenForo_Model_AdminTemplate');
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

	protected function _getCreditHelper()
	{
		return $this->getHelper('Brivium_Credits_ControllerHelper_Credit');
	}

	protected function _getCreditStastModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_CreditStast');
	}

}