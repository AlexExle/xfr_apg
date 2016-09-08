<?php

class Brivium_Credits_ControllerPublic_Credits extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$limit = XenForo_Application::get('options')->BRC_memberPerTop;
		$userModel = $this->_getUserModel();
		$transactionModel = $this->_getTransactionModel();
		$creditModel = $this->_getCreditModel();
		$stastModel = $this->_getCreditStastModel();
		$canViewStatistic = $stastModel->canViewCreditStatistics();
		$canViewRanking = $creditModel->canViewRanking();
		if(!$canViewRanking && !$canViewStatistic){
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('credits/transactions')
			);
		}
		$viewStatisticParams = array();
		$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
		$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);

		$currency = isset($currencies[$currencyId])?$currencies[$currencyId]:reset($currencies);

		if(empty($currency['currency_id'])){
			return $this->responseError(new XenForo_Phrase('BRC_requested_currency_not_found'));
		}
		$currencyId = $currency['currency_id'];
		$actionIds = $this->_input->filterSingle('action_id', XenForo_Input::STRING, array('array' => true));

		$actionModel = $this->_getActionModel();
		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();
		$action = array();
		$events = $actionObj->getEvents();

		foreach($currencies AS &$_currency){
			$_currency['events'] = array();
			foreach($events AS $event){
				if(!empty($event[$_currency['currency_id']]) &&
					!empty($event[$_currency['currency_id']]['title']) &&
					!empty($event[$_currency['currency_id']]['action_id']) &&
					!empty($event[$_currency['currency_id']]['active'])){
					$_currency['events'][$event[$_currency['currency_id']]['action_id']] = $event[$_currency['currency_id']];
				}
			}
		}

		$actionId 	= '';
		$currency = isset($currencies[$currencyId])?$currencies[$currencyId]:$currency;
		if(!empty($actionIds[$currencyId])){
			$actionId 	= $actionIds[$currencyId];
			if(!empty($currency['events'][$actionId])){
				$action = $currency['events'][$actionId];
			}else{
				return $this->responseError(new XenForo_Phrase('BRC_requested_action_not_found'));
			}
		}

		$viewStatisticParams = array(
			'action' 			=> 	$action,
			'actionId' 			=> 	$actionId,
			'actions' 			=> 	$actions
		);
		if($canViewStatistic){
			$totalCredits 	= $creditModel->totalCredits($currency['column']);

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


			$viewStatisticParams = array(
				'action' 			=> 	$action,
				'actionId' 			=> 	$actionId,
				'actions' 			=> 	$actions,

				'totalCredits'  	=> $totalCredits,

				'earnedPerday'  	=> $earnedPerday,
				'spentPerday'   	=> $spentPerday,

				'statisticRecord'   => $statisticRecord,
				'todayStatisticRecord'   => $todayStatisticRecord,
				'firstDay'  		=> $firstDay,
			);
		}

		$richest = array();
		$poorest = array();
		if($canViewRanking){
			$criteria = array(
				'user_state' => 'valid',
				'is_banned' => 0
			);

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
		}

		$boardTotals = $this->getModelFromCache('XenForo_Model_DataRegistry')->get('boardTotals');
		if (!$boardTotals)
		{
			$boardTotals = $this->getModelFromCache('XenForo_Model_Counters')->rebuildBoardTotalsCounter();
		}
		$totalUsers 	= $boardTotals['users'];
		$viewParams = array_merge($viewStatisticParams,array(

			'canViewRanking'  => $canViewRanking,
			'canViewStatistic'  => $canViewStatistic,
			'totalUsers'   		=> $totalUsers,

			'currencies'   		=> 	$currencies,
			'currency'   		=> 	$currency,
			'richest'  		 	=> 	$richest,
			'poorest'   		=> 	$poorest,
		));
		return $this->_getWrapper(
			'credits', 'index',
			$this->responseView(
				'Brivium_Credits_ViewPublic_Credits_Index',
				'BRC_credits',
				$viewParams
			)
		);
	}

	public function actionNavigationPopup()
	{
		$creditModel = $this->_getCreditModel();
		$actionObj = XenForo_Application::get('brcActionHandler');

		$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();

		$canExchange = $creditModel->canExchange();
		$canPurchaseCredits = $creditModel->canPurchaseCredits();
		$viewParams = array(
			'canExchange'=> $canExchange,
			'canTransfer'=> $actionObj->canTriggerActionEvents('transfer'),
			'canStealCredits' => $creditModel->canStealCredits() && $actionObj->canTriggerActionEvents('steal'),
			'canPurchaseCredits'=> $canPurchaseCredits,
			'canWithdraw' => $actionObj->canTriggerActionEvents('withdraw'),
			'canViewRanking' => $creditModel->canViewRanking(),
			'canViewOtherTransactions' => $this->_getTransactionModel()->canViewOtherTransactions(),
		);

		return $this->responseView(
			'Brivium_Credits_ViewPublic_Credits_NavigationPopup',
			'BRC_navigation_tabs_popup',
			$viewParams
		);
	}

	protected function _getPurchaseCreditActions()
	{
		$paymentActions = array();
		if(XenForo_Application::get('brcActionHandler')->canTriggerActionEvents('paypalPayment')){
			$paymentActions['paypalPayment'] = new XenForo_Phrase('BRC_paypal');
		}

		return $paymentActions;
	}

	protected function _getBuyCreditParams($type)
	{
		$viewParams = array();
		if($type == 'paypalPayment'){
			$currencies = $this->_getCreditHelper()->assertCurrenciesValidAndViewable('paypalPayment');
			if(!$currencies){
				return $viewParams;
			}
			$viewParams['currencies'] = $currencies;
			$viewParams['formPurchaseTemplateName'] = 'BRCP_paypal_form';

			if(XenForo_Application::getOptions()->BRCP_enableSanbox){
				//for paypal sanbox test
				$viewParams['payPalUrl'] = 'https://www.sandbox.paypal.com/cgi-bin/websrc';
			}else{
				$viewParams['payPalUrl'] = 'https://www.paypal.com/cgi-bin/websrc';
			}
		}
		return $viewParams;
	}

	public function actionBuyCredit()
	{
		$type = $this->_input->filterSingle('type', XenForo_Input::STRING);
		$creditModel = $this->_getCreditModel();
		if(!$creditModel->canPurchaseCredits()){
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$paymentActions = $this->_getPurchaseCreditActions();

		if(!$type){
			$type = key($paymentActions);
		}

		$viewParams = $this->_getBuyCreditParams($type, $paymentActions);
		if ($viewParams instanceof XenForo_ControllerResponse_Error)
		{
			return $viewParams;
		}
		if (!$viewParams)
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}
		$viewParams['type'] = $type;
		$viewParams['paymentActions'] = $paymentActions;

		return $this->responseView(
			'Brivium_Credits_ViewPublic_Credits_PurchaseCredits',
			'BRC_buy_credits',
			$viewParams
		);
	}

	/*========================= Transaction ================================*/
	public function actionTransactions()
	{
		$transactionModel = $this->_getTransactionModel();
		$creditModel = $this->_getCreditModel();

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$options = XenForo_Application::get('options');
		$perPage = $options->BRC_transactionsPerPage;
		list($defaultOrder, $defaultOrderDirection) = $this->_getDefaultTransactionSort();

		$order = $this->_input->filterSingle('order', XenForo_Input::STRING, array('default' => $defaultOrder));
		$orderDirection = $this->_input->filterSingle('direction', XenForo_Input::STRING, array('default' => $defaultOrderDirection));
		$visitor = XenForo_Visitor::getInstance();

		$export = false;
		$canExport = $creditModel->canExportTransaction($visitor->toArray());
		if ($this->_input->inRequest('export') && $canExport)
		{
			$export = true;
		}
		$disabledActions = $options->BRC_disabledActions;
		$conditions = array(
			'user_id' => $visitor['user_id'],
			'currency_active' => true,
		);
		if($disabledActions){
			$conditions['not_action_id'] = $disabledActions;
		}

		$actionId = $this->_input->filterSingle('action_id', XenForo_Input::STRING, array('default' => ''));
		$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT, array('default' => 0));
		$pageParams = array();
		if ($actionId)
		{
			$pageParams['action_id'] = $actionId;
			$conditions['action_id'] = $actionId;
		}
		if ($currencyId)
		{
			$pageParams['currency_id'] = $currencyId;
			$conditions['currency_id'] = $currencyId;
			$currency = XenForo_Application::get('brcCurrencies')->$currencyId;
		}
		if(!$export){
			$fetchOptions = array(
				'order' => $order,
				'orderDirection' => $orderDirection,
				'page' => $page,
				'perPage' => $perPage,
				'join' =>  Brivium_Credits_Model_Transaction::FETCH_TRANSACTION_FULL
			);
		}else{
			$fetchOptions = array(
				'join' =>  Brivium_Credits_Model_Transaction::FETCH_TRANSACTION_FULL
			);
		}

		$transactions = $transactionModel->getTransactions($conditions, $fetchOptions);
		$transactions = $transactionModel->prepareTransactions($transactions);
		$totalTransactions = $transactionModel->countTransactions($conditions);
		if($export){
			$name = "Brivium_Transaction_Export_" . date("Y-m-d_h-i-s-A") . ".csv";
			$fileName = "data/transactions/{$visitor['user_id']}". $name;
			$directory = dirname($fileName);

			if (XenForo_Helper_File::createDirectory($directory, true))
			{
				$fp = fopen($fileName, 'w');
				$header = array(
					'Transaction Id',
					'Action',
					'Currency Id',
					'User Id',
					'User Name',
					'User Action Id',
					'User Action Name',
					'Amount',
					'Reverted',
					'Status',
					'Transaction Date',
					'Message',
				);
				fputcsv($fp, $header);
					foreach ($transactions as $transaction) {
						$transaction['reverted'] = !empty($transaction['extraData']['reverted'])?'Reverted':'';
						$newTransaction = array(
							$transaction['transaction_id'],
							$transaction['action'],
							$transaction['currency_id'],
							$transaction['user_id'],
							$transaction['username'],
							$transaction['user_action_id'],
							$transaction['user_action_name'],
							$transaction['amount_phrase'],
							$transaction['reverted'],
							$transaction['moderate']?new XenForo_Phrase('BRC_pending'):new XenForo_Phrase('BRC_complete'),
							XenForo_Template_Helper_Core::dateTime($transaction['transaction_date'],'absolute'),
							$transaction['message'],
						);
						fputcsv($fp, $newTransaction);
					}
				fclose($fp);
				XenForo_Helper_File::makeWritableByFtpUser($fileName);
			}

			$this->_routeMatch->setResponseType('raw');

			$viewParams = array(
				'transactions' => array(
					'filename' =>  $name,
					'export_date' => XenForo_Application::$time,
					'file_size' => filesize($fileName),
				),
				'transactionsFile' => $fileName,
			);

			return $this->responseView(
				'Brivium_Credits_ViewPublic_Credits_Transaction',
				'',
				$viewParams
			);
		}
		$orderDirectionEx = ($orderDirection == 'desc' ? 'asc' : 'desc');

		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();

		foreach($actions AS $_actionId=>$action){
			$events = $actionObj->getActionEvents($_actionId);
			if(!$events){
				unset($actions[$_actionId]);
			}
		}

		$viewParams = array(
			'actions' => $actions,
			'currencies' => XenForo_Application::get('brcCurrencies')->getCurrencies(),
			'transactions' => $transactions,
			'page' => $page,
			'perPage' => $perPage,
			'actionId' => $actionId,
			'currencyId' => $currencyId,
			'canExport' => $canExport,
			'transactionStartOffset' => ($page - 1) * $perPage + 1,
			'transactionEndOffset' => ($page - 1) * $perPage + count($transactions) ,
			'totalTransactions' => $totalTransactions,
			'pagenavLink' => 'credits/transactions',

			'pageParams' => $pageParams,
			'order' => $order,
			'orderDirection' => $orderDirection,
			'orderDirectionEx' => $orderDirectionEx,
			'canViewSensitiveData' => 	$creditModel->canViewSensitiveData(),
		);
		return $this->_getWrapper(
			'credits', 'transactions',
			$this->responseView(
				'Brivium_Credits_ViewPublic_Credits_Transaction',
				'BRC_transaction_list',
				$viewParams
			)
		);
	}

	public function actionAllTransactions()
	{
		$transactionModel = $this->_getTransactionModel();
		$creditModel = $this->_getCreditModel();

		$canViewOtherTransactions = $transactionModel->canViewOtherTransactions();
		if(!$canViewOtherTransactions){
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}
		$input = $this->_getFilterParams();

		$dateInput = $this->_input->filter(array(
			'start' => XenForo_Input::DATE_TIME,
			'end' => XenForo_Input::DATE_TIME,
		));

		$moderate = $this->_input->filterSingle('moderate', XenForo_Input::UINT);

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
		if ($input['action_id'])
		{
			$pageParams['action_id'] = $input['action_id'];
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
		$options = XenForo_Application::get('options');

		$disabledActions = $options->BRC_disabledActions;
		$conditions = array(
			'action_id' => $input['action_id'],
			'currency_id' => $input['currency_id'],
			'user_id' => $userId,
			'start' => $dateInput['start'],
			'end' => $dateInput['end'],
		);
		if($disabledActions){
			$conditions['not_action_id'] = $disabledActions;
		}
		if($moderate){
			$conditions['moderate'] = $moderate;
		}
		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage = $options->BRC_transactionsPerPage;

		$fetchOptions = array(
			'page' => $page,
			'perPage' => $perPage,
			'join' =>  Brivium_Credits_Model_Transaction::FETCH_TRANSACTION_FULL
		);
		switch ($input['order'])
		{
			case 'amount':
				$fetchOptions['order'] = 'amount';
				break;

			case 'transaction_date';
			default:
				$input['order'] = 'transaction_date';
				$fetchOptions['order'] = 'transaction_date';
				break;
		}

		$transactions = $transactionModel->getTransactions($conditions, $fetchOptions);
		//prd($transactions);
		$transactions = $transactionModel->prepareTransactions($transactions);

		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();

		foreach($actions AS $_actionId=>$action){
			$events = $actionObj->getActionEvents($_actionId);
			if(!$events){
				unset($actions[$_actionId]);
			}
		}

		$viewParams = array(
			'actions' => $actions,
			'currencies' => XenForo_Application::get('brcCurrencies')->getCurrencies(),
			'transactions' => $transactions,
			'moderate' => $moderate,

			'order' => $input['order'],
			'actionId' => $input['action_id'],
			'currencyId' => $input['currency_id'],
			'username' => $input['username'],
			'start' => $input['start'],
			'end' => $input['end'],

			'datePresets' => XenForo_Helper_Date::getDatePresets(),

			'page' => $page,
			'perPage' => $perPage,
			'pageParams' => $pageParams,
			'totalTransactions' =>	$transactionModel->countTransactions($conditions),
			'transactionStartOffset' => ($page - 1) * $perPage + 1,
			'transactionEndOffset' => ($page - 1) * $perPage + count($transactions),
			'canViewSensitiveData' => 	$creditModel->canViewSensitiveData(),
			'pagenavLink' => 'credits/all-transactions',
		);
		return $this->responseView(
				'Brivium_Credits_ViewPublic_Credits_Transaction',
				'BRC_transaction_list_all',
				$viewParams
			);
	}

	public function actionTransactionView()
	{
		$transactionId = $this->_input->filterSingle('transaction_id', XenForo_Input::UINT);
		$transactionModel = $this->_getTransactionModel();
		$creditModel = $this->_getCreditModel();

		$fetchOptions = array(
			'join' =>  Brivium_Credits_Model_Transaction::FETCH_TRANSACTION_FULL
		);
		$transaction = $transactionModel->getTransactionById($transactionId, $fetchOptions);

		if(!$transaction){
			return $this->responseError(new XenForo_Phrase('BRC_requested_transaction_not_found'));
		}
		$viewParams = array(
			'transaction' => $transactionModel->prepareTransaction($transaction),
			'canViewSensitiveData' => 	$creditModel->canViewSensitiveData(),
		);

		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_View', 'BRC_transaction_view', $viewParams);
	}

	/*========================= Transfer ================================*/
	public function actionTransfer()
	{
		$currencies = $this->_getCreditHelper()->assertCurrenciesValidAndViewable('transfer');
		if(!$currencies){
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}
		$data = $this->_input->filter(array(
			'receiver' => XenForo_Input::STRING,
			'currency_id' => XenForo_Input::UINT,
		));
		$viewParams = array(
			'receiver' => $data['receiver'],
			'currencyId' => $data['currency_id'],
			'currencies' => $currencies,
			'canAnonymousTransfer' => $this->_getCreditModel()->canAnonymousTransfer(),
		);

		return $this->_getWrapper(
			'credits', 'transfer',
			$this->responseView(
				'Brivium_Credits_ViewAdmin_Credits_Transaction',
				'BRC_transfer',
				$viewParams
			)
		);
	}

	public function actionDoTransfer()
	{
		$this->_assertPostOnly();
		$data = $this->_input->filter(array(
			'receiver' => XenForo_Input::STRING,
			'amount' => XenForo_Input::UNUM,
			'currency_id' => XenForo_Input::UINT,
			'anonymous' => XenForo_Input::UINT,
			'comment' => XenForo_Input::STRING,
			'redirect' => XenForo_Input::STRING,
		));
		$redirect = ($data['redirect'] ? $data['redirect'] : $this->getDynamicRedirect());

		$visitor = XenForo_Visitor::getInstance()->toArray();

		/* @var $userModel XenForo_Model_user */
		$userModel = $this->getModelFromCache('XenForo_Model_User');
		$creditModel = $this->_getCreditModel();

		$currencyObj = XenForo_Application::get('brcCurrencies');

		list($event, $currency) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('transfer',$data['currency_id']);

		if ($data['amount'] <= 0) {
			return $this->responseError(new XenForo_Phrase('BRC_not_valid_amount'));
		}
		if ($data['anonymous'] && !$creditModel->canAnonymousTransfer()) {
			return $this->responseError(new XenForo_Phrase('BRC_do_not_have_permission_anonymous_transfer'));
		}

		$userCredit = $visitor[$currency['column']];

		if($data['amount'] <= $event['amount']){
			return $this->responseError(new XenForo_Phrase('BRC_your_transfer_must_bigger_than_x',array('amount' => $currencyObj->currencyFormat($event['amount'],false,$currency['currency_id']))));
		}

		list($userTax, $userActionTax) = $creditModel->processTax($data['amount'], $event);
		$userTaxedAmount = $data['amount'] + $userTax ;
		$userActionTaxedAmount = $data['amount'] - $userActionTax;

		$creditModel->setIsWaitSubmit(true);
		$receiverUsernames = explode(',', $data['receiver']);
		$neededMoney = 0;
		$listReceivers = $userModel->getUsersByNames($receiverUsernames, array('join' => XenForo_Model_User::FETCH_USER_FULL));
		if(!$listReceivers){
			return $this->responseError(new XenForo_Phrase('BRC_requested_receivers_not_found'));
		}
		foreach ($listReceivers as $receiver) {
			if ($receiver['user_id'] == $visitor['user_id']) {
				return $this->responseError(new XenForo_Phrase('BRC_transfer_self'));
			}
			$neededMoney += $userTaxedAmount;
			if ( ($userCredit - $userTaxedAmount) < 0) {
				continue;
				//return $this->responseError(new XenForo_Phrase('BRC_not_enough_transfer',array('amount' => $currencyObj->currencyFormat($userTaxedAmount,false,$currency['currency_id']))));
			}
			$userCredit = $userCredit - $userTaxedAmount;
			$dataCredit = array(
				'user_action_id' 	=>	$receiver['user_id'],
				'amount' 			=>	-$userTaxedAmount,
				'user'				=>	$visitor,
				'currency_id'		=>	$currency['currency_id'],
				'message' 			=>	$data['anonymous']?(new XenForo_Phrase('BRC_anonymous_transfer') .': '. $data['comment']):$data['comment'],
				'extraData' 		=>	array('type'	=>	'sender',)
			);
			$creditModel->updateUserCredit('transfer',$visitor['user_id'],$dataCredit,$errorString);
			if($errorString)return $this->responseError($errorString);
			$dataCredit2 = array(
				'user_action_id' 	=>	$data['anonymous']?$receiver['user_id']:$visitor['user_id'],
				'amount' 			=>	$userActionTaxedAmount,
				'message' 			=>	$data['anonymous']?(new XenForo_Phrase('BRC_anonymous_transfer') .': '. $data['comment']):$data['comment'],
				'currency_id'		=>	$currency['currency_id'],
				'ignore_include' 	=>	true,
				'user'				=>	$receiver,
				'extraData' 		=>	array('type'	=>	'receiver', 'anonymous'	=>	$data['anonymous'])
			);

			$errorString = '';
			$creditModel->updateUserCredit('transfer',$receiver['user_id'],$dataCredit2,$errorString);
			if($errorString)return $this->responseError($errorString);
		}
		if ( ($visitor[$currency['column']] - $neededMoney) < 0) {
			return $this->responseError(new XenForo_Phrase('BRC_not_enough_transfer',array('amount' => $currencyObj->currencyFormat($neededMoney,false,$currency['currency_id']))));
		}
		$creditModel->commitUpdate();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirect,
			new XenForo_Phrase('BRC_transaction_processed_successfully')
		);
	}

	/*========================= Exchange ================================*/
	public function actionExchange()
	{
		$currencies = $this->_getCreditHelper()->assertCurrenciesValidAndViewable('exchange');
		if(!$currencies || count($currencies) < 2){
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$fromCurrencies = array();
		$toCurrencies = array();
		foreach($currencies AS $currency){
			if(!empty($currency['in_bound'])){
				$toCurrencies[$currency['currency_id']] = $currency;
			}
			if(!empty($currency['out_bound'])){
				$fromCurrencies[$currency['currency_id']] = $currency;
			}
		}
		if(!$fromCurrencies || !$toCurrencies){
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$viewParams = array(
			'currencies' => $currencies,
			'fromCurrencies' => $fromCurrencies,
			'toCurrencies' => $toCurrencies,
		);

		return $this->_getWrapper(
			'credits', 'exchange',
			$this->responseView(
				'Brivium_Credits_ViewAdmin_Credits_Exchange',
				'BRC_exchange',
				$viewParams
			)
		);
	}

	public function actionDoExchange()
	{
		$this->_assertPostOnly();
		$data = $this->_input->filter(array(
			'amount' => XenForo_Input::UNUM,
			'from' => XenForo_Input::UINT,
			'to' => XenForo_Input::UINT,
			'redirect' => XenForo_Input::STRING,
		));
		$redirect = ($data['redirect'] ? $data['redirect'] : $this->getDynamicRedirect());

		$visitor = XenForo_Visitor::getInstance()->toArray();

		if ($data['from'] == $data['to']) {
			return $this->responseError(new XenForo_Phrase('BRC_exchange_one_currency_error'));
		}
		/* @var $userModel XenForo_Model_user */
		$userModel = $this->getModelFromCache('XenForo_Model_User');
		$creditModel = $this->_getCreditModel();

		$currenciesObj = XenForo_Application::get('brcCurrencies');

		list($eventFrom, $currencyFrom) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('exchange', $data['from']);
		list($eventTo, $currencyTo) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('exchange', $data['to']);

		if (!$currencyFrom['out_bound'] || !$currencyFrom['active']) {
			return $this->responseError(new XenForo_Phrase('BRC_you_cant_exchange_from_x',array('currency'=>$currencyTo['title'])));
		}
		if (!$currencyTo['in_bound'] || !$currencyTo['active']) {
			return $this->responseError(new XenForo_Phrase('BRC_you_cant_exchange_to_x',array('currency'=>$currencyTo['title'])));
		}
		if ($data['amount'] <= 0) {
			return $this->responseError(new XenForo_Phrase('BRC_not_valid_amount'));
		}


		$userCredit = $visitor[$currencyFrom['column']];

		if($data['amount'] <= $eventFrom['amount']){
			return $this->responseError(new XenForo_Phrase('BRC_amount_using_must_bigger_than_x',array('amount' => $currenciesObj->currencyFormat($eventFrom['amount'],false,$currencyFrom['currency_id']))));
		}
		//prd($creditModel->processTax( $data['amount'],$eventFrom));
		list($userTax, $userActionTax) = $creditModel->processTax( $data['amount'],$eventFrom);
		$userTaxedAmount = $data['amount'] + $userTax ;
		$userActionTaxedAmount = $data['amount'] - $userActionTax;
		if ( ($userCredit - $userTaxedAmount) < 0) {
			return $this->responseError(new XenForo_Phrase('BRC_not_enough_transfer',array('amount' => $currenciesObj->currencyFormat($userTaxedAmount,false,$currencyFrom['currency_id']))));
		}


		$userActionTaxedAmount = $userActionTaxedAmount*($currencyTo['value']/$currencyFrom['value']);


		//$hash = md5(implode(',',array_keys($receivers)) . $formData['amount'] . $balanceAfter);
		$dataCredit = array(
			'user_action_id' 	=>	$visitor['user_id'],
			'amount' 			=>	-$userTaxedAmount,
			'user'				=>	$visitor,
			'currency_id'			=>	$currencyFrom['currency_id'],
			//'message' 			=>	$data['comment'],
			'extraData' 		=>	array(
										'type'	=>	'sender',
										'currency'	=>	$currencyTo,
									)
		);
		$errorString = '';
		$creditModel->setIsWaitSubmit(true);
		$creditModel->updateUserCredit('exchange',$visitor['user_id'],$dataCredit,$errorString);
		if($errorString)return $this->responseError($errorString);
		$dataCredit2 = array(
			'user_action_id' 	=>	$visitor['user_id'],
			'amount' 			=>	$userActionTaxedAmount,
			'user'				=>	$visitor,
			'currency_id'		=>	$currencyTo['currency_id'],
			//'message' 		=>	$data['comment'],
			'ignore_include' 	=>	true,
			'extraData' 		=>	array(
									'type'	=>	'receiver',
									'currency'	=>	$currencyFrom
								)
		);
		$errorString = '';
		$creditModel->updateUserCredit('exchange',$visitor['user_id'],$dataCredit2,$errorString);
		if($errorString)return $this->responseError($errorString);

		$creditModel->commitUpdate();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirect,
			new XenForo_Phrase('BRC_transaction_processed_successfully')
		);
	}


	public function actionGetExchangeAmount()
	{
		$options = XenForo_Application::get('options');
		$data = $this->_input->filter(array(
			'amount' => XenForo_Input::UNUM,
			'from' => XenForo_Input::UINT,
			'to' => XenForo_Input::UINT,
		));
		$amount = $this->_input->filterSingle('amount', XenForo_Input::UNUM);

		$currenciesObj = XenForo_Application::get('brcCurrencies');
		$creditModel = $this->_getCreditModel();
		list($eventFrom, $currencyFrom) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('exchange', $data['from']);
		list($eventTo, $currencyTo) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('exchange', $data['to']);

		$visitor = XenForo_Visitor::getInstance()->toArray();

		if (!$currencyFrom['out_bound'] || !$currencyFrom['active']) {
			return $this->responseError(new XenForo_Phrase('BRC_you_cant_exchange_from_x',array('currency'=>$currencyTo['title'])));
		}
		if (!$currencyTo['in_bound'] || !$currencyTo['active']) {
			return $this->responseError(new XenForo_Phrase('BRC_you_cant_exchange_to_x',array('currency'=>$currencyTo['title'])));
		}
		if ($data['amount'] <= 0) {
			return $this->responseError(new XenForo_Phrase('BRC_not_valid_amount'));
		}

		list($userTax, $userActionTax) = $creditModel->processTax( $data['amount'],$eventFrom);
		$userTaxedAmount = $data['amount'] + $userTax ;
		$userActionTaxedAmount = $data['amount'] - $userActionTax;
		$userCredit = $visitor[$currencyFrom['column']];
		if ( ($userCredit - $userTaxedAmount) < 0) {
			return $this->responseError(new XenForo_Phrase('BRC_not_enough_transfer',array('amount' => $currenciesObj->currencyFormat($userTaxedAmount,false,$currencyFrom['currency_id']))));
		}

		$userActionTaxedAmount = $userActionTaxedAmount*($currencyTo['value']/$currencyFrom['value']);
		$amountPhrase = $currenciesObj->currencyFormat($userActionTaxedAmount, false, $currencyTo['currency_id']);

		$losePhrase = $currenciesObj->currencyFormat($userTaxedAmount, false, $currencyFrom['currency_id']);

		$viewParams = array(
			'loseAmount' => $losePhrase,
			'amount' => $amountPhrase,
		);

		return $this->responseView(
			'Brivium_Credits_ViewPublic_Credits_GetAmountExchange',
			'',
			$viewParams
		);
	}

	/*========================= WithDraw ================================*/


	protected function _getWithDrawActions()
	{
		$paymentActions = array();
		if(XenForo_Application::get('brcActionHandler')->canTriggerActionEvents('withdraw')){
			$paymentActions['withdraw'] = new XenForo_Phrase('BRC_withdraw');
		}
		return $paymentActions;
	}

	protected function _getWithDrawParams($type)
	{
		$viewParams = array();
		if($type == 'withdraw'){
			$currencies = $this->_getCreditHelper()->assertCurrenciesValidAndViewable('withdraw');
			if(!$currencies){
				throw $this->getNoPermissionResponseException();
			}
			$viewParams['currencies'] = $currencies;
			$viewParams['formWithdrawTemplateName'] = 'BRC_withdraw_form';
		}
		return $viewParams;
	}

	public function actionWithdraw()
	{
		$type = $this->_input->filterSingle('type', XenForo_Input::STRING);
		$creditModel = $this->_getCreditModel();
		$withdrawActions = $this->_getWithDrawActions();
		if(!$type){
			$type = key($withdrawActions);
		}

		$viewParams = $this->_getWithDrawParams($type, $withdrawActions);
		if (!is_array($viewParams))
		{
			return $viewParams;
		}
		if (!$viewParams)
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}
		$viewParams['type'] = $type;
		$viewParams['withdrawActions'] = $withdrawActions;
		return $this->responseView(
			'Brivium_Credits_ViewPublic_Credits_WithDraw',
			'BRC_withdraw',
			$viewParams
		);
	}

	public function actionDoWithDraw()
	{
		$this->_assertPostOnly();
		$data = $this->_input->filter(array(
			'amount' => XenForo_Input::UNUM,
			'currency_id' => XenForo_Input::UINT,
			'comment' => XenForo_Input::STRING,
			'redirect' => XenForo_Input::STRING,
			'sensitive_data' => XenForo_Input::STRING,
		));
		$redirect = ($data['redirect'] ? $data['redirect'] : $this->getDynamicRedirect());

		$visitor = XenForo_Visitor::getInstance()->toArray();


		/* @var $userModel XenForo_Model_user */
		$userModel = $this->getModelFromCache('XenForo_Model_User');
		$creditModel = $this->_getCreditModel();

		$currencyObj = XenForo_Application::get('brcCurrencies');

		list($event, $currency) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('withdraw',$data['currency_id']);

		if(!$currency['withdraw']){
			return $this->responseError(new XenForo_Phrase('BRC_currency_cannot_withdraw'));
		}

		if ($data['amount'] <= 0) {
			return $this->responseError(new XenForo_Phrase('BRC_not_valid_amount'));
		}

		$userCredit = $visitor[$currency['column']];

		if($currency['withdraw_min'] > 0 && $data['amount'] < $currency['withdraw_min']){
			return $this->responseError(new XenForo_Phrase('BRC_amount_using_must_bigger_than_x',array('amount' => $currencyObj->currencyFormat($currency['withdraw_min'],false,$currency['currency_id']))));
		}
		if($currency['withdraw_max'] > 0 && $data['amount'] > $currency['withdraw_max']){
			return $this->responseError(new XenForo_Phrase('BRC_amount_using_must_smaller_than_x',array('amount' => $currencyObj->currencyFormat($currency['withdraw_max'],false,$currency['currency_id']))));
		}

		if ( ($userCredit - $data['amount']) < 0) {
			return $this->responseError(new XenForo_Phrase('BRC_not_enough_amount',array('amount' => $currencyObj->currencyFormat($data['amount'],false,$currency['currency_id']))));
		}

		//$hash = md5(implode(',',array_keys($receivers)) . $formData['amount'] . $balanceAfter);
		$dataCredit = array(
			'user_action_id' 	=>	$visitor['user_id'],
			'amount' 			=>	-$data['amount'],
			'user'				=>	$visitor,
			'currency_id'		=>	$currency['currency_id'],
			'message' 			=>	$data['comment'],
			'moderate' 			=>	true,
			'updateUser' 		=>	true,
			'extraData' 		=>	array('sensitive_data'=>$data['sensitive_data']),
		);
		$errorString = '';
		$creditModel->setIsWaitSubmit(true);
		$creditModel->updateUserCredit('withdraw',$visitor['user_id'],$dataCredit,$errorString);
		if($errorString)return $this->responseError($errorString);
		$creditModel->commitUpdate();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirect,
			new XenForo_Phrase('BRC_transaction_processed_successfully')
		);
	}

	public function actionGetWithdrawAmount()
	{
		$options = XenForo_Application::get('options');
		$amount = $this->_input->filterSingle('amount', XenForo_Input::UNUM);

		$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);

		$currency = XenForo_Application::get('brcCurrencies')->$currencyId;
		$options = XenForo_Application::getOptions();
		$withdrawRate = $options->BRC_withdrawRate;
		$withdrawCurrency = $options->BRC_withdrawCurrency;
		if($withdrawRate && $withdrawRate!=0 && !empty($currency['value']) && $currency['value'] != 0){
			$amount = $amount/($withdrawRate * $currency['value']);
			$amount = XenForo_Locale::numberFormat($amount, $options->BRC_withdrawDecimal);
			$amount = $amount . ' ' .$withdrawCurrency;
		}
		$withdrawRate = $options->BRC_withdrawRate;

		$viewParams = array(
			'amount' => $amount,
			'currency' => $currency,
		);

		return $this->responseView(
			'Brivium_Credits_ViewPublic_Credits_GetAmountWithdraw',
			'',
			$viewParams
		);
	}

	public function actionStealCredits()
	{
		if ($this->isConfirmedPost())
		{
			$this->_assertPostOnly();
			$data = $this->_input->filter(array(
				'victim' => XenForo_Input::STRING,
				'amount' => XenForo_Input::UNUM,
				'currency_id' => XenForo_Input::UINT,
				'anonymous' => XenForo_Input::UINT,
				'comment' => XenForo_Input::STRING,
				'redirect' => XenForo_Input::STRING,
			));
			$redirect = ($data['redirect'] ? $data['redirect'] : $this->getDynamicRedirect());

			$visitor = XenForo_Visitor::getInstance()->toArray();

			/* @var $userModel XenForo_Model_user */
			$userModel = $this->getModelFromCache('XenForo_Model_User');
			$creditModel = $this->_getCreditModel();

			$currencyObj = XenForo_Application::get('brcCurrencies');

			list($event, $currency) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('steal',$data['currency_id']);

			if ($data['amount'] <= 0) {
				return $this->responseError(new XenForo_Phrase('BRC_not_valid_amount'));
			}

			if ($data['anonymous'] && !$creditModel->canAnonymousStealCredits()) {
				return $this->responseError(new XenForo_Phrase('BRC_do_not_have_permission_anonymous_steal'));
			}

			$creditModel->setIsWaitSubmit(true);

			$victim = $userModel->getUserByName($data['victim'], array('join' => XenForo_Model_User::FETCH_USER_FULL));
			$victimCredit = $victim[$currency['column']];
			if ($victim['user_id'] == $visitor['user_id']) {
				return $this->responseError(new XenForo_Phrase('BRC_you_can_not_steal_self'));
			}
			if ( ($victimCredit - $data['amount']) < 0) {
				return $this->responseError(new XenForo_Phrase('BRC_x_have_ony_y_to_steal',array('user' => $data['victim'],'amount' => $currencyObj->currencyFormat($victimCredit,false,$currency['currency_id']))));
			}

			$dataCredit = array(
				'user_action_id' 	=>	$data['anonymous']?$victim['user_id']:$visitor['user_id'],
				'amount' 			=>	-$data['amount'],
				'user'				=>	$victim,
				'currency_id'		=>	$currency['currency_id'],
				'message' 			=>	$data['anonymous']?(new XenForo_Phrase('BRC_anonymous') .': '. $data['comment']):$data['comment'],
				'extraData' 		=>	array('type'	=>	'victim', 'anonymous'	=>	$data['anonymous'])
			);
			$creditModel->updateUserCredit('steal',$victim['user_id'],$dataCredit,$errorString);
			if($errorString)return $this->responseError($errorString);
			$dataCredit2 = array(
				'user_action_id' 	=>	$victim['user_id'],
				'amount' 			=>	$data['amount'],
				'message' 			=>	$data['anonymous']?(new XenForo_Phrase('BRC_anonymous') .': '. $data['comment']):$data['comment'],
				'currency_id'		=>	$currency['currency_id'],
				'ignore_include' 	=>	true,
				'user'				=>	$visitor,
				'extraData' 		=>	array('type' =>	'thief', 'anonymous'	=>	$data['anonymous'])
			);

			$errorString = '';
			$creditModel->updateUserCredit('steal',$victim['user_id'],$dataCredit2,$errorString);
			if($errorString)return $this->responseError($errorString);
			$creditModel->commitUpdate();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect,
				new XenForo_Phrase('BRC_transaction_processed_successfully')
			);
		}
		else // show delete confirmation prompt
		{
			$currencies = $this->_getCreditHelper()->assertCurrenciesValidAndViewable('steal');
			if(!$currencies){
				return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
			}
			if(!$this->_getCreditModel()->canStealCredits()){
				return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
			}

			$data = $this->_input->filter(array(
				'victim' => XenForo_Input::STRING,
				'currency_id' => XenForo_Input::UINT,
			));
			$viewParams = array(
				'victim' => $data['victim'],
				'currencyId' => $data['currency_id'],
				'currencies' => $currencies,
				'canAnonymousStealCredits' => $this->_getCreditModel()->canAnonymousStealCredits(),
			);

			return $this->_getWrapper(
				'credits', 'steal',
				$this->responseView(
					'Brivium_Credits_ViewAdmin_Credits_WithDraw',
					'BRC_steal_credit',
					$viewParams
				)
			);
		}
	}

	protected function _preDispatch($action)
	{
		$this->_assertRegistrationRequired();
		if (!$this->_getCreditModel()->canUseCredits($error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}

	protected function _getDefaultTransactionSort()
	{
		return array('transaction_date',  'desc');
	}

	protected function _getTransactionSortFields()
	{
		return array('action_id', 'currency_id', 'transaction_date', 'user_id', 'user_action_id', 'amount');
	}

	protected function _getFilterParams()
	{
		return $this->_input->filter(array(
			'order' => XenForo_Input::STRING,
			'action_id' => XenForo_Input::STRING,
			'currency_id' => XenForo_Input::UINT,
			'username' => XenForo_Input::STRING,
			'start' => XenForo_Input::STRING,
			'end' => XenForo_Input::STRING
		));
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
		return $this->_getCreditHelper()->getWrapper($selectedGroup, $selectedLink, $subView);
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
