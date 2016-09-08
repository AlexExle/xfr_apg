<?php

class Brivium_Credits_Payment_PayPal_PayPal
{
	/**
	 * @var Zend_Controller_Request_Http
	 */
	protected $_request;

	/**
	 * @var XenForo_Input
	 */
	protected $_input;

	/**
	 * List of filtered input for handling a callback.
	 *
	 * @var array
	 */
	protected $_filtered = null;

	/**
	 * Info about the user the upgrade is for.
	 *
	 * @var array|false
	 */
	protected $_user = false;


	protected $_sanbox = false;
	protected $_event = false;
	protected $_currency = false;

	protected $_paymentModel = null;

	/**
	 * Initializes handling for processing a request callback.
	 *
	 * @param Zend_Controller_Request_Http $request
	 */
	public function initCallbackHandling(Zend_Controller_Request_Http $request)
	{
		$this->_request = $request;
		$this->_input = new XenForo_Input($request);
		$this->_filtered = $this->_input->filter(array(
			'test_ipn'			=> XenForo_Input::UINT,
			'business'			=> XenForo_Input::STRING,
			'receiver_email'	=> XenForo_Input::STRING,
			'txn_type'			=> XenForo_Input::STRING,
			'txn_id'			=> XenForo_Input::STRING,
			'parent_txn_id'		=> XenForo_Input::STRING,
			'mc_currency'		=> XenForo_Input::STRING,
			'mc_gross'			=> XenForo_Input::UNUM,
			'payment_status'	=> XenForo_Input::STRING,
			'custom'			=> XenForo_Input::STRING,
			'subscr_id'			=> XenForo_Input::STRING
		));
		$this->_filtered['mc_gross_string'] = $this->_input->filterSingle('mc_gross', XenForo_Input::STRING);

		$this->_paymentModel =  XenForo_Model::create('Brivium_Credits_Payment_PayPal_Model_Payment');
	}

	/**
	 * Validates the callback request is valid. If failure happens, the response should
	 * tell the processor to retry.
	 *
	 * @param string $errorString Output error string
	 *
	 * @return boolean
	 */
	public function validateRequest(&$errorString)
	{
		$options = XenForo_Application::get('options');
		try
		{
			if ($this->_filtered['test_ipn'] && $options->BRCP_enableSanbox)
			{
				$this->_sanbox = true;
				$validator = XenForo_Helper_Http::getClient('https://www.sandbox.paypal.com/cgi-bin/webscr');
			}
			else
			{
				$validator = XenForo_Helper_Http::getClient('https://www.paypal.com/cgi-bin/webscr');
			}
			$validator->setParameterPost('cmd', '_notify-validate');
			$validator->setParameterPost($_POST);
			$validatorResponse = $validator->request('POST');

			if (!$validatorResponse || $validatorResponse->getBody() != 'VERIFIED' || $validatorResponse->getStatus() != 200)
			{
				$errorString = 'Request not validated';
				return false;
			}
		}
		catch (Zend_Http_Client_Exception $e)
		{
			$errorString = 'Connection to PayPal failed';
			return false;
		}
		if($options->BRCP_enableSanbox){
			if (strtolower($this->_filtered['business']) != trim(strtolower($options->BRCP_ppSanboxEmail))
				&& strtolower($this->_filtered['receiver_email']) != trim(strtolower($options->BRCP_ppSanboxEmail))
			)
			{
				$errorString = 'Invalid business or receiver_email';
				return false;
			}
		}else{
			if (strtolower($this->_filtered['business']) != trim(strtolower($options->BRCP_ppBusinessEmail))
				&& strtolower($this->_filtered['receiver_email']) != trim(strtolower($options->BRCP_ppBusinessEmail))
			)
			{
				$errorString = 'Invalid business or receiver_email';
				return false;
			}
		}


		return true;
	}

