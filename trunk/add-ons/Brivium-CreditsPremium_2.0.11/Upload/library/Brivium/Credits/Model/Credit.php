<?php
class Brivium_Credits_Model_Credit extends XenForo_Model
{
	/**
	 * Determines if this process is doing a bulk rebuild.
	 *
	 * @var boolean
	 */
	protected $_isBulk = false;
	protected $_waitSubmit = false;
	protected $_defaultData = array();
	protected $_bulkInserts = array();
	protected $_bulkAlerts = array();
	protected $_bulkAlertUserIds = array();
	protected $_bulkUpdates = array();
	protected $_bulkInsertLength = 0;
	protected $_bulkAlertLength = 0;

	protected $_userCreditChanges = array();

	public function getWaitSubmit()
	{
		return $this->_waitSubmit;
	}

	protected function _getDefaultData()
	{
		if(empty($this->_defaultData)){
			$this->_defaultData = array(
				// User is associated with this event of $user ( default System = 0 )  (int)
				'user_action_id' 		=>	0,

				'transaction_key' 		=>	0,
				// User for trigger action
				'user' 					=>	array(),
				// User trigger
				'user_action' 			=>	array(),
				// Date of transaction (int)
				'transaction_date' 		=>	XenForo_Application::$time,
				// Set amount override event's amount ( if = 0 , amount = event.amount ) (double)
				'amount' 				=>	0,
				// Number will calculate with event's multiplier   ( amount = amount + event.multiplier * multiplier )  (int)
				'multiplier' 			=>	0,
				// Multipliter Amount  (  amount  = amount x multiAmount )  (int)
				'multi_amount' 			=>	0,
				// Comment for this transaction (string)
				'message' 				=>	'',
				// Set state for transaction  (string)
				'transaction_state' 	=>	'',
				// Data forum id  (int)
				'node_id' 				=>	0,

				// Maximum times per day  (int)
				'times' 				=>	0,
				// Maximum times After this event has occured.  (int)
				'apply_max' 			=>	0,
				// Timespan in seconds that the above maximum is enforced. (int)
				'max_time' 				=>	0,
				// Trigger  specify event
				'event_id' 				=>	0,
				// Trigger events for currency
				'currency_id' 			=>	0,

				'content_id' 			=>	0,
				'content_type'			=>	'',

				// Error phrase if trigger below minimum handling
				'errorMinimumHandle'	=>	'',
				// Is ignore min handle.  (boolen)
				'ignore_min_handle' 	=>	false,
				// Timespan in seconds that the above maximum is enforced. (int)
				'moderate' 				=>	false,
				// Ignored check include (forum and user group). (boolen)
				'ignore_include' 		=>	false,
				// Ignored check maximum perday and maximum times trigger event.  (boolen)
				'ignore_maximum' 		=>	false,
				// Ignored check permission for register or ....
				'ignore_permission' 		=>	false,
				// Update user credit field or not (Using this when you want update manual) (boolen)
				'updateUser' 			=>	false,
				// Allow member's credit can negative after transaction
				'allow_negative' 		=>	false,
				// Extra data for transaction and alert
				'is_bulk' 				=>	false,
				'extraData' 			=>	array()
			);
		}
		return $this->_defaultData;
	}

