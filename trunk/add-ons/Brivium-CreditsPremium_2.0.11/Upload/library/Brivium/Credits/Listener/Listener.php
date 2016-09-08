<?php

class Brivium_Credits_Listener_Listener extends Brivium_BriviumHelper_EventListeners
{
	protected static $_hasNewVersion = null;
	protected static $_hasTemplatePerm = null;
	protected static $_currencyFields = null;
	protected static $_currencies = null;
	protected static $_canTransfer = null;
	protected static $_canStealCredits = null;
	protected static $_canViewOtherTransactions = null;

	protected static $_listenerClasses = null;

	public static function brsaApiHandler(array &$actions)
	{
		$actions['credit'] = 'Brivium_Credits_ApiHandler_Credit';
	}

	public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		if ($dependencies instanceof XenForo_Dependencies_Admin)
		{
			XenForo_CacheRebuilder_Abstract::$builders['Credit'] = 'Brivium_Credits_CacheRebuilder_Credit';
			XenForo_CacheRebuilder_Abstract::$builders['CreditImport'] = 'Brivium_Credits_CacheRebuilder_CreditImport';
		}
		$data = XenForo_Model::create('XenForo_Model_DataRegistry')->getMulti(array('brcCurrencies','brcEvents'));
		$creditAddOnId = 0;
		if (XenForo_Application::isRegistered('addOns'))
		{
			$addOns = XenForo_Application::get('addOns');
			if(!empty($addOns['Brivium_Credits']) && $addOns['Brivium_Credits']  >= 2000000){
				$creditAddOnId = $addOns['Brivium_Credits'];
			}
		}
		if(!$creditAddOnId || $creditAddOnId < 2000000){
			$currencies = array();
			$events = array();
			self::$_hasNewVersion = false;
		}else{
			$currencies = (!empty($data['brcCurrencies']) && is_array($data['brcCurrencies']) ? $data['brcCurrencies'] : array());
			$events = (!empty($data['brcEvents']) && is_array($data['brcEvents']) ? $data['brcEvents'] : array());
			self::$_hasNewVersion = true;
			XenForo_Template_Helper_Core::$helperCallbacks['brc_currencyformat'] = array('Brivium_Credits_Listener_Helpers', 'helperCurrencyFormat');
			XenForo_Template_Helper_Core::$helperCallbacks['brc_currencyicon'] = array('Brivium_Credits_Listener_Helpers', 'helperCurrencyIconUrl');
		}

		$currenciesObj = new Brivium_Credits_Currency($currencies);
		XenForo_Application::set('brcCurrencies',$currenciesObj);

		$actionObj = new Brivium_Credits_Action($dependencies);
		$actionObj->setEvents($events);
		self::$_listenerClasses = $actionObj->getExtendedClasses();
		XenForo_Application::set('brcActionHandler', $actionObj);