	/**
	 * Validates pre-conditions on the callback. These represent things that likely wouldn't get fixed
	 * (and generally shouldn't happen), so retries are not necessary.
	 *
	 * @param string $errorString
	 *
	 * @return boolean
	 */
	public function validatePreConditions(&$errorString)
	{
		$itemParts = explode(',', $this->_filtered['custom'], 5);
		if (count($itemParts) != 5)
		{
			$errorString = 'Invalid item (custom)';
			return false;
		}

		list($userId, $currencyId, $type, $validationType, $validation) = $itemParts;
		// $validationType allows validation method changes
		$user = XenForo_Model::create('XenForo_Model_User')->getUserById($userId,
			array('join'=> XenForo_Model_User::FETCH_USER_PROFILE |
				XenForo_Model_User::FETCH_USER_OPTION |
				XenForo_Model_User::FETCH_USER_PERMISSIONS));

		if (!$user)
		{
			$errorString = 'Invalid user';
			return false;
		}
		$this->_user = $user;

		$event = array();
		$actionObj = XenForo_Application::get('brcActionHandler');
		$events = $actionObj->getActionEvents('paypalPayment', array('currency_id' => $currencyId));
		if($allowEventId = $actionObj->checkTriggerActionEvents($events, $user)){
			if(isset($events[$allowEventId])){
				$event = $events[$allowEventId];
			}
		}

		if(!$event){
			$errorString = 'Invalid Event';
			return false;
		}
		$currency = XenForo_Application::get('brcCurrencies')->$currencyId;


		$this->_event = $event;
		$this->_currency = $currency;

		$tokenParts = explode(',', $validation);
		if (count($tokenParts) != 3 || sha1($tokenParts[1] . $user['csrf_token']) != $tokenParts[2])
		{
			$errorString = 'Invalid validation';
			return false;
		}

		if (!$this->_filtered['txn_id'])
		{
			$errorString = 'No txn_id';
			return false;
		}

		$transaction = $this->_paymentModel->getProcessedTransactionLog($this->_filtered['txn_id']);

		if ($transaction)
		{
			$errorString = 'Transaction already processed';
			return false;
		}

		return true;
	}

	/**
	 * Once all conditions are validated, process the transaction.
	 *
	 * @return array [0] => log type (payment, cancel, info), [1] => log message
	 */
	public function processTransaction()
	{
		$options = XenForo_Application::get('options');
		switch ($this->_filtered['txn_type'])
		{
			case 'web_accept':
			case 'subscr_payment':
				if ($this->_filtered['payment_status'] == 'Completed')
				{
					$moneyPaid = $this->_filtered['mc_gross'];
					$moneyPaidString = $this->_filtered['mc_gross_string'];
					$event = $this->_event;
					if(isset($event['extra_data'], $event['extra_data']['price_type'], $event['extra_data']['step_price']) && $event['extra_data']['price_type']=='step'){
						if(!empty($event['extra_data']['step_price'][$moneyPaid])){
							$creditReceive = $event['extra_data']['step_price'][$moneyPaid]['credit'];
						}elseif(!empty($event['extra_data']['step_price'][$moneyPaidString])){
							$creditReceive = $event['extra_data']['step_price'][$moneyPaidString]['credit'];
						}else{
							$creditReceive = 0;
						}
					}else{
						$options = XenForo_Application::get('options');
						if($options->BRCP_creditPurchaseNumber['max'] > 0 && $moneyPaid > $options->BRCP_creditPurchaseNumber['max']){
							$moneyPaid = $options->BRCP_creditPurchaseNumber['max'];
						}
						if(!empty($event['extra_data']['fee'])){
							$moneyPaid = $moneyPaid - $event['extra_data']['fee'];
						}
						if(!empty($event['extra_data']['tax'])){
							$moneyPaid = $moneyPaid * (100 - $event['extra_data']['tax'])/100;
						}
						$creditReceive = $event['multiplier']>0?round($moneyPaid/$event['multiplier'], $this->_currency['decimal_place']):$moneyPaid;
					}
					$creditReceive += $event['amount'];

					$dataCredit = array(
						'amount' 			=>	$creditReceive,
						'user'				=>	$this->_user,
						'currency_id'		=>	$this->_currency['currency_id'],
						'message' 			=>	'PayPal Payment '.$this->_filtered['payment_status'],
					);
					$this->_getCreditModel()->updateUserCredit('paypalPayment',$this->_user['user_id'],$dataCredit);
					$this->_getPaymentPaypalModel()->sendEmailPayment($this->_user, $dataCredit, $this->_event, $this->_filtered);
					$this->_filtered['credit_receive'] = $creditReceive;
					$this->_filtered['currency_id'] = $this->_currency['currency_id'];
					return array('payment', 'Payment received, recharged', $this->_filtered);
				}
				break;
		}

		if ($this->_filtered['payment_status'] == 'Refunded' || $this->_filtered['payment_status'] == 'Reversed')
		{
			$transaction = $this->_paymentModel->getLogByTransactionId($this->_filtered['parent_txn_id']);
			if(!empty($transaction['transaction_details'])){
				$detail = @unserialize($transaction['transaction_details']);
				$moneyPaid = $detail['mc_gross'];

				$event = $this->_event;
				if(isset($event['extra_data'], $event['extra_data']['price_type'], $event['extra_data']['step_price']) && $event['extra_data']['price_type']=='step'){
					if(!empty($event['extra_data']['step_price'][$moneyPaid])){
						$creditReceive = $event['extra_data']['step_price'][$moneyPaid]['credit'];
					}else{
						$creditReceive = 0;
					}
				}else{
					$options = XenForo_Application::get('options');
					if($options->BRCP_creditPurchaseNumber['max'] > 0 && $moneyPaid > $options->BRCP_creditPurchaseNumber['max']){
						$moneyPaid = $options->BRCP_creditPurchaseNumber['max'];
					}
					if(!empty($event['extra_data']['fee'])){
						$moneyPaid = $moneyPaid - $event['extra_data']['fee'];
					}
					if(!empty($event['extra_data']['tax'])){
						$moneyPaid = $moneyPaid * (100 - $event['extra_data']['tax'])/100;
					}
					$creditReceive = $event['multiplier']>0?round($moneyPaid/$event['multiplier'], $this->_currency['decimal_place']):$moneyPaid;
				}
				$creditReceive += $event['amount'];

				$dataCredit = array(
					'amount' 			=>	-$creditReceive,
					'user'				=>	$this->_user,
					'currency_id'		=>	$this->_currency['currency_id'],
					'message' 			=>	'PayPal Payment '.$this->_filtered['payment_status'],
				);
				$this->_getCreditModel()->updateUserCredit('paypalPaymentRe', $this->_user['user_id'], $dataCredit);
				$this->_filtered['credit_receive'] = -$creditReceive;
				$this->_filtered['currency_id'] = $this->_currency['currency_id'];
			}else{
				return array('cancel', 'Payment refunded/reversed Error, no parent transaction found',$this->_filtered);
			}
			return array('cancel', 'Payment refunded/reversed',$this->_filtered);
		}

		return array('info', 'OK, no action',$this->_filtered);
	}

