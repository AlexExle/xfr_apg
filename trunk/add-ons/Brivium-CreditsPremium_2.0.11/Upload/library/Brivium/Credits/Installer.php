<?php
class Brivium_Credits_Installer extends Brivium_BriviumHelper_Installer
{
	protected $_installerType = 1;

	public static function install($existingAddOn, $addOnData)
	{
		self::$_addOnInstaller = __CLASS__;
		if (self::$_addOnInstaller && class_exists(self::$_addOnInstaller))
		{
			$installer = self::create(self::$_addOnInstaller);
			$installer->installAddOn($existingAddOn, $addOnData);
		}
		return true;
	}

	public static function uninstall($addOnData)
	{
		self::$_addOnInstaller = __CLASS__;
		if (self::$_addOnInstaller && class_exists(self::$_addOnInstaller))
		{
			$installer = self::create(self::$_addOnInstaller);
			$installer->uninstallAddOn($addOnData);
		}
	}

	protected function _preInstall()
	{
		if(!empty($this->_existingAddOn['addon_id']) && $this->_existingAddOn['addon_id'] < 1000000){
			$db = $this->_getDb();
			try
			{
				$oldCurrency = $db->fetchRow(' SELECT * FROM xf_credits_currency ORDER BY currency_id ASC LIMIT 0,1');
				if(!empty($oldCurrency)){
					$this->_data['xf_brivium_credits_currency'] = "
						REPLACE INTO `xf_brivium_credits_currency`
						(`currency_id`, `title`, `description`, `column`, `code`, `symbol_left`, `symbol_right`, `decimal_place`, `negative_handle`, `user_groups`, `max_time`, `earn_max`, `in_bound`, `out_bound`, `value`, `withdraw`, `withdraw_min`, `withdraw_max`, `display_order`, `active`) VALUES
						(1, '".$oldCurrency['title']."', '', 'credits', '".$oldCurrency['code']."', '".$oldCurrency['symbol_left']."', '".$oldCurrency['symbol_right']."', '".$oldCurrency['decimal_place']."', 'show', '', 0, 0.0000, 1, 0, 1.0000, 1, 0.0000, 0.0000, 10, 1),
						(2, 'Point', '', 'brc_points', 'Point', '', ' p', 2, 'show', '', 0, 0.0000, 1, 1, 21000.0000, 0, 0.0000, 0.0000, 20, 1);
					";
				}
			}
			catch (Zend_Db_Exception $e){}
		}
	}

	protected function _postInstall()
	{
		XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
		XenForo_Model::create('Brivium_Credits_Model_Currency')->rebuildCurrencyCaches();
		XenForo_Model::create('Brivium_Credits_Model_Event')->rebuildEventCache();
	}

