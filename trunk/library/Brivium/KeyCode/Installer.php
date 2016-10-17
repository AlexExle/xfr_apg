<?php
class Brivium_KeyCode_Installer extends Brivium_BriviumHelper_Installer
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

	protected function _getPrerequisites()
    {
        return array(
			'Brivium_Credits'	=>	array(
				'title'	=>	'Brivium - Credits (Lite or Premium)',
				'version_id'	=>	0,
			),
		);
    }
	protected function _postUninstall()
	{
		$creditsAddon = XenForo_Model::create('XenForo_Model_AddOn')->getAddOnVersion('Brivium_Credits');
		if ($creditsAddon < 2000000) {
			$dw = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Action', XenForo_DataWriter::ERROR_SILENT);
			if ($dw->setExistingData('BRKC_PurchaseKeyCode'))
			{
				$dw->delete();
			}
		}
	}
	public function getTables()
	{
		$tables = array();

		$tables['xf_brivium_keycode_category'] = "
				CREATE TABLE IF NOT EXISTS `xf_brivium_keycode_category` (
				  `code_category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `title` text NOT NULL,
				  `price` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
				  `currency_id` int(10) unsigned NOT NULL DEFAULT '0',
				  `max_code_options` mediumblob NOT NULL,
				  `user_groups` mediumblob NOT NULL,
				  `active` tinyint(3) unsigned NOT NULL DEFAULT '1',
				  PRIMARY KEY (`code_category_id`),
				  KEY `category_id` (`code_category_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;
				";
		$tables['xf_brivium_keycode_code'] = "
				CREATE TABLE IF NOT EXISTS `xf_brivium_keycode_code` (
				  `code_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `code` varchar(200) NOT NULL,
				  `code_category_id` int(10) unsigned NOT NULL DEFAULT '0',
				  `create_date` int(10) unsigned NOT NULL DEFAULT '0',
				  `date_receive` int(10) unsigned NOT NULL DEFAULT '0',
				  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
				  `code_state` varchar(30) NOT NULL,
				  PRIMARY KEY (`code_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;
				";
		return $tables;
	}

	public function getQueryFinal()
	{
		$query = array();
		$query[] = "
			DELETE FROM `xf_brivium_listener_class` WHERE `addon_id` = 'Brivium_KeyCode';
		";
		if($this->_triggerType != "uninstall"){
			$query[] = "
				REPLACE INTO `xf_brivium_addon` 
					(`addon_id`, `title`, `version_id`, `copyright_removal`, `start_date`, `end_date`) 
				VALUES
					('Brivium_KeyCode', 'Brivium - Key Code', '1010300', 0, 0, 0);
			";
		}else{
			$query[] = "
				DELETE FROM `xf_brivium_addon` WHERE `addon_id` = 'Brivium_KeyCode';
			";
		}
		return $query;
	}
}