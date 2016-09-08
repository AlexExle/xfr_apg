<?php

class Brivium_Credits_ControllerAdmin_Transaction extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('BRC_transaction');
	}

	public function actionIndex()
	{
		if ($this->_input->inRequest('delete_selected'))
		{
			return $this->responseReroute(__CLASS__, 'delete');
		}
		if ($this->_input->inRequest('update'))
		{
			return $this->responseReroute(__CLASS__, 'update');
		}
		$export = false;
		if ($this->_input->inRequest('export'))
		{
			$export = true;
		}

		$input = $this->_getFilterParams();

		$dateInput = $this->_input->filter(array(
			'start' => XenForo_Input::DATE_TIME,
			'end' => XenForo_Input::DATE_TIME,
		));

		$transactionModel = $this->_getTransactionModel();

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

		$conditions = array(
			'action_id' => $input['action_id'],
			'currency_id' => $input['currency_id'],
			'user_id' => $userId,
			'start' => $dateInput['start'],
			'end' => $dateInput['end'],
		);

		if($moderate){
			$conditions['moderate'] = $moderate;
		}
		if(!$export){
			$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
			$perPage = 50;

			$fetchOptions = array(
				'page' => $page,
				'perPage' => $perPage,
				'join' =>  Brivium_Credits_Model_Transaction::FETCH_TRANSACTION_FULL
			);
		}else{
			$fetchOptions = array(
				'join' =>  Brivium_Credits_Model_Transaction::FETCH_TRANSACTION_FULL
			);
		}
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
		$transactions = $transactionModel->prepareTransactions($transactions);
		if($export){
			$name = "Brivium_Transaction_Export_" . date("Y-m-d_h-i-s-A") . ".csv";
			$fileName = "data/transactions/". $name;
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
				'Brivium_Credits_ViewAdmin_Credits_Transactions',
				'',
				$viewParams
			);
		}

		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();

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
			'total' =>	$transactionModel->countTransactions($conditions)
		);

		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_Transactions', 'BRC_transaction_list', $viewParams);
	}

	public function actionView()
	{
		$transactionId = $this->_input->filterSingle('transaction_id', XenForo_Input::UINT);
		$transactionModel = $this->_getTransactionModel();
		$fetchOptions = array(
			'join' =>  Brivium_Credits_Model_Transaction::FETCH_TRANSACTION_FULL
		);
		$transaction = $transactionModel->getTransactionById($transactionId,$fetchOptions);
		if(!$transaction){
			return $this->responseError(new XenForo_Phrase('BRC_requested_transaction_not_found'));
		}
		$viewParams = array(
			'transaction' => $transactionModel->prepareTransaction($transaction),
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_ViewTransaction', 'BRC_transaction_view', $viewParams);
	}

	public function actionRaw()
	{
		$transactionId = $this->_input->filterSingle('transaction_id', XenForo_Input::UINT);
		$transactionModel = $this->_getTransactionModel();
		$fetchOptions = array(
			'join' =>  Brivium_Credits_Model_Transaction::FETCH_TRANSACTION_FULL
		);
		$transaction = $transactionModel->getTransactionById($transactionId,$fetchOptions);
		if(!$transaction){
			return $this->responseError(new XenForo_Phrase('BRC_requested_transaction_not_found'));
		}
		print_r($transaction);exit();
		$viewParams = array(
			'transaction' => $transactionModel->prepareTransaction($transaction),
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_ViewTransaction', 'BRC_transaction_view', $viewParams);
	}

	public function actionSave()
	{
		$transactionId = $this->_input->filterSingle('transaction_id', XenForo_Input::UINT);
		$complete = $this->_input->filterSingle('complete', XenForo_Input::UINT);
		$notifyUser = $this->_input->filterSingle('notify_user', XenForo_Input::UINT);
		$moderate = $complete?false:true;
		$transactionModel = $this->_getTransactionModel();
		$fetchOptions = array(
			'join' =>  Brivium_Credits_Model_Transaction::FETCH_TRANSACTION_FULL
		);
		$transaction = $transactionModel->getTransactionById($transactionId,$fetchOptions);

		if(!$transaction){
			return $this->responseError(new XenForo_Phrase('BRC_requested_transaction_not_found'));
		}

		$dw = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Transaction');
		$dw->setExistingData($transactionId);
		$dw->setOption(Brivium_Credits_DataWriter_Transaction::OPTION_ALLOW_CREDIT_CHANGE, false);
		$dw->set('moderate', $moderate);
		$dw->save();
		if($notifyUser && $complete){
			$visitor = XenForo_Visitor::getInstance()->toArray();
			if($transaction['user_id']==$visitor['user_id']){
				$user = $visitor;
			}else{
				$user = $this->getModelFromCache('XenForo_Model_User')->getUserById($transaction['user_id'], array('join'=> XenForo_Model_User::FETCH_USER_OPTION | XenForo_Model_User::FETCH_USER_PERMISSIONS));
			}
			if(XenForo_Model_Alert::userReceivesAlert($user, 'credits', $transaction['action_id'])){
				$extraData = array(
					'alert_type' =>' complete',
					'amount' => $transaction['amount'],
				);
				$this->_getCreditModel()->createTransactionAlert($transaction['user_id'], $transaction['user_id'], '', $transactionId, $transaction['action_id'], $extraData);
			}
		}

		$filterParams = $this->_getFilterParams();
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('brc-transactions', null, $filterParams)
		);
	}

	public function actionUpdate()
	{
		$transactionModel = $this->_getTransactionModel();

		$filterParams = $this->_getFilterParams();

		$transactionIds = $this->_input->filterSingle('transaction_ids', array(XenForo_Input::UINT, 'array' => true));

		if ($transactionId = $this->_input->filterSingle('transaction_id', XenForo_Input::UINT))
		{
			$transactionIds[] = $transactionId;
		}
		$transactionId = $this->_input->filterSingle('transaction_id', XenForo_Input::UINT);
		foreach ($transactionIds AS $transactionId)
		{
			$dw = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Transaction');
			$dw->setExistingData($transactionId);
			$dw->set('moderate',0);
			$dw->save();
		}
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('brc-transactions', null, $filterParams)
		);
	}

	public function actionDelete()
	{
		$transactionModel = $this->_getTransactionModel();

		$filterParams = $this->_getFilterParams();

		$transactionIds = $this->_input->filterSingle('transaction_ids', array(XenForo_Input::UINT, 'array' => true));

		if ($transactionId = $this->_input->filterSingle('transaction_id', XenForo_Input::UINT))
		{
			$transactionIds[] = $transactionId;
		}
		$transactionId = $this->_input->filterSingle('transaction_id', XenForo_Input::UINT);

		if ($this->isConfirmedPost())
		{
			foreach ($transactionIds AS $transactionId)
			{
				$dw = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Transaction');
				$dw->setExistingData($transactionId);
				$dw->delete();
			}
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('brc-transactions', null, $filterParams)
			);
		}
		else // show confirmation dialog
		{
			$fetchOptions = array(
				'join' =>  Brivium_Credits_Model_Transaction::FETCH_TRANSACTION_FULL
			);
			$viewParams = array(
				'transactionIds' => $transactionIds,
				'filterParams' => $filterParams
			);

			if (count($transactionIds) == 1)
			{
				list($transactionId) = $transactionIds;
				$transactions = $transactionModel->getTransactionById($transactionId,$fetchOptions);
				if($transactions)
				$viewParams['transaction'] = $transactionModel->prepareTransaction($transactions);
			}
			return $this->responseView('Brivium_Credits_ViewAdmin_Credits_DeleteTransaction', 'BRC_transaction_delete', $viewParams);
		}
	}

	public function actionPaypalTransactionLog()
	{
		$paymentModel = $this->_getPaypalPaymentModel();

		$logId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		if ($logId)
		{
			$log = $paymentModel->getTransactionLogById($logId);
			if (!$log)
			{
				return $this->responseError(new XenForo_Phrase('requested_log_entry_not_found'));
			}

			$log['transactionDetails'] = @unserialize($log['transaction_details']);

			$viewParams = array(
				'log' => $log
			);
			return $this->responseView('Brivium_Credits_ViewAdmin_Credits_TransactionLogView', 'BRC_paypal_transaction_log_view', $viewParams);
		}

		$conditions = $this->_input->filter(array(
			'transaction_id' => XenForo_Input::STRING,
			'subscriber_id' => XenForo_Input::STRING,
			'username' => XenForo_Input::STRING,
			'user_id' => XenForo_Input::UINT,
			'user_upgrade_id' => XenForo_Input::UINT
		));

		if ($conditions['username'])
		{
			/** @var XenForo_Model_User $userModel */
			$userModel = $this->getModelFromCache('XenForo_Model_User');
			$user = $userModel->getUserByName($conditions['username']);
			if (!$user)
			{
				return $this->responseError(new XenForo_Phrase('requested_user_not_found'));
			}

			$conditions['user_id'] = $user['user_id'];
			$conditions['username'] = '';
		}

		foreach ($conditions AS $condition => $value)
		{
			if (!$value)
			{
				unset($conditions[$condition]);
			}
		}

		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$perPage = 20;

		$logs = $paymentModel->getTransactionLogs($conditions, array(
			'page' => $page,
			'perPage' => $perPage
		));
		if (!$logs)
		{
			return $this->responseMessage(new XenForo_Phrase('no_results_found'));
		}

		$totalLogs = $paymentModel->countTransactionLogs($conditions);

		$viewParams = array(
			'logs' => $logs,
			'totalLogs' => $totalLogs,

			'page' => $page,
			'perPage' => $perPage,

			'conditions' => $conditions
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Credits_TransactionLog', 'BRC_paypal_transaction_log', $viewParams);
	}

	public function actionPaypalTransactionLogSearch()
	{
		$viewParams = array(
		);
		return $this->responseView(
			'Brivium_Credits_ViewAdmin_Credits_TransactionLogSearch',
			'BRC_paypal_transaction_log_search',
			$viewParams
		);
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
	 * Gets the credit model.
	 *
	 * @return Brivium_Credits_Model_Credit
	 */
	protected function _getCreditModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Credit');
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

	protected function _getPaypalPaymentModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Payment_PayPal_Model_Payment');
	}
}