		// avoid error
		XenForo_Application::set('brcEvents', new Brivium_Credits_Events(array()));
	}

	protected static function _loadClassExtend($classType, $class, &$extend)
	{
		if(
			!empty(self::$_listenerClasses) &&
			!empty(self::$_listenerClasses[$classType][$class]) &&
			is_array(self::$_listenerClasses[$classType][$class])
		)
		{
			foreach(self::$_listenerClasses[$classType][$class] AS $extendClass){
				$extend[] = $extendClass;
			}
			$extend = array_unique($extend);
		}
	}

	public static function criteriaUser($rule, array $data, array $user, &$returnValue)
	{
		if(self::$_hasNewVersion){
			if(is_null(self::$_currencyFields)){
				if(is_null(self::$_currencies)){
					$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
					self::$_currencies = $currencies;
				}
				$listFields = array();
				if(self::$_currencies){
					foreach(self::$_currencies AS $currency){
						if(!empty($currency['column'])){
							$listFields[] = $currency['column'];
						}
					}
				}
				self::$_currencyFields = $listFields;

			}
			if(!empty(self::$_currencyFields) && in_array($rule,self::$_currencyFields)){
				if (isset($user[$rule]) && isset($data['credits']) && $user[$rule] > $data['credits'])
				{
					$returnValue = true;
				}
			}
		}
	}

	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if(!self::$_hasNewVersion){
			return;
		}
		if ($template instanceof XenForo_Template_Admin)
		{
			switch ($templateName) {
				case 'user_edit':
					$template->preloadTemplate('BRC_admin_user_edit_tabs');
					$template->preloadTemplate('BRC_admin_user_edit_panes');
					break;
			}
		}else{
			if (self::$_hasTemplatePerm === null)
			{
				self::$_hasTemplatePerm = XenForo_Visitor::getInstance()->hasPermission('BR_CreditsPermission', 'useCredits');
			}

			if (!isset($params['canUseCredits']))
			{
				$params['canUseCredits'] = self::$_hasTemplatePerm;
			}
			$visitor = XenForo_Visitor::getInstance();
			switch ($templateName) {
				case 'member_card':
					$template->preloadTemplate('BRC_member_card_stats');
					break;
				case 'thread_view':
					$template->preloadTemplate('BRC_message_user_info_extra');
					break;
				default:
					if($visitor['is_moderator'] || $visitor['is_admin']){
						$template->preloadTemplate('BRC_moderator_bar');
					}
					$template->preloadTemplate('BRC_sidebar_visitor_panel_stats');
					$template->preloadTemplate('BRC_navigation_visitor_tabs_end');
			}
		}
	}

	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if(!self::$_hasNewVersion){
			return;
		}
		$param = array();

		$options = XenForo_Application::get('options');

		if (self::$_hasTemplatePerm === null)
		{
			self::$_hasTemplatePerm = XenForo_Visitor::getInstance()->hasPermission('BR_CreditsPermission', 'useCredits');
		}

		switch($hookName){

			// Admin
			case 'admin_user_edit_tabs':
			case 'admin_user_edit_panes':
				$newTemplate = $template->create('BRC_' . $hookName, $template->getParams());
				$contents .= $newTemplate->render();
				break;

			// Public
			case 'account_alerts_extra':
				if(self::$_hasTemplatePerm){
					$newTemplate = $template->create('BRC_' . $hookName, $template->getParams());
					$contents .= $newTemplate->render();
				}
				break;
			case 'moderator_bar':
				if(self::$_hasTemplatePerm){
					$transactionModel = XenForo_Model::create('Brivium_Credits_Model_Transaction');

					if (self::$_canViewOtherTransactions === null)
					{
						self::$_canViewOtherTransactions = $transactionModel->canViewOtherTransactions();
					}
					if(self::$_canViewOtherTransactions){
						if($options->get('BRC_pendingDisplayOption', 'pending_transaction') || $options->get('BRC_pendingDisplayOption', 'pending_withdraw')){
							$newTemplate = $template->create('BRC_' . $hookName, $template->getParams());
							if($options->get('BRC_pendingDisplayOption', 'pending_transaction')){
								$pendingTransaction = $transactionModel->countTransactions(array('moderate'	=> 1));
								if(!$pendingTransaction){
									$pendingTransaction = 0;
								}
								$newTemplate->setParam('pendingTransaction', $pendingTransaction);
							}
							if($options->get('BRC_pendingDisplayOption', 'pending_withdraw')){
								$pendingWithdraw = $transactionModel->countTransactions(array('moderate'	=> 1, 'action_id'	=> 'withdraw'));
								if(!$pendingWithdraw){
									$pendingWithdraw = 0;
								}
								$newTemplate->setParam('pendingWithdraw', $pendingWithdraw);
							}
							$contents .= $newTemplate->render();
						}
					}
				}
				break;
			case 'message_user_info_extra':
			case 'sidebar_visitor_panel_stats':
			case 'member_view_info_block':
			case 'member_card_stats':
			case 'navigation_visitor_tabs_end':
				if($options->get('BRC_displayOption', $hookName) && self::$_hasTemplatePerm){
					if (self::$_currencies === null)
					{
						self::$_currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
					}

					$creditModel = XenForo_Model::create('Brivium_Credits_Model_Credit');
					$actionObj = XenForo_Application::get('brcActionHandler');

					if (self::$_canTransfer === null)
					{
						self::$_canTransfer = $actionObj->canTriggerActionEvents('transfer');
					}
					if (self::$_canStealCredits === null)
					{
						self::$_canStealCredits = $creditModel->canStealCredits();
					}

					$param['canTransfer'] = self::$_canTransfer;
					$param['canStealCredits'] = self::$_canStealCredits;

					$currencyDisplay = $options->get('BRC_currencyDisplay');
					if(isset($currencyDisplay[0]) && ($currencyDisplay[0]==''||$currencyDisplay[0]==0)){
						$currencyDisplay = array();
					}

					$param['currencyDisplay'] = $currencyDisplay;
					$param['currencies'] = self::$_currencies;

					$newTemplate = $template->create('BRC_' . $hookName,$template->getParams());
					$newTemplate->setParams($hookParams);
					$newTemplate->setParams($param);
					$contents .= $newTemplate->render();
				}
				break;
		}
	}

	public static function loadClass($class, &$extend)
	{
		$classType = 'load_class';
		self::_loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassController($class, &$extend)
	{
		$classType = 'load_class_controller';
		self::_loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassDatawriter($class, &$extend)
	{
		$classType = 'load_class_datawriter';
		self::_loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassImporter($class, &$extend)
	{
		$classType = 'load_class_importer';
		self::_loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassModel($class, &$extend)
	{
		$classType = 'load_class_model';
		self::_loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassView($class, &$extend)
	{
		$classType = 'load_class_view';
		self::_loadClassExtend($classType, $class, $extend);
	}
}