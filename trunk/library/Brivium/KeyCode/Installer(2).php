<?php
class Brivium_RockPaperScissors_Installer extends Brivium_BriviumHelper_Installer
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
	public function getTables()
	{
		$tables = array();

		$tables['xf_brivium_rps_challenge'] = "
				CREATE TABLE IF NOT EXISTS `xf_brivium_rps_challenge` (
				  `challenge_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `user_id` int(10) unsigned NOT NULL,
				  `betting_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
				  `currency_id` int(10) unsigned NOT NULL DEFAULT '0',
				  `money_type` varchar(50) NOT NULL DEFAULT '',
				  `choice` enum('rock','paper','scissors') NOT NULL,
				  `competitor_id` int(10) unsigned NOT NULL DEFAULT '0',
				  `start_time` int(10) unsigned NOT NULL DEFAULT '0',
				  `end_time` int(10) unsigned NOT NULL DEFAULT '0',
				  PRIMARY KEY (`challenge_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
				";
		$tables['xf_brivium_rps_play_log'] = "
				CREATE TABLE IF NOT EXISTS `xf_brivium_rps_play_log` (
				  `play_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `user_id` int(10) unsigned NOT NULL,
				  `play_type` varchar(25) NOT NULL DEFAULT '',
				  `betting_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
				  `amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
				  `currency_id` int(10) unsigned NOT NULL DEFAULT '0',
				  `money_type` varchar(50) NOT NULL DEFAULT '',
				  `choice` enum('rock','paper','scissors') NOT NULL,
				  `competitor_id` int(10) unsigned NOT NULL DEFAULT '0',
				  `competitor_choice` enum('rock','paper','scissors') NOT NULL,
				  `play_result` enum('win','tie','lose') NOT NULL,
				  `start_time` int(10) unsigned NOT NULL DEFAULT '0',
				  `end_time` int(10) unsigned NOT NULL DEFAULT '0',
				  PRIMARY KEY (`play_log_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
				";
		$tables['xf_brivium_rps_stats'] = "
				CREATE TABLE IF NOT EXISTS `xf_brivium_rps_stats` (
				  `user_id` int(10) unsigned NOT NULL,
				  `win_total` int(10) unsigned NOT NULL DEFAULT '0',
				  `win_month` int(10) unsigned NOT NULL DEFAULT '0',
				  `win_week` int(10) unsigned NOT NULL DEFAULT '0',
				  `win_day` int(10) unsigned NOT NULL DEFAULT '0',
				  `lose_total` int(10) unsigned NOT NULL DEFAULT '0',
				  `lose_month` int(10) unsigned NOT NULL DEFAULT '0',
				  `lose_week` int(10) unsigned NOT NULL DEFAULT '0',
				  `lose_day` int(10) unsigned NOT NULL DEFAULT '0',
				  `play_total` int(10) unsigned NOT NULL DEFAULT '0',
				  `play_month` int(10) unsigned NOT NULL DEFAULT '0',
				  `play_week` int(10) unsigned NOT NULL DEFAULT '0',
				  `play_day` int(10) unsigned NOT NULL DEFAULT '0',
				  `stats_date` int(10) unsigned NOT NULL DEFAULT '0',
				  `play_type` varchar(25) NOT NULL,
				  PRIMARY KEY (`user_id`,`play_type`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				";
		return $tables;
	}
	
	
	public function getData()
	{
		$data = array();
		$data['xf_content_type'] = "
			INSERT IGNORE INTO xf_content_type
				(content_type, addon_id, fields)
			VALUES
				('rock_paper_scissors', 'Brivium_RockPaperScissors', ''),
				('rps_challenge', 'Brivium_RockPaperScissors', '');
		";
		
		$data['xf_content_type_field'] = "
			INSERT IGNORE INTO `xf_content_type_field` 
				(`content_type`, `field_name`, `field_value`) 
			VALUES
				('rock_paper_scissors', 'alert_handler_class', 'Brivium_RockPaperScissors_AlertHandler_RockPaperScissors'),
				('rps_challenge', 'alert_handler_class', 'Brivium_RockPaperScissors_AlertHandler_Challenge');
		";
		return $data;
	}
	
	public function getAlters()
	{
		$alters = array();
		$alters['xf_user_privacy'] = array(
			'brrps_allow_challenge' => "ENUM(  'everyone',  'members',  'followed',  'none' ) NOT NULL DEFAULT  'everyone'"
		);
		return $alters;
	}
	
	public function getQueryFinal()
	{
		$query = array();
		$query[] = "
			DELETE FROM `xf_brivium_listener_class` WHERE `addon_id` = 'Brivium_RockPaperScissors';
		";
		if($this->_triggerType != "uninstall"){
			$query[] = "
				REPLACE INTO `xf_brivium_addon` 
					(`addon_id`, `title`, `version_id`, `copyright_removal`, `start_date`, `end_date`) 
				VALUES
					('Brivium_RockPaperScissors', 'Brivium - Rock Paper Scissors', '1010020', 0, 0, 0);
			";
			$query[] = "
				REPLACE INTO `xf_brivium_listener_class` 
					(`class`, `class_extend`, `event_id`, `addon_id`) 
				VALUES
					('XenForo_ControllerPublic_Account', 'Brivium_RockPaperScissors_ControllerPublic_Account', 'load_class_controller', 'Brivium_RockPaperScissors'),
					('XenForo_DataWriter_User', 'Brivium_RockPaperScissors_DataWriter_User', 'load_class_datawriter', 'Brivium_RockPaperScissors'),
					('XenForo_Model_User', 'Brivium_RockPaperScissors_Model_User', 'load_class_model', 'Brivium_RockPaperScissors');
			";
		}else{
			$query[] = "
				DELETE FROM `xf_brivium_addon` WHERE `addon_id` = 'Brivium_RockPaperScissors';
			";
		}
		return $query;
	}
}

?>