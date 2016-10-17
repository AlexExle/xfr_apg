<?php
class Brivium_CreResIntegration_Installer extends Brivium_BriviumHelper_Installer
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
			'XenResource'	=>	array(
				'title'	=>	'XenForo Resource Manager',
				'version_id'	=>	1010670,
			),
			'Brivium_Credits'	=>	array(
				'title'	=>	'Brivium - Credits Premium',
				'version_id'	=>	2000000,
			),
		);
	}

	protected function _postUninstall()
	{
		$creditsAddon = XenForo_Model::create('XenForo_Model_AddOn')->getAddOnVersion('Brivium_Credits');
		if ($creditsAddon < 2000000) {
			$dw = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Action', XenForo_DataWriter::ERROR_SILENT);
			if ($dw->setExistingData('ResourceGetPurchased'))
			{
				$dw->delete();
			}
			$dw2 = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Action', XenForo_DataWriter::ERROR_SILENT);
			if ($dw2->setExistingData('ResourcePurchased'))
			{
				$dw2->delete();
			}
		}
	}

	public function getTables()
	{
		$tables = array();
		$tables["xf_resource_purchased"] = "
			CREATE TABLE IF NOT EXISTS `xf_resource_purchased` (
			  `resource_purchased_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `resource_id` int(10) unsigned NOT NULL,
			  `currency_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `user_id` int(10) unsigned NOT NULL,
			  `resource_version_id` int(10) unsigned NOT NULL,
			  `purchased_date` int(10) unsigned NOT NULL,
			  `resource_price` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
			  `active` tinyint(3) unsigned NOT NULL DEFAULT '1',
			  PRIMARY KEY (`resource_purchased_id`),
			  KEY `resource_id` (`resource_id`),
			  KEY `currency_id` (`currency_id`),
			  KEY `user_id` (`user_id`),
			  KEY `user_resource` (`user_id`,`resource_id`),
			  KEY `purchased_date` (`purchased_date`),
			  KEY `resource_price` (`resource_price`),
			  KEY `active` (`active`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8
		";
		return $tables;
	}


	public function getData()
	{
		$data = array();
		return $data;
	}

	public function getAlters()
	{
		$alters = array();
		$alters['xf_resource'] = array(
			'credit_price'	=>	" decimal(19,4) unsigned NOT NULL DEFAULT '0.0000'",
			'brcri_currency_id'	=>	" int(10) unsigned NOT NULL DEFAULT '0'",
		);
		return $alters;
	}

	public function getQueryBeforeData()
	{
		$query = array();
		$query[] = "
			ALTER TABLE  `xf_resource_purchased`
			ADD KEY `resource_id` (`resource_id`),
			ADD KEY `currency_id` (`currency_id`),
			ADD KEY `user_id` (`user_id`),
			ADD KEY `user_resource` (`user_id`, `resource_id`),
			ADD KEY `purchased_date` (`purchased_date`),
			ADD KEY `resource_price` (`resource_price`),
			ADD KEY `active` (`active`);
		";
		$query[] = "
			ALTER TABLE  `xf_resource`
			ADD KEY `credit_price` (`credit_price`),
			ADD KEY `brcri_currency_id` (`brcri_currency_id`);
		";
		return $query;
	}

	public function getQueryFinal()
	{
		$query = array();
		$query[] = "
			DELETE FROM `xf_brivium_listener_class` WHERE `addon_id` = 'Brivium_CreResIntegration';
		";
		if($this->_triggerType != "uninstall"){
			$query[] = "
				REPLACE INTO `xf_brivium_addon` 
					(`addon_id`, `title`, `version_id`, `copyright_removal`, `start_date`, `end_date`) 
				VALUES
					('Brivium_CreResIntegration', 'Brivium - Credits Resource Integration', '1040800', 0, 0, 0);
			";
			$query[] = "
				REPLACE INTO `xf_brivium_listener_class` 
					(`class`, `class_extend`, `event_id`, `addon_id`) 
				VALUES
					('XenResource_ControllerPublic_Resource', 'Brivium_CreResIntegration_ControllerPublic_Resource', 'load_class_controller', 'Brivium_CreResIntegration'),
					('XenResource_DataWriter_Resource', 'Brivium_CreResIntegration_DataWriter_Resource', 'load_class_datawriter', 'Brivium_CreResIntegration'),
					('XenResource_Model_Resource', 'Brivium_CreResIntegration_Model_Resource', 'load_class_model', 'Brivium_CreResIntegration');
			";
		}else{
			$query[] = "
				DELETE FROM `xf_brivium_addon` WHERE `addon_id` = 'Brivium_CreResIntegration';
			";
		}
		return $query;
	}
}