	/**
	 * Get details for use in the log.
	 *
	 * @return array
	 */
	public function getLogDetails()
	{
		$details = $this->_filtered;
		$details['_callbackIp'] = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false);

		return $details;
	}

	/**
	 * Gets the transaction ID.
	 *
	 * @return string
	 */
	public function getTransactionId()
	{
		return isset($this->_filtered['txn_id'])?$this->_filtered['txn_id']:$this->_filtered['tx'];
	}

	/**
	 * Gets the ID of the processor.
	 *
	 * @return string
	 */
	public function getProcessorId()
	{
		return 'paypal';
	}

	/**
	 * Logs the request.
	 *
	 * @param string $type Log type (info, payment, cancel, error)
	 * @param string $message Log message
	 * @param array $extra Extra details to log (not including output from getLogDetails)
	 */
	public function log($type, $message, array $extra)
	{
		$processor = $this->getProcessorId();
		$transactionId = $this->getTransactionId();
		$details = $this->getLogDetails() + $extra;
		if(!isset($this->_user['user_id'])){
			$userId = XenForo_Visitor::getUserId();
		}else{
			$userId = $this->_user['user_id'];
		}
		$this->_paymentModel->logPayment(
			array(
				'user_id'				=> $userId,
				'processor'				=> $processor,
				'transaction_id'		=> $transactionId,
				'transaction_type'		=> $type,
				'message'				=> $message,
				'is_sanbox'				=> $this->_sanbox,
				'transaction_details'	=> serialize($details),
				'log_date'				=> XenForo_Application::$time,
			)
		);
	}
	/**
	 * Gets the action model.
	 *
	 * @return Brivium_Credits_Model_Credit
	 */
	protected function _getCreditModel()
	{
		return XenForo_Model::create('Brivium_Credits_Model_Credit');
	}
	protected function _getPaymentPaypalModel()
	{
		return XenForo_Model::create('Brivium_Credits_Payment_PayPal_Model_Payment');
	}
}