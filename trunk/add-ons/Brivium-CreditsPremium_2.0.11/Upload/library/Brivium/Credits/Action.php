<?php

/**
 * Credits actions accessor class.
 *
 * @package Brivium_Credits_Actions
 */
class Brivium_Credits_Action
{
	/**
	 * Collection of actions.
	 *
	 * @var array
	 */
	protected $_dependencies = null;
	protected $_actionHandlers = array();
	protected $_actionClasses = array();
	protected $_disabledActions = array();

	protected $_extendedClasses = array();

	protected $_actions = array();
	protected $_events = array();
	/**
	 * Constructor. Sets up the accessor using the provided actions.
	 *
	 * @param array $actions Collection of actions. Keys represent action names.
	 */
	public function __construct($dependencies)
	{
		$this->_dependencies = $dependencies;
		$this->_setDefaultActionClasses();
		XenForo_CodeEvent::fire('brc_action_handler', array(&$this->_actionClasses));

		$this->_disabledActions = XenForo_Application::getOptions()->BRC_disabledActions;
		$this->setActions();
	}

	protected function _setDefaultActionClasses()
	{
		$this->_actionClasses = array(
			'login'						=> 'Login',
			'exchange'					=> 'Exchange',
			'transfer'					=> 'Transfer',
			'withdraw'					=> 'Withdraw',
			'steal'						=> 'Steal',
			'paypalPayment'				=> 'PaypalPayment',
			'paypalPaymentRe'			=> 'PaypalPaymentRe',
			'registration'				=> 'Registration',

			'facebookAssociate'			=> 'FacebookAssociate',
			'facebookDisassociate'		=> 'FacebookDisassociate',
			'twitterAssociate'			=> 'TwitterAssociate',
			'twitterDisassociate'		=> 'TwitterDisassociate',
			'googleAssociate'			=> 'GoogleAssociate',
			'googleDisassociate'		=> 'GoogleDisassociate',

			'birthday'					=> 'Birthday',
			'importVbb'					=> 'ImportVbb',
			'interest'					=> 'Interest',



			'updateFullProfile'			=> 'UpdateFullProfile',
			'updateFullProfileRe'		=> 'UpdateFullProfileRe',
			'uploadAvatar'				=> 'UploadAvatar',
			'uploadAvatarRe'			=> 'UploadAvatarRe',
			'updateStatus'				=> 'UpdateStatus',

			'follow'					=> 'Follow',
			'followRe'					=> 'FollowRe',
			'getFollower'				=> 'GetFollower',
			'getFollowerRe'				=> 'GetFollowerRe',

			'profilePost'				=> 'ProfilePost',
			'getProfilePost'			=> 'GetProfilePost',
			'likeProfilePost'			=> 'LikeProfilePost',
			'likeProfilePostRe'			=> 'LikeProfilePostRe',
			'receiveProfilePostLike'	=> 'ReceiveProfilePostLike',
			'receiveProfilePostLikeRe'	=> 'ReceiveProfilePostLikeRe',



			'createConversation'		=> 'CreateConversation',
			'createConversationRe'		=> 'CreateConversationRe',
			'receiveConversation'		=> 'ReceiveConversation',
			'replyConversation'			=> 'ReplyConversation',
			'conversationGetReply'		=> 'ConversationGetReply',


			'trophyReward'				=> 'TrophyReward',
			'dailyReward'				=> 'DailyReward',
			'salary'					=> 'Salary',


			'createNewThread'			=> 'CreateNewThread',
			'threadDeleted'				=> 'ThreadDeleted',
			'threadGetReply'			=> 'ThreadGetReply',

			'threadViewed'				=> 'ThreadViewed',
			'readThread'				=> 'ReadThread',

			'watchThread'				=> 'WatchThread',
			'watchThreadRe'				=> 'WatchThreadRe',
			'threadGetWatched'			=> 'ThreadGetWatched',
			'threadGetWatchedRe'		=> 'ThreadGetWatchedRe',

			'createNewPoll'				=> 'CreateNewPoll',
			'votePoll'					=> 'VotePoll',
			'pollGetVote'				=> 'PollGetVote',

			'threadSticky'				=> 'ThreadSticky',
			'threadStickyRe'			=> 'ThreadStickyRe',


			'newPost'					=> 'NewPost',
			'postDeleted'				=> 'PostDeleted',

			'uploadAttachment'			=> 'UploadAttachment',
			'uploadAttachmentRe'		=> 'UploadAttachmentRe',

			'downloadAttachment'		=> 'DownloadAttachment',
			'attachmentDownloaded'		=> 'AttachmentDownloaded',

			'likePost'					=> 'LikePost',
			'likePostRe'				=> 'LikePostRe',

			'receivePostLike'			=> 'ReceivePostLike',
			'receivePostLikeRe'			=> 'ReceivePostLikeRe',

			'reportPost'				=> 'ReportPost',
			'postReported'				=> 'PostReported',
		);
	}