	/**
	 * update user credit
	 *
	 * @param string $actionId
	 * @param integer $userId
	 * @param array $triggerData
	 * @param string $errorString
	 */
	public function updateUserCredit($actionId, $userId, array $triggerData = array(), &$errorString = '')
	{
		$actionObj = XenForo_Application::get('brcActionHandler');
		$actionHandler = $actionObj->getActionHandler($actionId);
		if($actionHandler && $actionHandler->canUseAction()){
			if(!$events = $actionObj->getActionEvents($actionId, $triggerData)){
				$errorString = new XenForo_Phrase('BRC_no_events_have_been_created_for_action_yet');
				return false;
			}
			$visitor = XenForo_Visitor::getInstance()->toArray();
			// SET DEFAULT DATA
			$defaultData = $this->_getDefaultData();
			$triggerData = array_merge($defaultData, $triggerData);


			$triggerUserId 		= $triggerData['user_action_id'];
			$user 				= $triggerData['user'];
			$triggerUser 		= $triggerData['user_action'];
			$ignorePermission 	= $triggerData['ignore_permission'];

			$triggerData['extraData']['ignoreInclude'] = $triggerData['ignore_include'];


			$userModel = $this->_getUserModel();
			// SET USER
			if(!empty($user['user_id'])){
				$userId = $userId?$userId:$user['user_id'];
			}else if($userId){
				if($userId==$visitor['user_id']){
					$user = $visitor;
				}else{
					$user = $userModel->getUserById($userId, array('join'=> XenForo_Model_User::FETCH_USER_OPTION | XenForo_Model_User::FETCH_USER_PERMISSIONS));
				}
			}else{
				$errorString = new XenForo_Phrase('requested_user_not_found');
				return false;
			}
			if (!$user || !is_array($user) || empty($user['user_id']))
			{
				$errorString = new XenForo_Phrase('requested_user_not_found');
				return false;
			}

			// SET TRIGGER USER
			if(!empty($triggerUser['user_id'])){
				$triggerUserId = $triggerUserId?$triggerUserId:$triggerUser['user_id'];
			}else if($triggerUserId){
				if($triggerUserId==$visitor['user_id']){
					$triggerUser = $visitor;
				}else{
					$triggerUser = $userModel->getUserById($triggerUserId, array('join'=> XenForo_Model_User::FETCH_USER_OPTION | XenForo_Model_User::FETCH_USER_PERMISSIONS));
				}
			}else{
				$triggerUser = $user;
			}
			if(!$ignorePermission){
				if(!isset($user['global_permission_cache'])){
					$user = $userModel->setPermissionsFromUserId($user,$user['user_id']);
				}

				$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);

				if (!XenForo_Permission::hasPermission($user['permissions'], 'BR_CreditsPermission', 'useCredits')){
					$errorString = new XenForo_Phrase('do_not_have_permission');
					return false;
				}
			}

			$triggerData['user'] = $user;
			$triggerData['user_action'] = $triggerUser;
			$triggerData['is_bulk'] = $this->_isBulk;

			$actionHandler->setCreditModel($this);

			$eventIds = array();

			foreach($events AS $event){
				$errorString = '';
				if(!$triggerData['ignore_include'] && !$actionObj->checkTriggerActionEvent($event, $user, $triggerData['node_id'])){
					$errorString = new XenForo_Phrase('do_not_have_permission');
					continue;
				}
				$isBreak = false;
				$amountUpdate = $actionHandler->triggerEvent($event, $user, $triggerData, $errorString, $isBreak);
				if(!$amountUpdate){
					if($isBreak){
						return false;
					}
					continue;
				}else{
					$eventIds[] = $event['event_id'];
				}
			}
			if($eventIds && !$this->_waitSubmit){
				$this->commitUpdate();
			}
			return $eventIds;
		}else{
			$errorString = new XenForo_Phrase('BRC_requested_action_not_found');
			return false;
		}
	}

	public function commitUpdate($updateUser = true)
	{
		if(!empty($this->_bulkInserts)){
			$this->_pushToTransaction($this->_bulkInserts);
			$this->_bulkInserts = array();
			$this->_bulkInsertLength = 0;
		}
		if(!empty($this->_bulkAlerts)){
			$this->_pushToAlert($this->_bulkAlerts);
			$this->_increaseUnreadAlert($this->_bulkAlertUserIds);
			$this->_bulkAlertUserIds = array();
			$this->_bulkAlerts = array();
			$this->_bulkInsertLength = 0;
		}
		if(!empty($this->_bulkUpdates) && $updateUser){
			$this->_updateUserCredits($this->_bulkUpdates);
			$this->_bulkUpdates = array();
			$this->_bulkAlertLength = 0;
		}
		return true;
	}

	/**
	 * Add user Transaction Simple with delay option
	 *
	 * @param array $data
	 */
	public function addUserTransaction($actionId, $eventId, $user, $triggerUser,
		$contentId, $contentType, $transactionDate,
		$amount, $currency, $multiplier, $message,
		$moderate, $transactionState, $extraData, $alert, $updateUser = false)
	{
		$sensitiveData = !empty($extraData['sensitive_data'])?$extraData['sensitive_data']:'';
		unset($extraData['sensitive_data']);
		$db = $this->_getDb();

		$this->insertIntoTransaction(
			$actionId, $eventId, $currency['currency_id'],
			$user['user_id'], $triggerUser['user_id'], $contentId, $contentType,
			$transactionDate, $amount, $multiplier,
			$message, $sensitiveData, $moderate, $transactionState, $extraData
		);
		if($alert){
			$this->insertIntoAlert($user['user_id'], $triggerUser['user_id'], $triggerUser['username'], $eventId, $actionId, $transactionDate, $extraData);
		}
		if(!$moderate || $amount < 0 || $updateUser){
			if(!isset($this->_userCreditChanges[$actionId])){
				$this->_userCreditChanges[$actionId] = array(
					'earn' => 0,
					'spend' => 0,
				);
			}
			if($amount < 0){
				$this->_userCreditChanges[$actionId]['spend'] += $amount;
			}else{
				$this->_userCreditChanges[$actionId]['earn'] += $amount;
			}

			if($currency['negative_handle']=='reset' && ($user[$currency['column']] + $amount < 0)){
				$amount = -$user[$currency['column']];
			}
			$this->updateUserCredits($user['user_id'],$amount,$currency['column']);
		}

		return true;
	}

	/**
	 * Update user credits
	 *
	 * @param array $data
	 */
	public function updateUserCredits($userId, $amount, $column)
	{
		$db = $this->_getDb();
		$row = ' `'.$column.'` = `'.$column.'` + ' . $db->quote($amount);

		$this->_bulkUpdates[$userId][] = $row;
		return;
	}
	/**
	 * Update user credits
	 *
	 * @param array $data
	 */
	public function _updateUserCredits($records)
	{
		if (is_array($records))
		{
			foreach($records AS $userId=>$record){
				if (is_array($record))
				{
					$record = implode(',', $record);
				}

				if (!$record)
				{
					continue;
				}
				$this->_getDb()->query('
					UPDATE xf_user SET
						'. $record .'
					WHERE user_id = ?
				', $userId);
			}
		}else{
			return;
		}
		return true;
	}

	public function insertIntoTransaction(
		$actionId, $eventId, $currencyId,
		$userId, $triggerUserId, $contentId, $contentType,
		$transactionDate, $amount, $multiplier,
		$message, $sensitiveData, $moderate, $transactionState, $extraData
	)
	{
		$transactionKey = '';
		if(!empty($extraData['transaction_key'])){
			$transactionKey = $extraData['transaction_key'];
		}
		if(isset($extraData)){
			$extraData = serialize($extraData);
		}

		$db = $this->_getDb();

		$row = '(' . $db->quote($actionId)
			. ', ' . $db->quote($transactionKey)
			. ', ' . $db->quote(intval($eventId))
			. ', ' . $db->quote(intval($currencyId))
			. ', ' . $db->quote(intval($userId))
			. ', ' . $db->quote(intval($triggerUserId))
			. ', ' . $db->quote(intval($contentId))
			. ', ' . $db->quote($contentType)
			. ', 0' //. $db->quote($ownerId)
			. ', ' . $db->quote($transactionDate)
			. ', ' . $db->quote($amount) . ', ' . $db->quote($multiplier)
			. ', 0' //. $db->quote($negate)
			. ', ' . $db->quote($message)
			. ', ' . $db->quote($sensitiveData)
			. ', ' . $db->quote(intval($moderate))
			. ', 0' //. $db->quote(intval($reverted))
			. ', ' . $db->quote($transactionState)
			. ', ' . $db->quote($extraData) . ')';

		$this->_bulkInserts[] = $row;
		$this->_bulkInsertLength += strlen($row);

		if ($this->_bulkInsertLength > 500000)
		{
			$this->_pushToTransaction($this->_bulkInserts);

			$this->_bulkInserts = array();
			$this->_bulkInsertLength = 0;
		}
		return;
	}

	/**
	 * Runs the actual query to inserts transaction.
	 *
	 * @param string|array $record A record (SQL) or array of SQL
	 */
	protected function _pushToTransaction($record)
	{
		if (is_array($record))
		{
			$record = implode(',', $record);
		}

		if (!$record)
		{
			return;
		}
		$this->_getDb()->query('
			INSERT INTO xf_brivium_credits_transaction
				(action_id, transaction_key, event_id, currency_id, user_id, user_action_id,
				content_id, content_type, owner_id, transaction_date,
				amount, multiplier, negate, message, sensitive_data, moderate,
				is_revert, transaction_state, extra_data)
			VALUES
				' . $record
		);
		return $this->_getDb()->lastInsertId('xf_brivium_credits_transaction', 'transaction_id');
	}

	public function insertIntoAlert($alertUserId, $userId, $username, $eventId,
		$actionId, $transactionDate, array $extraData = null)
	{
		if(isset($extraData)){
			$extraData = serialize($extraData);
		}
		$db = $this->_getDb();
		$row = '(' . $db->quote(intval($alertUserId))
			. ', ' . $db->quote(intval($userId))
			. ', ' . $db->quote($username)
			. ', ' . $db->quote('credit')
			. ', ' . $db->quote(intval($eventId))
			. ', ' . $db->quote($actionId)
			. ', ' . $db->quote($transactionDate)
			. ', ' . $db->quote($extraData)
			. ')';

		$this->_bulkAlerts[] = $row;
		if(empty($this->_bulkAlertUserIds[$alertUserId])){
			$this->_bulkAlertUserIds[$alertUserId] = 0;
		}
		$this->_bulkAlertUserIds[$alertUserId] += 1;

		$this->_bulkAlertLength += strlen($row);

		if ($this->_bulkInsertLength > 500000)
		{
			$this->_pushToAlert($this->_bulkAlerts);
			$this->_increaseUnreadAlert($this->_bulkAlertUserIds);

			$this->_bulkAlerts = array();
			$this->_bulkAlertUserIds = array();
			$this->_bulkInsertLength = 0;
		}
		return;
	}

	protected function _increaseUnreadAlert($userIds)
	{
		if (empty($userIds) || !is_array($userIds))
		{
			return;
		}
		$db = $this->_getDb();
		foreach($userIds AS $userId=>$increaseNumber){
			if($increaseNumber > 0){
				$db->query('
					UPDATE xf_user SET
					alerts_unread = alerts_unread + ' . $db->quote($increaseNumber) . '
					WHERE user_id = ' . $db->quote($userId) . '
						AND alerts_unread < 65535
				');
			}
		}
	}

	/**
	 * Runs the actual query to inserts alert.
	 *
	 * @param string|array $record A record (SQL) or array of SQL
	 */
	protected function _pushToAlert($record)
	{
		if (is_array($record))
		{
			$record = implode(',', $record);
		}

		if (!$record)
		{
			return;
		}
		$this->_getDb()->query('
			INSERT INTO xf_user_alert
				(alerted_user_id, user_id, username, content_type, content_id, action, event_date, extra_data)
			VALUES
				' . $record
		);
		return $this->_getDb()->lastInsertId('xf_user_alert', 'alert_id');
	}

	public function updateInterestAmount($userIds, $events)
	{
		if(!$events || !$userIds){
			return false;
		}
		$db = $this->_getDb();
		$currencyObj = XenForo_Application::get('brcCurrencies');
		foreach($events AS $event){
			if(isset($event['amount'] ,$event['event_id']) && $event['amount'] && !empty($userIds[$event['event_id']])){
				$currency = $currencyObj->$event['currency_id'];

				$db->query('
					UPDATE ' . (XenForo_Application::get('options')->BRC_enableUpdateLowPriority ? 'LOW_PRIORITY' : '') . ' xf_user SET
						`'.$currency['column'].'` = `'.$currency['column'].'` + ? + (`'.$currency['column'].'`*?)
					WHERE user_id IN (' . $db->quote($userIds[$event['event_id']]) . ') AND `'.$currency['column'].'` > 0
				', array($event['amount'], $event['multiplier']));
			}
		}
	}

	public function updatePeriodRewardAmount($userIds, $events)
	{
		if(!$events || !$userIds){
			return false;
		}
		$db = $this->_getDb();
		$currencyObj = XenForo_Application::get('brcCurrencies');
		foreach($events AS $event){
			if(isset($event['amount'] ,$event['event_id']) && $event['amount'] && !empty($userIds[$event['event_id']])){
				$currency = $currencyObj->$event['currency_id'];
				$db->query('
					UPDATE ' . (XenForo_Application::get('options')->BRC_enableUpdateLowPriority ? 'LOW_PRIORITY' : '') . ' xf_user SET
						`'.$currency['column'].'` = `'.$currency['column'].'` + ?
					WHERE user_id IN (' . $db->quote($userIds[$event['event_id']]) . ') AND `'.$currency['column'].'` >= 0
				', $event['amount']);
			}
		}
	}

	/**
	 * alert member
	 *
	 */
	public function createAlert($alertUserId, $userId, $username, $eventId, $actionId, array $extraData = null)
	{
		XenForo_Model_Alert::alert(
			$alertUserId,
			$userId,
			$username,
			'credit',
			$eventId,
			$actionId,
			$extraData
		);
	}

	public function createTransactionAlert($alertUserId, $userId, $username, $eventId, $actionId, array $extraData = null)
	{
		XenForo_Model_Alert::alert(
			$alertUserId,
			$userId,
			$username,
			'brc_transaction',
			$eventId,
			$actionId,
			$extraData
		);
	}

	/**
	 * import credits from other field of `xf_user`
	 *
	 */
	public function importCredits($field,$column,$merge = true)
	{
		if (!$this->checkIfExist('xf_user', $field)) {
			return false;
		}
		$merger = '';
		if($merge){
			$merger = ' + `'.$column.'`';
		}
		$this->_getDb()->query('
			UPDATE xf_user SET
				`'.$column.'` = `'.$field.'` '.$merger.'
		');
	}
	/**
	 * Set all trophy point to 0
	 *
	 */
	public function resetTrophyPoints()
	{
		$this->_getDb()->query('
			UPDATE xf_user SET trophy_points = 0
		');
	}

	/**
	 * Set all credits to amount
	 *
	 *	@param double $amount
	 */
	public function resetCredit(array $columns,$userIds = array())
	{
		$db = $this->_getDb();
		$where = '1';
		if($userIds){
			if(count($userIds)==1){
				$where = $where . ' AND user_id = ' . $db->quote($userIds);
			}else{
				$where = $where . ' AND user_id IN (' . $db->quote($userIds) . ')';
			}
		}
		$db->update('xf_user',
			$columns,
			$where
		);
	}

	public function processTax($amount,$event)
	{
		$userTax = 0;
		$userActionTax = 0;

		$taxed = ($amount-$event['amount']) * $event['multiplier'] + $event['amount'];
		if($event['target']=='user'){
			$userTax = $taxed;
		}else if($event['target']=='user_action'){
			$userActionTax = $taxed;
		}else if($event['target']=='both'){
			$userActionTax = $userTax = $taxed/2;
		}

		return array(
			$userTax,$userActionTax
		);
	}

	public function calculateWordAmount($string)
	{

		if(XenForo_Application::get("options")->BRC_excludeBlockBbcode){
			$string = preg_replace('#\[(quote|php|html|code)[^\]]*\].*\[/\\1\]#siU', ' ', $string);
		}
		$string = preg_replace('#\[(attach|media|img)[^\]]*\].*\[/\\1\]#siU', ' [\\1] ', $string);
		while ($string != ($newString = preg_replace('#\[([a-z0-9]+)(=[^\]]*)?\](.*)\[/\1\]#siU', '\3', $string)))
		{
			$string = $newString;
		}
		return $this->countWords($string);
	}

	public function countWords($string)
	{
		$count = 0;
		if(XenForo_Application::get("options")->BRC_creditSizeWord){
			$count = count(explode(" ", $string));
		}else{
			$count = utf8_strlen($string);
		}
		return $count;
	}

	public function stripBbCode($string)
	{
		if ($stripQuote)
		{
			$string = preg_replace('#\[(quote)[^\]]*\].*\[/\\1\]#siU', ' ', $string);
		}

		// replaces unviewable tags with a text representation
		$string = preg_replace('#\[(attach|media|img)[^\]]*\].*\[/\\1\]#siU', '[\\1]', $string);

		while ($string != ($newString = preg_replace('#\[([a-z0-9]+)(=[^\]]*)?\](.*)\[/\1\]#siU', '\3', $string)))
		{
			$string = $newString;
		}

		$string = str_replace('[*]', '', $string);
	}

	public function totalCredits($column)
	{
		if(empty($column) || !$this->checkIfExist('xf_user', $column)){
			return 0;
		}
		return $this->_getDb()->fetchOne('
			SELECT SUM(`'.$column.'`)
			FROM xf_user
		');
	}

    public function getNodeIdFromPostId($postId)
	{
		$db = $this->_getDb();

		return $db->fetchOne('
			SELECT thread.node_id
			FROM `xf_post` AS `post`
			INNER JOIN xf_thread AS thread ON
				(thread.thread_id = post.thread_id)
			WHERE post.post_id = ?
		', $postId);
	}

	public function countEventsTriggerByUser($actionId, $userId, $conditions = array())
	{
		$transactionModel = $this->_getTransactionModel();
		$conditions['action_id'] =  $actionId;
		$conditions['user_id'] =  $userId;
		return $transactionModel->countTransactions($conditions);
	}

	public function canAnonymousTransfer(array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'BRC_anonymous_transfer'))
		{
			return true;
		}

		return false;
	}

	public function canExportTransaction(array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'BRC_exportTransaction'))
		{
			return true;
		}

		return false;
	}

	public function canViewRanking(array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'viewRanking'))
		{
			return true;
		}

		return false;
	}

	public function canUseCredits(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'useCredits'))
		{
			return true;
		}

		return false;
	}

	public function canViewSensitiveData(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'viewSensitiveData'))
		{
			return true;
		}

		return false;
	}

	public function canStealCredits(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'stealCredit');
	}

	public function canAnonymousStealCredits(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'anonymousSteal');
	}

	public function canEditUserCredits(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'editUserCredits');
	}

	public function canPurchaseCredits(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$actionObj = XenForo_Application::get('brcActionHandler');

		return $actionObj->canTriggerActionEvents('paypalPayment');
	}

	public function canExchange()
	{
		$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
		if(!$currencies || count($currencies) <= 1){
			return false;
		}

		$actionObj = XenForo_Application::get('brcActionHandler');

		$canExchange = false;
		$inBounce = false;
		$outBounce = false;
		foreach($currencies AS $currencyId=>$currency){
			$events = $actionObj->getActionEvents('exchange', array('currency_id' => $currency['currency_id']));

			if(!$actionObj->checkTriggerActionEvents($events)){
				continue;
			}
			if($currency['in_bound']){
				$inBounce = true;
			}
			if($currency['out_bound']){
				$outBounce = true;
			}
		}
		if($inBounce && $outBounce){
			return true;
		}

		return false;
	}

	public function checkIfExist($table, $field)
	{
		$db = $this->_getDb();
		if ($db->fetchRow('SHOW columns FROM `' . $table . '` WHERE Field = ?', $field)) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Sets whether this is a bulk. If true, behavior may be modified to be
	 * less asynchronous.
	 *
	 * @param boolean $bulk
	 */
	public function setIsBulk($bulk)
	{
		$this->_isBulk = $bulk;
	}

	/**
	 * Sets whether this is a wait. If true, behavior may be modified to be
	 * less asynchronous.
	 *
	 * @param boolean $wait
	 */
	public function setIsWaitSubmit($wait)
	{
		$this->_waitSubmit = $wait;
	}

	public function setBulkInsertLength($length)
	{
		$this->_bulkInsertLength = $length;
		$this->_bulkAlertLength = $length;
	}

	public function getAllAdCreditCurrencies()
	{
		if($this->checkIfExist('adcredit_currency', 'currency_id')){
			return $this->fetchAllKeyed('
				SELECT *
				FROM adcredit_currency
				ORDER BY display_order
			', 'currency_id');
		}
		return array();
	}

	/**
	 * Gets the user model.
	 *
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
	protected function _getTransactionModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Transaction');
	}
}