	public function getTables()
	{
		$tables = array();
		$tables["xf_brivium_credits_currency"] = "
			CREATE TABLE IF NOT EXISTS `xf_brivium_credits_currency` (
			  `currency_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `title` varchar(50) NOT NULL,
			  `description` text NOT NULL,
			  `column` varchar(100) NOT NULL,
			  `code` text NOT NULL,
			  `symbol_left` varchar(50) NOT NULL,
			  `symbol_right` varchar(50) NOT NULL,
			  `decimal_place` tinyint(2) unsigned NOT NULL DEFAULT '0',
			  `negative_handle` enum('reset','hide','show') NOT NULL DEFAULT 'show',
			  `user_groups` mediumblob NOT NULL,
			  `max_time` int(10) unsigned NOT NULL DEFAULT '0',
			  `earn_max` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `in_bound` tinyint(3) unsigned NOT NULL DEFAULT '1',
			  `out_bound` tinyint(3) unsigned NOT NULL DEFAULT '1',
			  `value` decimal(19,6) unsigned NOT NULL DEFAULT '0.000000',
			  `withdraw` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `withdraw_min` decimal(19,6) unsigned NOT NULL DEFAULT '0.000000',
			  `withdraw_max` decimal(19,6) unsigned NOT NULL DEFAULT '0.000000',
			  `display_order` int(10) unsigned NOT NULL DEFAULT '0',
			  `active` tinyint(3) unsigned NOT NULL DEFAULT '1',
			  PRIMARY KEY (`currency_id`),
			  KEY `column` (`column`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		";
		$tables["xf_brivium_credits_event"] = "
			CREATE TABLE IF NOT EXISTS `xf_brivium_credits_event` (
			  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `action_id` varchar(100) NOT NULL,
			  `currency_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `user_groups` mediumblob NOT NULL,
			  `forums` mediumblob NOT NULL,
			  `amount` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `sub_amount` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `multiplier` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `sub_multiplier` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `active` tinyint(3) unsigned NOT NULL DEFAULT '1',
			  `moderate` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `alert` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `times` int(10) unsigned NOT NULL DEFAULT '1',
			  `max_time` int(10) unsigned NOT NULL DEFAULT '0',
			  `apply_max` int(10) unsigned NOT NULL DEFAULT '0',
			  `extra_min` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `extra_max` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `extra_min_handle` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `target` enum('user','user_action','both') NOT NULL DEFAULT 'user',
			  `allow_negative` tinyint(3) NOT NULL DEFAULT '0',
			  `negative_handle` varchar(30) NOT NULL DEFAULT '',
			  `extra_data` blob NOT NULL,
			  PRIMARY KEY (`event_id`),
			  KEY `action_id` (`action_id`),
			  KEY `currency_id` (`currency_id`),
			  KEY `active` (`active`),
			  KEY `moderate` (`moderate`),
			  KEY `action_currency` (`action_id`,`currency_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		";
		$tables["xf_brivium_credits_paypal_log"] = "
			CREATE TABLE IF NOT EXISTS `xf_brivium_credits_paypal_log` (
			  `payment_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `processor` varchar(25) NOT NULL,
			  `transaction_id` varchar(50) NOT NULL,
			  `transaction_type` enum('payment','cancel','info','error') NOT NULL,
			  `message` varchar(255) NOT NULL DEFAULT '',
			  `is_sanbox` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `transaction_details` mediumblob NOT NULL,
			  `log_date` int(10) unsigned NOT NULL DEFAULT '0',
			  PRIMARY KEY (`payment_log_id`),
			  KEY `transaction_id` (`transaction_id`),
			  KEY `transaction_type` (`transaction_type`),
			  KEY `user_id` (`user_id`),
			  KEY `log_date` (`log_date`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		";
		$tables["xf_brivium_credits_stats"] = "
			CREATE TABLE IF NOT EXISTS `xf_brivium_credits_stats` (
			  `action_id` varchar(50) NOT NULL,
			  `total_earn` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `total_spend` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `start_date` int(10) unsigned NOT NULL DEFAULT '0',
			  `stats_date` int(10) unsigned NOT NULL DEFAULT '0',
			  `currency_id` int(10) unsigned NOT NULL,
			  `stats_type` varchar(30) NOT NULL DEFAULT '',
			  PRIMARY KEY (`action_id`,`currency_id`,`stats_type`),
			  KEY `currency_id` (`currency_id`),
			  KEY `stats_type` (`stats_type`),
			  KEY `currency_stats_type` (`currency_id`,`stats_type`),
			  KEY `start_date` (`start_date`),
			  KEY `stats_date` (`stats_date`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		";
		$tables["xf_brivium_credits_transaction"] = "
			CREATE TABLE IF NOT EXISTS `xf_brivium_credits_transaction` (
			  `transaction_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `transaction_key` varbinary(50) NOT NULL DEFAULT '',
			  `action_id` varchar(100) NOT NULL,
			  `event_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `currency_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `user_action_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `content_type` varchar(25) NOT NULL DEFAULT '',
			  `owner_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `multiplier` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `transaction_date` int(10) NOT NULL DEFAULT '0',
			  `amount` decimal(19,6) NOT NULL DEFAULT '0.000000',
			  `negate` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `message` text NOT NULL,
			  `sensitive_data` text NOT NULL,
			  `moderate` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `is_revert` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `transaction_state` varchar(30) NOT NULL,
			  `extra_data` mediumblob NOT NULL COMMENT 'Serialized. Stores any extra data relevant to the transaction',
			  PRIMARY KEY (`transaction_id`),
			  KEY `action_id` (`action_id`),
			  KEY `event_id` (`event_id`),
			  KEY `currency_id` (`currency_id`),
			  KEY `action_currency` (`action_id`,`currency_id`),
			  KEY `user_id` (`user_id`),
			  KEY `user_action_id` (`user_action_id`),
			  KEY `content_id` (`content_id`),
			  KEY `content` (`content_id`,`content_type`),
			  KEY `multiplier` (`multiplier`),
			  KEY `transaction_state` (`transaction_state`),
			  KEY `transaction_date` (`transaction_date`),
			  KEY `action_moderate` (`action_id`,`moderate`),
			  KEY `user_action` (`user_id`,`action_id`),
			  KEY `transaction_action_currency` (`transaction_date`,`action_id`,`currency_id`),
			  KEY `transaction_key` (`transaction_key`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		";
		return $tables;
	}

	public function getData()
	{
		$data = array();
		$data['xf_brivium_credits_currency'] = "
			INSERT IGNORE INTO `xf_brivium_credits_currency`
				(`currency_id`, `title`, `description`, `column`, `code`, `symbol_left`, `symbol_right`, `decimal_place`, `negative_handle`, `user_groups`, `max_time`, `earn_max`, `in_bound`, `out_bound`, `value`, `withdraw`, `withdraw_min`, `withdraw_max`, `display_order`, `active`)
			VALUES
				(1, 'Credits', '', 'credits', 'Credits', '', 'C', 2, 'show', '', 0, '0.0000', 1, 1, '1.0000', 1, '0.0000', '100000.0000', 10, 1),
				(2, 'Point', '', 'brc_points', 'Point', '', ' p', 2, 'show', '', 0, '0.0000', 1, 1, '21000.0000', 0, '0.0000', '0.0000', 20, 1);
		";
		$data['xf_content_type'] = "
			INSERT IGNORE INTO xf_content_type
				(content_type, addon_id, fields)
			VALUES
				('brc_transaction', 'Brivium_Credits', ''),
				('credit', 'Brivium_Credits', '');
		";

		$data['xf_content_type_field'] = "
			INSERT IGNORE INTO `xf_content_type_field`
				(`content_type`, `field_name`, `field_value`)
			VALUES
				('brc_transaction', 'alert_handler_class', 'Brivium_Credits_AlertHandler_Transaction'),
				('credit', 'alert_handler_class', 'Brivium_Credits_AlertHandler_Credit'),
				('credit', 'moderator_log_handler_class', 'Brivium_Credits_ModeratorLogHandler_UserCredit');
		";
		return $data;
	}

	public function getAlters()
	{
		$alters = array();
		$alters['xf_user'] = array(
			'credits'	=>	" decimal(19,6) NOT NULL DEFAULT '0.0000'",
			'brc_points'	=>	" decimal(19,6) NOT NULL DEFAULT '0.0000'",
		);
		$alters['xf_brivium_credits_event'] = array(
			'allow_negative'	=>	" tinyint(3) NOT NULL DEFAULT '0'",
			'negative_handle'	=>	" varchar(30) NOT NULL DEFAULT ''",
			'extra_data'	=>	" blob NOT NULL",
		);
		$alters['xf_brivium_credits_currency'] = array(
			'display_order'	=>	" int(10) unsigned NOT NULL DEFAULT '0'",
			'active'	=>	" tinyint(3) NOT NULL DEFAULT '1'",
		);
		$alters['xf_brivium_credits_paypal_log'] = array(
			'is_sanbox'	=>	" TINYINT(3) UNSIGNED NOT NULL DEFAULT '0'",
		);
		if($this->_existingVersionId > 0 && $this->_existingVersionId < 1000000){
			$alters["xf_brivium_credits_transaction"] = array(
				"event_id"		=> "INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0'",
				"currency_id"	=> "INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0'",
				"content_id"	=> "INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0'",
				"content_type"	=> "varchar(25) NOT NULL DEFAULT ''",
				"moderate"		=> "tinyint(3) unsigned NOT NULL DEFAULT '0'",
				'is_revert'	=>	" TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT  '0'",
			);
		}else{
			$alters["xf_brivium_credits_transaction"] = array();
		}
		$alters["xf_brivium_credits_transaction"]['sensitive_data'] = 'TEXT NOT NULL AFTER  `message`';
		$alters["xf_brivium_credits_transaction"]['transaction_key'] = "VARBINARY(50) NOT NULL DEFAULT ''";
		return $alters;
	}

	public function getQueryBeforeTable()
	{
		$query = array();
		if($this->_triggerType != "uninstall" && $this->_existingVersionId > 0 && $this->_existingVersionId < 2000000){
			$query[] = " RENAME TABLE  `xf_credits_currency` TO  `xf_brivium_credits_currency` ;";
			$query[] = " RENAME TABLE  `xf_credits_event` TO  `xf_brivium_credits_event` ;";
			$query[] = " RENAME TABLE  `xf_credits_paypal_log` TO  `xf_brivium_credits_paypal_log` ;";
			$query[] = " RENAME TABLE  `xf_credits_stats` TO  `xf_brivium_credits_stats` ;";
			$query[] = " RENAME TABLE  `xf_credits_transaction` TO  `xf_brivium_credits_transaction` ;";
			$query[] = " ALTER TABLE  `xf_brivium_credits_currency` ENGINE = INNODB";
			$query[] = " ALTER TABLE  `xf_brivium_credits_event` ENGINE = INNODB";
			$query[] = " ALTER TABLE  `xf_brivium_credits_paypal_log` ENGINE = INNODB";
			$query[] = " ALTER TABLE  `xf_brivium_credits_stats` ENGINE = INNODB";
			$query[] = " ALTER TABLE  `xf_brivium_credits_transaction` ENGINE = INNODB";
		}
		return $query;
	}

	public function getQueryBeforeAlter()
	{
		$query = array();
		if($this->_triggerType != "uninstall" && $this->_existingVersionId > 0 && $this->_existingVersionId < 2000000){
			$query[] = "
				ALTER TABLE  `xf_brivium_credits_currency`
					CHANGE  `symbol_left`  `symbol_left` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
					CHANGE  `symbol_right`  `symbol_right` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
					CHANGE  `code`  `code` TEXT NOT NULL,
					CHANGE  `earn_max`  `earn_max` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000',
					CHANGE  `value`  `value` DECIMAL( 19, 6 ) UNSIGNED NOT NULL DEFAULT  '0.0000',
					CHANGE  `withdraw_min`  `withdraw_min` DECIMAL( 19, 6 ) UNSIGNED NOT NULL DEFAULT  '0.0000',
					CHANGE  `withdraw_max`  `withdraw_max` DECIMAL( 19, 6 ) UNSIGNED NOT NULL DEFAULT  '0.0000'
			";
			$query[] = "
				ALTER TABLE  `xf_brivium_credits_event`
					CHANGE  `amount`  `amount` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000',
					CHANGE  `sub_amount`  `sub_amount` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000',
					CHANGE  `multiplier`  `multiplier` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000',
					CHANGE  `sub_multiplier`  `sub_multiplier` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000',
					CHANGE  `extra_min`  `extra_min` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000',
					CHANGE  `extra_max`  `extra_max` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000'
			";
			$query[] = "
				ALTER TABLE  `xf_brivium_credits_transaction`
					CHANGE  `amount`  `amount` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000',
					CHANGE  `multiplier`  `multiplier` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000'
			";
			$query[] = "
				ALTER TABLE  `xf_brivium_credits_stats`
					CHANGE  `total_earn`  `total_earn` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000',
					CHANGE  `total_spend`  `total_spend` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.0000'
			";

			$query[] = "
				ALTER TABLE  `xf_user`
					CHANGE  `credits`  `credits` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.00000'
			";
			$query[] = "
				ALTER TABLE  `xf_user`
					CHANGE  `brc_points`  `brc_points` DECIMAL( 19, 6 ) NOT NULL DEFAULT  '0.00000'
			";
			if($this->_existingVersionId < 1000000){
				$query[] = "
					UPDATE `xf_brivium_credits_transaction` AS `transaction`
					INNER JOIN `xf_brivium_credits_event` AS `event`
						ON `event`.`action_id` = `transaction`.`action_id`
					SET `transaction`.`event_id` = `event`.`event_id`,
						`transaction`.`currency_id` = 1,
						`transaction`.`moderate` = 0
				";
			}
			$query[] = "
				ALTER TABLE  `xf_brivium_credits_currency`
					ADD KEY `column` (`column`);
			";
			$query[] = "
				ALTER TABLE  `xf_brivium_credits_currency`
					ADD KEY `action_id` (`action_id`),
					ADD KEY `currency_id` (`currency_id`),
					ADD KEY `active` (`active`),
					ADD KEY `moderate` (`moderate`),
					ADD KEY `action_currency` (`action_id`,`currency_id`);
			";
			$query[] = "
				ALTER TABLE  `xf_brivium_credits_paypal_log`
					ADD KEY `transaction_id` (`transaction_id`),
					ADD KEY `transaction_type` (`transaction_type`),
					ADD KEY `user_id` (`user_id`),
					ADD KEY `log_date` (`log_date`);
			";
			$query[] = "
				ALTER TABLE  `xf_brivium_credits_paypal_log`
					ADD KEY `currency_id` (`currency_id`),
					ADD KEY `stats_type` (`stats_type`),
					ADD KEY `currency_stats_type` (`currency_id`,`stats_type`),
					ADD KEY `start_date` (`start_date`),
					ADD KEY `stats_date` (`stats_date`);
			";
			$query[] = "
				ALTER TABLE  `xf_brivium_credits_transaction`
					ADD KEY `action_id` (`action_id`),
					ADD KEY `event_id` (`event_id`),
					ADD KEY `currency_id` (`currency_id`),
					ADD KEY `action_currency` (`action_id`,`currency_id`),
					ADD KEY `user_id` (`user_id`),
					ADD KEY `user_action_id` (`user_action_id`),
					ADD KEY `content_id` (`content_id`),
					ADD KEY `content` (`content_id`,`content_type`),
					ADD KEY `multiplier` (`multiplier`),
					ADD KEY `transaction_state` (`transaction_state`),
					ADD KEY `transaction_date` (`transaction_date`),
					ADD KEY `action_moderate` (`action_id`,`moderate`),
					ADD KEY `transaction_action_currency` (`transaction_date`, `action_id`, `currency_id`),
					ADD KEY `user_action` (`user_id`,`action_id`);
			";
		}
		if($this->_triggerType != "uninstall" && $this->_existingVersionId > 0 && $this->_existingVersionId < 2000371){
			$query[] = "
				ALTER TABLE  `xf_brivium_credits_transaction`
					ADD KEY `transaction_key` (`transaction_key`);
			";
		}
		return $query;
	}

	public function getQueryFinal()
	{
		$query = array();
		$query[] = "
			DELETE FROM `xf_brivium_listener_class` WHERE `addon_id` = 'Brivium_Credits';
		";
		if($this->_triggerType != "uninstall"){
			$query[] = "
				REPLACE INTO `xf_brivium_addon`
					(`addon_id`, `title`, `version_id`, `copyright_removal`, `start_date`, `end_date`)
				VALUES
					('Brivium_Credits', 'Brivium - Credits Premium', '2001031', 0, 0, 0);
			";
			$query[] = "
				REPLACE INTO `xf_brivium_listener_class`
					(`class`, `class_extend`, `event_id`, `addon_id`)
				VALUES
					('XenForo_ControllerAdmin_Tools', 'Brivium_Credits_ControllerAdmin_Tools', 'load_class_controller', 'Brivium_Credits'),
					('XenForo_Model_Option', 'Brivium_Credits_Model_Option', 'load_class_model', 'Brivium_Credits'),
					('XenForo_Model_Moderator', 'Brivium_Credits_Model_Moderator', 'load_class_model', 'Brivium_Credits'),
					('XenForo_Model_Attachment', 'Brivium_Credits_Model_Attachment', 'load_class_model', 'Brivium_Credits'),
					('XenForo_Model_AddOn', 'Brivium_Credits_Model_AddOn', 'load_class_model', 'Brivium_Credits'),
					('XenForo_DataWriter_User', 'Brivium_Credits_DataWriter_User', 'load_class_datawriter', 'Brivium_Credits'),
					('XenForo_ControllerPublic_Member', 'Brivium_Credits_ControllerPublic_Member', 'load_class_controller', 'Brivium_Credits'),
					('XenForo_ControllerPublic_Account', 'Brivium_Credits_ControllerPublic_Account', 'load_class_controller', 'Brivium_Credits'),
					('XenForo_ControllerAdmin_UserGroupPromotion', 'Brivium_Credits_ControllerAdmin_UserGroupPromotion', 'load_class_controller', 'Brivium_Credits'),
					('XenForo_ControllerAdmin_User', 'Brivium_Credits_ControllerAdmin_User', 'load_class_controller', 'Brivium_Credits'),
					('XenForo_Model_User', 'Brivium_Credits_Model_User', 'load_class_model', 'Brivium_Credits');
			";
		}else{
			$query[] = "
				DELETE FROM `xf_brivium_addon` WHERE `addon_id` = 'Brivium_Credits';
			";
		}
		return $query;
	}
}