	public function get($actionId)
	{
		if (!isset($this->_actions[$actionId]))
		{
			return null;
		}

		$action = $this->_actions[$actionId];

		if (is_array($action))
		{
			return $action;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Gets all actions in their raw form.
	 *
	 * @return array
	 */
	public function getActions()
	{
		if($this->_actions === null){
			$this->setActions();
		}
		return $this->_actions;
	}

	public function getExtendedClasses()
	{
		return is_null($this->_extendedClasses)?array():$this->_extendedClasses;
	}

	// Deleted
	public function setActionHandlers()
	{
		$this->_actionHandlers = array();
		$disableActions = array();
		if (!$this->_dependencies instanceof XenForo_Dependencies_Admin)
		{
			$disableActions = XenForo_Application::getOptions()->BRC_disabledActions;
		}
		if(!$disableActions){
			$disableActions = array();
		}
		foreach ($this->_actionClasses AS $actionId=>$handlerClass)
		{
			if (strpos($handlerClass, '_') === false)
			{
				$handlerClass = 'Brivium_Credits_ActionHandler_' . $handlerClass . '_ActionHandler';
			}

			if(empty($this->_actionHandlers[$actionId]) && !in_array($actionId, $disableActions)){
				try
				{
					if (!$handlerClass || !class_exists($handlerClass))
					{
						continue;
					}

					$handlerClass = XenForo_Application::resolveDynamicClass($handlerClass);
					$handler = new $handlerClass();
					if($handler && $handler->canUseAction()){
						$this->_actionHandlers[$actionId] = $handler;
					}
				}
				catch (Exception $e) {}
			}
		}
		return $this->_actionHandlers;
	}

	public function setActionHandler($actionId)
	{
		if(isset($this->_actionHandlers[$actionId])){
			return $this->_actionHandlers[$actionId];
		}
		$this->_actionHandlers[$actionId] = false;
		$disableActions = array();
		if (!$this->_dependencies instanceof XenForo_Dependencies_Admin)
		{
			$disableActions = $this->_disabledActions;
		}
		if(!$disableActions){
			$disableActions = array();
		}
		if(!empty($this->_actionClasses[$actionId])){
			$handlerClass = $this->_actionClasses[$actionId];
			if (strpos($handlerClass, '_') === false)
			{
				$handlerClass = 'Brivium_Credits_ActionHandler_' . $handlerClass . '_ActionHandler';
			}

			if(empty($this->_actionHandlers[$actionId]) && !in_array($actionId, $disableActions)){
				try
				{
					if ($handlerClass && class_exists($handlerClass))
					{
						$handlerClass = XenForo_Application::resolveDynamicClass($handlerClass);
						$handler = new $handlerClass();
						if($handler && $handler->canUseAction()){
							$this->_actionHandlers[$actionId] = $handler;
						}
					}
				}
				catch (Exception $e) {}
			}
		}
		return $this->_actionHandlers[$actionId];
	}

	public function setActions()
	{
		$this->_actions = array();
		$this->setActionHandlers();
		foreach($this->_actionHandlers AS $actionId=>$handler){
			$this->_actions[$actionId] = $handler->getAction();
			$this->_extendedClasses = $handler->getExtendedClasses($this->_extendedClasses);
		}
	}

	public function getEvents()
	{
		return $this->_events;
	}

	public function getActionHandler($actionId)
	{
		if (!isset($this->_actionHandlers[$actionId]))
		{
			$this->setActionHandler($actionId);
		}

		$handler = $this->_actionHandlers[$actionId];

		if ($handler)
		{
			return $handler;
		}
		else
		{
			return null;
		}
	}

	public function getActionEvents($actionId, $criteria = array())
	{
		if (!isset($this->_events[$actionId]))
		{
			return array();
		}
		$handler = $this->getActionHandler($actionId);
		if(!$handler){
			return array();
		}
		$events = $this->_events[$actionId];
		if (is_array($events))
		{
			if($criteria && !empty($criteria['currency_id'])){
				if(!empty($events[$criteria['currency_id']])){
					$events = $events[$criteria['currency_id']];
				}else{
					$events = array();
				}
			}
			$events = $this->getEventsFromTree($events);

			if($criteria && !empty($criteria['event_id']) && !empty($events[$criteria['event_id']])){
				if(!empty($events[$criteria['event_id']])){
					$events = array($criteria['event_id'] => $events[$criteria['event_id']]);
				}else{
					$events = array();
				}
			}
			return $events;
		}
		else
		{
			return array();
		}
	}

	public function getActionEventsByCurrencyId($actionId, $currencyId)
	{
		if (!isset($this->_events[$actionId]))
		{
			return array();
		}

		$events = $this->_events[$actionId];

		if (!empty($events[$currencyId]) && is_array($events[$currencyId]))
		{
			return $events[$currencyId];
		}
		else
		{
			return array();
		}
	}

	public function setEvents(array $events)
	{
		$this->_events = $events;
	}

	/**
	 * Magic getter for first-order actions.
	 *
	 * @param string $action
	 *
	 * @return null|mixed
	 */
	public function __get($action)
	{
		return $this->get($action);
	}

	/**
	 * Returns true if the named action exists
	 *
	 * @param string $action
	 *
	 * @return boolean
	 */
	public function __isset($action)
	{
		return ($this->get($action) !== null);
	}

	/**
	 * Sets an action.
	 *
	 * @param string $actionId
	 * @param mixed|null $value If null, ignored
	 */
	public function set($actionId, $value = null)
	{
		if (isset($this->_actions[$actionId]))
		{
			$this->_actions[$actionId] = $value;
		}
		else
		{
			throw new XenForo_Exception('Tried to write invalid action.');
		}
	}

	/**
	 * Magic set method. Only sets whole actions.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}

	public function canTriggerActionEvents($actionId, $user = array(), $nodeId = 0)
	{
		if(!$user){
			$user = XenForo_Visitor::getInstance()->toArray();
		}
		$actionHandler = $this->getActionHandler($actionId);
		if(!$actionHandler){
			return false;
		}
		$events = $this->getActionEvents($actionId);

		if($events){
			return $this->checkTriggerActionEvents($events, $user, $nodeId);
		}
		return false;
	}

	protected $_checkedEvents = null;

	public function checkTriggerActionEvents($events, $user = array(), $nodeId = 0)
	{
		foreach($events AS $eventId=>$event){
			if($allowEventId = $this->checkTriggerActionEvent($event, $user, $nodeId)){
				return $allowEventId;
			}
		}
		return false;
	}

	public function checkTriggerActionEvent($event, $user = array(), $nodeId = 0)
	{
		if(empty($user['user_id'])){
			$user['user_id'] = 0;
		}
		if(isset($this->_checkedEvents[$user['user_id']][$event['event_id']])){
			return $this->_checkedEvents[$user['user_id']][$event['event_id']]?$event['event_id']:0;
		}
		$actionHandler = $this->getActionHandler($event['action_id']);

		if(!isset($this->_checkedEvents[$user['user_id']])){
			$this->_checkedEvents[$user['user_id']] = array();
		}

		if(!empty($event['event_id']) && $allowEventId = $actionHandler->canTriggerEvent($event, $user, $nodeId)){
			$this->_checkedEvents[$user['user_id']][$event['event_id']] = $allowEventId;
			return $allowEventId;
		}

		$this->_checkedEvents[$user['user_id']][$event['event_id']] = false;
		return false;
	}

	public function getEventsFromTree($events)
	{
		$listEvents = array();
		if(is_array($events) && empty($events['event_id'])){
			foreach($events AS $event){
				$listEvents += $this->getEventsFromTree($event);
			}
		}else if(!empty($events['event_id'])){
			$listEvents[$events['event_id']] = $events;
		}
		return $listEvents;
	}

}