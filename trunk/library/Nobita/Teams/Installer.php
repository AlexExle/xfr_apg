<?php

if (!defined('TEAM_DATAREGISTRY_KEY'))
{
	define('TEAM_DATAREGISTRY_KEY', 'Teams_group_perms');
}

class Nobita_Teams_Installer
{
	protected static $_oldVersionId;
	protected static $_newVersionId;

	protected static $_db;

	protected static $_tables = array(
		'Field' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_field`(
				`field_id` varbinary(25) NOT NULL
				,`display_group` varchar(25) NOT NULL DEFAULT \'above_info\'
				,`display_order` int(10) unsigned NOT NULL DEFAULT \'1\'
				,`field_type` varchar(25) NOT NULL DEFAULT \'textbox\'
				,`field_choices` blob NOT NULL
				,`match_type` varchar(25) NOT NULL DEFAULT \'none\'
				,`match_regex` varchar(250) NOT NULL DEFAULT \'\'
				,`match_callback_class` varchar(75) NOT NULL
				,`match_callback_method` varchar(75) NOT NULL
				,`max_length` int(10) unsigned NOT NULL DEFAULT \'0\'
				,`required` tinyint(3) unsigned NOT NULL DEFAULT \'0\'
				,`display_template` text NOT NULL
				, PRIMARY KEY (`field_id`)
				, KEY `display_group_order` (`display_group`,`display_order`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_field`'
		),
		'FieldValue' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_field_value` (
				`team_id` int(10) unsigned NOT NULL
				,`field_id` varbinary(25) NOT NULL
				,`field_value` mediumtext NOT NULL
				, PRIMARY KEY (`team_id`,`field_id`)
				, KEY `field_id` (`field_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_field_value`'
		),
		'Category' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_category` (
				`team_category_id` int(10) unsigned NOT NULL AUTO_INCREMENT
				,`category_title` varchar(100) NOT NULL
				,`category_description` text NOT NULL
				,`parent_category_id` int(10) unsigned NOT NULL DEFAULT \'0\'
				,`depth` smallint(5) unsigned NOT NULL DEFAULT \'0\'
				,`lft` int(10) unsigned NOT NULL DEFAULT \'0\'
				,`rgt` int(10) unsigned NOT NULL DEFAULT \'0\'
				,`display_order` int(10) unsigned NOT NULL DEFAULT \'0\'
				,`team_count` int(10) unsigned NOT NULL DEFAULT \'0\'
				,`category_breadcrumb` blob NOT NULL
				,`always_moderate_create` tinyint(3) unsigned NOT NULL DEFAULT \'0\'
				,`field_cache` mediumblob NOT NULL
				,`featured_count` int(10) unsigned not null default \'0\'
				,`allow_team_create` tinyint(3) unsigned not null default \'1\'
				,`allowed_user_group_ids` blob not null
				,`allow_uploaded_file` TINYINT(3) UNSIGNED NOT NULL DEFAULT \'0\'
				, PRIMARY KEY (`team_category_id`)
				, KEY `parent_category_id_lft` (`parent_category_id`,`lft`)
				, KEY `lft_rgt` (`lft`,`rgt`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_category`'
		),
		'CategoryFieldValue' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_field_category` (
				`field_id` varbinary(25) NOT NULL
				,`team_category_id` int(11) NOT NULL
				, PRIMARY KEY (`field_id`,`team_category_id`)
				, KEY `team_category_id` (`team_category_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_field_category`'
		),
		'TeamInfo' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team` (
				`team_id` int(10) unsigned not null AUTO_INCREMENT
				,`title` varchar(100) not null
				,`tag_line` varchar(100) not null
				,`custom_url` varchar(25) default NULL
				,`user_id` int(10) unsigned not null
				,`username` varchar(50) not null default \'\'
				,`team_state` enum(\'visible\', \'moderated\', \'deleted\') default \'visible\'
				,`team_date` int(10) unsigned not null default \'0\'
				,`team_category_id` int(10) unsigned not null
				,`team_avatar_date` int(10) unsigned not null default \'0\'
				,`member_count` int(10) unsigned not null default \'0\'
				,`warning_id` int(10) unsigned not null default \'0\'
				,`last_updated` int(10) unsigned not null default \'0\'
				, PRIMARY KEY (`team_id`)
				, KEY `user_category_id` (`user_id`, `team_category_id`)
				, KEY `team_avatar` (`team_date`, `team_avatar_date`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team`'
		),
		'TeamProfile' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_profile` (
				`team_id` int(10) unsigned not null
				,`about` mediumtext not null
				,`custom_fields` mediumblob not null
				,`member_request_count` int(10) unsigned not null default \'0\'
				,`ribbon_display_class` varchar(50) not null default \'\'
				,`ribbon_text` varchar(25) not null default \'\'
				, PRIMARY KEY(`team_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_profile`'
		),
		'TeamPrivacy' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_privacy` (
				`team_id` int(10) unsigned not null
				,`allow_guest_posting` tinyint(3) unsigned not null default \'0\'
				,`allow_member_posting` tinyint(3) unsigned not null default \'1\'
				,`always_moderate_join` tinyint(3) unsigned not null default \'0\'
				,`always_moderate_posting` tinyint(3) unsigned not null default \'1\'
				, PRIMARY KEY (`team_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_privacy`'
		),
		'TeamFetured' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_feature` (
				`team_id` int(10) unsigned not null default \'0\'
				,`feature_date` int(10) unsigned not null default \'0\'
				, PRIMARY KEY (`team_id`)
				, KEY `featured_date` (`feature_date`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_feature`'
		),
		'TeamPost' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_post` (
				`post_id` int(10) unsigned not null AUTO_INCREMENT
				,`team_id` int(10) unsigned not null
				,`user_id` int(10) unsigned not null
				,`username` varchar(50) not null default \'\'
				,`message_state` enum(\'visible\', \'moderated\', \'deleted\') default \'visible\'
				,`post_date` int(10) unsigned not null default \'0\'
				,`message` mediumtext not null
				,`likes` int(10) unsigned not null default \'0\'
				,`like_users` mediumblob not null
				,`comment_count` int(10) unsigned not null default \'0\'
				,`warning_id` int(10) unsigned not null default \'0\'
				,`first_comment_date` int(10) unsigned not null default \'0\'
				,`last_comment_date` int(10) unsigned not null default \'0\'
				,`latest_comment_ids` varbinary(100) not null default \'\'
				,`sticky` tinyint(3) unsigned not null default \'0\'
				,`share_privacy` varbinary(25) not null
				,`attach_count` smallint(5) unsigned not null default \'0\'
				, PRIMARY KEY (`post_id`)
				, KEY `team_user` (`team_id`, `user_id`)
				, KEY `post_date` (`post_date`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_post`'
		),
		'TeamPostComment' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_comment` (
				`comment_id` int(10) unsigned AUTO_INCREMENT
				,`post_id` int(10) unsigned not null
				,`team_id` int(10) unsigned not null
				,`user_id` int(10) unsigned not null
				,`username` varchar(50) not null default \'\'
				,`comment_date` int(10) unsigned not null
				,`message` mediumtext not null
				, PRIMARY KEY (`comment_id`)
				, KEY `post_user` (`post_id`, `user_id`)
				, KEY `comment_date` (`comment_date`)
			)ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_comment`'
		),

		'TeamMember' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_member` (
				`team_id` int(10) unsigned not null
				,`user_id` int(10) unsigned not null
				,`username` varchar(50) not null default \'\'
				,`member_state` enum(\'request\', \'accept\', \'\') not null default \'accept\'
				,`position` enum(\'member\', \'admin\') not null default \'member\'
				,`join_date` int(10) unsigned not null
				,`alert` tinyint(3) unsigned not null default \'1\'
				,`action` varchar(25) not null default \'\'
				,`action_user_id` int(10) unsigned not null default \'0\'
				,`action_username` varchar(50) not null default \'\'
				, PRIMARY KEY (`team_id`,`user_id`)
				, KEY `action_user_id` (`action_user_id`)
				, KEY `join_date` (`join_date`)
			)ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_member`'
		),

		'categoryWatch' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_category_watch` (
				`user_id` int(10) unsigned not null
				,`team_category_id` int(10) unsigned not null
				,`notify_on` enum(\'\', \'team\') not null
				,`send_alert` tinyint(3) unsigned not null
				,`send_email` tinyint(3) unsigned not null
				,`include_children` tinyint(3) unsigned not null
				,PRIMARY KEY (`user_id`, `team_category_id`)
				,KEY `team_category_id` (`team_category_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_category_watch`'
		),

		'watchPost' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_post_watch` (
				`post_id` int(10) unsigned not null
				,`user_id` int(10) unsigned not null
				,PRIMARY KEY (`post_id`, `user_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_post_watch`'
		),

		/* BUILDING EVENTS FOR TEAM! */
		'Event' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_event` (
				`event_id` int(10) unsigned auto_increment
				,`event_title` varchar(100) not null
				,`team_id` int(10) unsigned not null
				,`user_id` int(10) unsigned not null
				,`username` varchar(50) not null
				,`event_type` varbinary(25) not null default \'public\'
				,`event_description` mediumtext not null
				,`publish_date` int(10) unsigned not null

				,`begin_date` int(10) unsigned not null
				,`end_date` int(10) unsigned not null

				,`allow_member_comment` tinyint(1) unsigned not null default \'0\'
				,PRIMARY KEY (`event_id`)
				,KEY `team_user` (`team_id`, `user_id`)
				,KEY `date` (`publish_date`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_event`'
		),
		/* END EVENTS DATA */

		'userRole' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_member_role` (
				`member_role_id` varbinary(25) not null,
				`roles` blob not null,
				`display_order` int(10) unsigned not null,
				`notice` tinyint(1) unsigned not null default \'0\',
				`is_staff` tinyint(1) unsigned not null default \'0\',
				PRIMARY KEY (`member_role_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_member_role`'
		),

		'banningUser' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_ban` (
				`team_id` int unsigned not null,
				`user_id` int unsigned not null,
				`ban_user_id` int unsigned not null,
				`ban_date` int unsigned not null default \'0\',
				`end_date` int unsigned not null default \'0\',
				`user_reason` varchar(255) not null,
				PRIMARY KEY (`team_id`, `user_id`),
				KEY `ban_user_id` (`ban_user_id`),
				INDEX end_date (end_date)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_ban`'
		),

		'teamDeferred' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_deferred` (
				`team_deferred_id` int unsigned auto_increment,
				`content_type` varbinary(25) not null,
				`content_id` int unsigned not null,
				`data` blob not null,
				`trigger_date` int unsigned not null,
				PRIMARY KEY (`team_deferred_id`),
				KEY `content_id` (`content_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_deferred`'
		),

		// log view
		'teamViewLog' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_team_view` (
				team_id int unsigned not null,
				KEY `team_id` (`team_id`)
			) ENGINE=MEMORY DEFAULT CHARSET=utf8;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_view`'
		),

		// NewsFeed table
		'NewsFeed' => array(
			'createQuery' => "CREATE TABLE IF NOT EXISTS xf_team_news_feed(
				news_feed_id int unsigned auto_increment,
				team_id int unsigned not null,
				content_type varbinary(25) not null,
				content_id int unsigned not null,
				event_date int unsigned not null,
				extra_data mediumblob not null,
				PRIMARY KEY (news_feed_id),
				INDEX event_date (event_date)
			)ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;",
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_team_news_feed`'
		),

		// Build stats Table
		'StatsDaily' => array(
			'createQuery' => "CREATE TABLE IF NOT EXISTS xf_team_stats_daily(
				team_id int unsigned not null,
				stats_date int unsigned not null,
				stats_type varbinary(25) not null,
				counter int unsigned not null default 0,
				PRIMARY KEY (team_id, stats_date, stats_type),
				INDEX stats_date (stats_date)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;",
			'dropQuery' => "DROP TABLE IF EXISTS `xf_team_stats_daily`"
		),
	);

	protected static $_patches = array(
		array(
			'table' => 'xf_user',
			'field' => 'manage_team_count',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_user\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_user` LIKE \'manage_team_count\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_user` ADD COLUMN `manage_team_count` INT(10) UNSIGNED NOT NULL DEFAULT \'0\',
				ADD INDEX `manage_team_count` (`manage_team_count`)',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_user` DROP COLUMN `manage_team_count`'
		),
		array(
			'table' => 'xf_user',
			'field' => 'team_ribbon_id',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_user\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_user` LIKE \'team_ribbon_id\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_user` ADD COLUMN `team_ribbon_id` INT(10) UNSIGNED NOT NULL DEFAULT \'0\',
				ADD INDEX `team_ribbon_id` (`team_ribbon_id`)',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_user` DROP COLUMN `team_ribbon_id`'
		),

		array(
			'table' => 'xf_thread',
			'field' => 'team_id',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_thread\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_thread` LIKE \'team_id\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_thread` ADD COLUMN `team_id` INT(10) UNSIGNED DEFAULT NULL',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_thread` DROP COLUMN `team_id`'
		),
		array(
			'table' => 'xf_thread',
			'field' => 'vb_thread_id',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_thread\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_thread` LIKE \'vb_thread_id\'',
			'alterTableAddColumnQuery' => null,
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_thread` DROP COLUMN `vb_thread_id`'
		),
		array(
			'table' => 'xf_post',
			'field' => 'vb_thread_id',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_post\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_post` LIKE \'vb_thread_id\'',
			'alterTableAddColumnQuery' => null,
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_post` DROP COLUMN `vb_thread_id`'
		)
	);

	public static function install($previous, $current = null)
	{
		$db = XenForo_Application::get('db');
		self::$_db = $db;

		/*if (XenForo_Application::$versionId < 1050000) {
			throw new XenForo_Exception("This addon requirement XenForo 1.5 or higher", true);
		}*/

		if (PHP_VERSION_ID < 50400) {
			throw new XenForo_Exception("This addon requirement PHP version 5.4 or higher", true);
		}

		self::$_oldVersionId = @$previous['version_id'];
		self::$_newVersionId = @$current['version_id'];

		foreach (self::$_tables as $tableKey => $table) 
		{
			$db->query($table['createQuery']);
		}

		foreach (self::$_patches as $patch) {
			if (empty($patch['alterTableAddColumnQuery']))
			{
				continue;
			}

			try
			{
				$db->query($patch['alterTableAddColumnQuery']);
			}
			catch(Zend_Db_Exception $e) {}
		}

		self::installCustomized();
		//self::applyPermissionDefaults($previous);

		/* change the type of column! */
		self::changeColumnsType($previous, $current);
		self::removeDatasFromOldVersion($previous, $current);

		// add built-in custom fields
		// rules
		self::removeCustomFieldsRules($db);

		// move field privacy_state on table `xf_team_privacy`
		// to the table `xf_team`
		self::_movePrivacyField($db, $previous['version_id']);

		$updatePackage = dirname(__FILE__) . '/Install';
		$updatePerms = array();

		foreach(glob($updatePackage . '/*.php') as $path)
		{
			$basename = basename($path);
			if ($basename == 'Skeleton.php')
			{
				continue;
			}

			require_once $path;

			$versionName = intval($basename);
			$class = 'Nobita_Teams_Install_' . $versionName;
			if (! class_exists($class))
			{
				continue;
			}

			$obj = new $class;
			if ($obj->isUpdate(self::$_oldVersionId, self::$_newVersionId))
			{
				$obj->doUpdate($db, self::$_oldVersionId);
				$updatePerms = array_merge($updatePerms, (array)$obj->doUpdatePermissions());
			}
		}

		self::_migrateThreadPolls278();

		self::_insertCategoryExampleOnNewInstall();

		// change in version 2.3.1
		self::_updatePostStructure231();
		self::_addTimeZoneToEventAndSetDefault231();

		if ($updatePerms)
		{
			foreach($updatePerms as $key => $perms)
			{
				$self = XenForo_Application::resolveDynamicClass(__CLASS__);
				call_user_func_array(array($self, 'applyGlobalPermission'), $perms);
			}
		}

		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');

		$adminRole = $db->fetchRow('SELECT * FROM xf_team_member_role WHERE member_role_id = ?', 'admin');
		if(empty($adminRole))
		{
			$db->query('
				INSERT INTO xf_team_member_role
					(member_role_id, roles, display_order, notice, is_staff)
				VALUES
					(?, ?, ?, ?, ?)
			', array('admin', 'a:0:{}', 10, 0, 1));
			$memberRoleModel->insertOrUpdateMasterPhrase('admin', 'Admin');
		}

		$memberRole = $db->fetchRow('SELECT * FROM xf_team_member_role WHERE member_role_id = ?', 'member');
		if(empty($adminRole))
		{
			$db->query('
				INSERT INTO xf_team_member_role
					(member_role_id, roles, display_order, notice, is_staff)
				VALUES
					(?, ?, ?, ?, ?)
			', array('member', 'a:0:{}', 10, 0, 0));

			$memberRoleModel->insertOrUpdateMasterPhrase('admin', 'Member');
		}

		$memberRoleModel->saveMemberRolesToCache();

		Nobita_Teams_sonnb_XenGallery_Installer::install(null, $previous, $current);
		Nobita_Teams_XenGallery_Installer::install($db);

		XenForo_Application::defer(
			Nobita_Teams_Model_Deferred::DEFERRED_CLASS, array(), 'nobita_Teams_deferred', false, XenForo_Application::$time + 300
		);
		Nobita_Teams_Container::getModel('Nobita_Teams_Model_Deferred')->insertDefaultQueue();

		if(self::$_oldVersionId < 2040640) {
			XenForo_Application::defer(
				'Nobita_Teams_Deferred_Team', array(), 'nobita_Teams'
			);
		}

	}

	protected static function _migrateThreadPolls278()
	{
		// migrate some thread missing polls
		if (self::$_oldVersionId < 278) {
			$db = self::$_db;

			$pollIds = $db->fetchCol('SELECT content_id FROM xf_poll WHERE content_type = \'thread\'');
			if (! empty($pollIds))
			{
				// GOOD
				foreach ($pollIds as $threadId)
				{
					try
					{
						$db->query("
							UPDATE xf_thread
							SET discussion_type = 'poll'
							WHERE thread_id = ?
						", array($threadId));
					}
					catch(Zend_Db_Exception $e) {}
				}

			}
		}
	}

	protected static function _addTimeZoneToEventAndSetDefault231()
	{
		$defaultTimeZone = XenForo_Application::get('options')->guestTimeZone;

		try
		{
			self::$_db->query("ALTER TABLE `xf_team_event` ADD COLUMN `timezone` varchar(50) not null default ''");
		}
		catch(Zend_Db_Exception $e) {}

		if (self::$_oldVersionId < 260)
		{
			// set default to previous version
			try
			{
				self::$_db->query("
					UPDATE xf_team_event
					SET timezone = ?
					WHERE 1=1
				", array($defaultTimeZone));
			}
			catch(Zend_Db_Exception $e) {}
		}
	}

	protected static function _updatePostStructure231()
	{
		// update post structure in version 2.3.1

		$db = self::$_db;

		// Remove message_count on table xf_team
		try
		{
			$db->query("ALTER TABLE xf_team DROP COLUMN message_count");
		}
		catch(Zend_Db_Exception $e) {}

		// change discussion_type to share_privacy
		try
		{
			$db->query("ALTER TABLE xf_team_post CHANGE discussion_type share_privacy varbinary(25) not null");
		}
		catch(Zend_Db_Exception $e) {}
	}

	protected static function _insertCategoryExampleOnNewInstall()
	{
		if (is_null(self::$_oldVersionId))
		{
			// Well. New Install
			self::$_db->query("
				INSERT IGNORE INTO xf_team_category
					(category_title, display_order, allow_uploaded_file, allowed_user_group_ids)
				VALUES
					(?, ?, ?, ?)
			", array('Example Category', 10, 1, serialize(array(-1))));

			Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category')->rebuildCategoryStructure();
		}
	}

	protected static function _movePrivacyField($db, $oldVersionId)
	{
		try
		{
			$db->query("ALTER TABLE xf_team ADD COLUMN privacy_state varbinary(25) not null default 'open' AFTER team_state");
		}
		catch(Zend_Db_Exception $e) {}

		if ($oldVersionId < 191)
		{
			try
			{
				$oldPrivacy = $db->fetchAll('
					SELECT team_id, privacy_state
					FROM xf_team_privacy
					WHERE 1=1
					ORDER BY team_id
				');
			}
			catch(Zend_Db_Exception $e)
			{
				$oldPrivacy = false;
			}

			if ($oldPrivacy)
			{
				foreach($oldPrivacy as $updateInfo) {
					try
					{
						$db->query("
							UPDATE xf_team
							SET privacy_state = ?
							WHERE team_id = ?
						", array($updateInfo['privacy_state'], $updateInfo['team_id']));
					}
					catch(Zend_Db_Exception $e) {}
				}
			}
		}

		try
		{
			$db->query("ALTER TABLE xf_team_privacy DROP COLUMN privacy_state");
		}
		catch(Zend_Db_Exception $e) {}
	}

	public static function removeCustomFieldsRules($db)
	{
		$rulesFieldId = 'rules';
		$existed = $db->fetchOne('SELECT field_id FROM xf_team_field WHERE field_id = ?', $rulesFieldId);

		if (!empty($existed))
		{
			// delete rules field
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_TeamField');
			$dw->setExistingData($existed);

			$dw->delete();
		}
	}

	public static function changeColumnsType($previous, $current)
	{
		$db = XenForo_Application::get('db');

		$changes = array();
		$oldVersionId = $previous['version_id'];

		$changes[] = array(
			'table' => 'xf_team_member',
			'field' => 'member_state',
			'changeSql' => 'varbinary(25) not null'
		);

		$changes[] = array(
			'table' => 'xf_team_member',
			'field' => 'position',
			'changeSql' => 'varbinary(25) not null'
		);

		$changes[] = array(
			'table' => 'xf_team_member',
			'field' => 'action',
			'changeSql' => 'varbinary(25) not null'
		);

		$changes[] = array(
			'table' => 'xf_team',
			'field' => 'custom_url',
			'changeSql' => 'varchar(25) default NULL'
		);

		$changes[] = array(
			'table' => 'xf_team_profile',
			'field' => 'ribbon_display_class',
			'changeSql' => 'varchar(50) not null'
		);

		// 2.2.3
		$changes[] = array(
			'table' => 'xf_team_deferred',
			'field' => 'action',
			'new' => 'content_type',
			'changeSql' => 'varbinary(50) not null'
		);
		$changes[] = array(
			'table' => 'xf_team_deferred',
			'field' => 'data_id',
			'new' => 'content_id',
			'changeSql' => 'int unsigned not null'
		);

		if ($changes)
		{
			foreach($changes as $value)
			{
				$newField = isset($value['new']) ? $value['new'] : $value['field'];
				$field = $value['field'];

				try
				{
					$db->query(sprintf("ALTER TABLE %s CHANGE %s %s %s", $value['table'], $field, $newField, $value['changeSql']));
				}
				catch(Zend_Db_Exception $e) {}
			}
		}

		if ($previous['version_id'] < 138)
		{
			// reset all avatar
			try {
				$db->query("
				UPDATE xf_team
				SET avatar_date = 0, cover_date = 0
				WHERE 1 = 1
			");
			} catch (Zend_Db_Exception $e) {}
		}

		// version 2.1.2
		if ($previous['version_id'] < 150)
		{
			// change from avatar_date -> team_avatar_date
			try
			{
				$db->query("
					UPDATE xf_team_post
					SET discussion_type = 'staff'
					WHERE discussion_type = 'moderator'
				");
			}
			catch(Zend_Db_Exception $e) {}
		}
	}

	/**
	 * Delete all datas from old version which don't
	 * necessary to using.
	 *
	 */
	public static function removeDatasFromOldVersion($old, $new)
	{
		$db = XenForo_Application::get('db');
		$oldVersionId = $old['version_id'];

		$fields = array();

		if ($oldVersionId < 108)
		{
			$fields = array_merge($fields, array(
				'cover_width' => 'xf_team',
				'cover_height' => 'xf_team',
				'cover_crop_x' => 'xf_team_profile',
				'cover_crop_y' => 'xf_team_profile'
			));
		}
		elseif ($oldVersionId > 109 && $oldVersionId < 126)
		{
			$db->delete('xf_data_registry', 'data_key = ' . $db->quote('Teams_group_perms'));

			$fields = array_merge($fields, array(
				'ban_users' => 'xf_team',
				'avatar_width' => 'xf_team',
				'avatar_height' => 'xf_team',
				'avatar_crop_x' => 'xf_team_profile',
				'avatar_crop_y' => 'xf_team_profile'
			));
		}
		elseif ($oldVersionId > 127 && $oldVersionId < 144)
		{
			$fields = array_merge($fields, array(
				'content_team_id' => 'xf_attachment'
			));
		}
		elseif ($oldVersionId > 145 && $oldVersionId < 175)
		{
			$fields = array_merge($fields, array(
				'discussion_prefix_id' => 'xf_team_category'
			));
		}

		if ($fields)
		{
			foreach($fields as $fieldName => $tableName)
			{
				try
				{
					$db->query('ALTER TABLE `' . $tableName . '` DROP COLUMN `' . $fieldName . '`');
				}
				catch(Zend_Db_Exception $e) {}
			}
		}
	}

	public static function applyPermissionDefaults()
	{
		$perms = array();
		if (self::$_oldVersionId < 50)
		{
			$perms['view'] = array('Teams', 'view', 'general', 'viewNode', false);
			$perms['add'] = array('Teams', 'add', 'forum', 'postThread', false);
			$perms['updateSelf'] = array('Teams', 'updateSelf', 'forum', 'editOwnPost', false);
			$perms['deleteSelf'] = array('Teams', 'deleteSelf', 'forum', 'deleteOwnPost', false);
			$perms['deletePostSelf'] = array('Teams', 'deletePostSelf', 'forum', 'deleteOwnPost', false);

			$perms['avatar'] = array('Teams', 'avatar', 'avatar', 'allowed', false);
			$perms['editAny'] = array('Teams', 'editAny', 'forum', 'editAnyPost', true);
			$perms['deleteAny'] = array('Teams', 'deleteAny', 'forum', 'deleteAnyPost', true);
			$perms['viewModerated'] = array('Teams', 'viewModerated', 'forum', 'viewModerated', true);
			$perms['viewDeleted'] = array('Teams', 'viewDeleted', 'forum', 'viewDeleted', true);
			$perms['hardDeleteAny'] = array('Teams', 'hardDeleteAny', 'forum', 'hardDeleteAnyPost', true);
			$perms['warn'] = array('Teams', 'warn', 'forum', 'warn', true);

			$perms['undelete'] = array('Teams', 'undelete', 'forum', 'undelete', true);
			$perms['approveUnapprove'] = array('Teams', 'approveUnapprove', 'forum', 'approveUnapprove', true);
			$perms['approveUnapprovePost'] = array('Teams', 'approveUnapprovePost', 'forum', 'approveUnapprove', true);
			$perms['viewModeratedPost'] = array('Teams', 'viewModeratedPost', 'forum', 'viewModerated', true);
			$perms['editPostAny'] = array('Teams', 'editPostAny', 'forum', 'editAnyPost', true);
			$perms['deletePostAny'] = array('Teams', 'deletePostAny', 'forum', 'deleteAnyPost', true);

			$perms['stickyPost'] = array('Teams', 'stickyPost', 'forum', 'stickUnstickThread', true);
			$perms['featureUnfeature'] = array('Teams', 'featureUnfeature', 'forum', 'stickUnstickThread', true);
		}

		if (self::$_oldVersionId >  50 && self::$_oldVersionId < 55)
		{
			$perms['viewAttachment'] = array('Teams', 'viewAttachment', 'forum', 'viewAttachment', false);
			$perms['uploadAttachment'] = array('Teams', 'uploadAttachment', 'forum', 'uploadAttachment', false);
			$perms['reassignAny'] = array('Teams', 'reassignAny', 'forum', 'editAnyPost', true);
		}

		if (self::$_oldVersionId > 80 && self::$_oldVersionId < 99)
		{
			$perms['editEventAny'] = array('Teams', 'editEventAny', 'forum', 'editAnyPost', true);
			$perms['deleteEventAny'] = array('Teams', 'deleteEventAny', 'forum', 'deleteAnyPost', true);
		}

		if (self::$_oldVersionId > 180 && self::$_oldVersionId < 192)
		{
			$perms['addSecretGroup'] = array('Teams', 'addSecretGroup', 'forum', 'editOwnPost', false);
		}

		return $perms;
	}

	protected static $_globalModPermCache = null;

	protected static function _getGlobalModPermissions()
	{
		if (self::$_globalModPermCache === null)
		{
			$moderators = XenForo_Application::getDb()->fetchPairs('
				SELECT user_id, moderator_permissions
				FROM xf_moderator
			');
			foreach ($moderators AS &$permissions)
			{
				$permissions = unserialize($permissions);
			}

			self::$_globalModPermCache = $moderators;
		}

		return self::$_globalModPermCache;
	}

	protected static function _updateGlobalModPermissions($userId, array $permissions)
	{
		self::$_globalModPermCache[$userId] = $permissions;

		XenForo_Application::getDb()->query('
			UPDATE xf_moderator
			SET moderator_permissions = ?
			WHERE user_id = ?
		', array(serialize($permissions), $userId));
	}

	public static function applyGlobalPermission($applyGroupId, $applyPermissionId, $dependGroupId = null, $dependPermissionId = null, $checkModerator = true)
	{
		$db = XenForo_Application::getDb();

		XenForo_Db::beginTransaction($db);

		if ($dependGroupId && $dependPermissionId)
		{
			$db->query("
				INSERT IGNORE INTO xf_permission_entry
					(user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
				SELECT user_group_id, user_id, ?, ?, 'allow', 0
				FROM xf_permission_entry
				WHERE permission_group_id = ?
					AND permission_id = ?
					AND permission_value = 'allow'
			", array($applyGroupId, $applyPermissionId, $dependGroupId, $dependPermissionId));
		}
		else
		{
			$db->query("
				INSERT IGNORE INTO xf_permission_entry
					(user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
				SELECT DISTINCT user_group_id, user_id, ?, ?, 'allow', 0
				FROM xf_permission_entry
			", array($applyGroupId, $applyPermissionId));
		}

		if ($checkModerator)
		{
			$moderators = self::_getGlobalModPermissions();
			foreach ($moderators AS $userId => $permissions)
			{
				if (!$dependGroupId || !$dependPermissionId || !empty($permissions[$dependGroupId][$dependPermissionId]))
				{
					$permissions[$applyGroupId][$applyPermissionId] = '1'; // string 1 is stored by the code
					self::_updateGlobalModPermissions($userId, $permissions);
				}
			}
		}

		XenForo_Db::commit($db);
	}

	public static function uninstall() {
		$db = XenForo_Application::get('db');

		foreach (self::$_patches as $patch) {
			try
			{
				$db->query($patch['alterTableDropColumnQuery']);
			}
			catch(Zend_Db_Exception $e) {}
		}

		foreach (self::$_tables as $table) {
			$db->query($table['dropQuery']);
		}

		$updatePackage = dirname(__FILE__) . '/Install';
		$updateFiles = array();

		foreach(glob($updatePackage . '/*.php') as $path) {
			$basename = basename($path);

			if ($basename == 'Skeleton.php') continue;

			require_once $path;

			$class = 'Nobita_Teams_Install_' . intval($basename);
			if (! class_exists($class))
			{
				continue;
			}

			$obj = new $class;
			$obj->doUninstall($db);
		}

		self::uninstallCustomized();

		$deferredExecuteClasses = array(
			'Nobita_Teams_Deferred_Category',
			'Nobita_Teams_Deferred_Deferred',
			'Nobita_Teams_Deferred_Team'
		);
		$deferredExecuteClasses = $db->quote($deferredExecuteClasses);
		$db->delete('xf_deferred', 'execute_class IN (' . $deferredExecuteClasses . ')');
	}

	private static function installCustomized() {
		$db = XenForo_Application::get('db');

		$db->query("
			INSERT IGNORE INTO xf_content_type
				(content_type, addon_id)
			VALUES
				('team_member', 			'nobita_Teams'),
				('team',					'nobita_Teams'),

				('team_post',				'nobita_Teams'),
				('team_comment',			'nobita_Teams'),
				('team_event',				'nobita_Teams')
		");
		$db->query("
			INSERT IGNORE INTO xf_content_type_field
				(content_type, field_name, field_value)
			VALUES
				('team_member', 	'alert_handler_class', 			'Nobita_Teams_AlertHandler_Member'),

				('team',			'report_handler_class',			'Nobita_Teams_ReportHandler_Team'),
				('team',			'warning_handler_class', 		'Nobita_Teams_WarningHandler_Team'),
				('team',			'moderator_log_handler_class',	'Nobita_Teams_ModeratorLogHandler_Team'),
				('team',			'moderation_queue_handler_class','Nobita_Teams_ModerationQueueHandler_Team'),
				('team',			'news_feed_handler_class',		'Nobita_Teams_NewsFeedHandler_Team'),
				('team',			'search_handler_class',			'Nobita_Teams_Search_DataHandler_Team'),
				('team',			'spam_handler_class',			'Nobita_Teams_SpamHandler_Team'),
				('team',			'alert_handler_class',			'Nobita_Teams_AlertHandler_Team'),
				('team',			'stats_handler_class',			'Nobita_Teams_StatsHandler_Team'),
				('team',			'sitemap_handler_class',		'Nobita_Teams_SitemapHandler_Team'),
				('team',			'tag_handler_class',			'Nobita_Teams_TagHandler_Team'),

				('team_post',		'report_handler_class',			'Nobita_Teams_ReportHandler_Post'),
				('team_post',		'alert_handler_class',			'Nobita_Teams_AlertHandler_Post'),
				('team_post',		'like_handler_class', 			'Nobita_Teams_LikeHandler_Post'),
				('team_post',		'news_feed_handler_class', 		'Nobita_Teams_NewsFeedHandler_Post'),
				('team_post',		'attachment_handler_class',		'Nobita_Teams_AttachmentHandler_Post'),
				('team_post',		'search_handler_class',			'Nobita_Teams_Search_DataHandler_Post'),

				('team_comment',	'alert_handler_class',			'Nobita_Teams_AlertHandler_Comment'),
				('team_comment',	'like_handler_class',			'Nobita_Teams_LikeHandler_Comment'),
				('team_comment',	'search_handler_class',			'Nobita_Teams_Search_DataHandler_Comment'),

				('team_event',		'attachment_handler_class',		'Nobita_Teams_AttachmentHandler_Event'),
				('team_event',		'alert_handler_class',			'Nobita_Teams_AlertHandler_Event'),
				('team_event',		'like_handler_class',			'Nobita_Teams_LikeHandler_Event'),
				('team_event',		'news_feed_handler_class',		'Nobita_Teams_NewsFeedHandler_Event'),
				('team_event',		'sitemap_handler_class',		'Nobita_Teams_SitemapHandler_Event'),
				('team_event',		'report_handler_class',			'Nobita_Teams_ReportHandler_Event'),
				('team_event',		'tag_handler_class',			'Nobita_Teams_TagHandler_Event'),
				('team_event',		'search_handler_class',			'Nobita_Teams_Search_DataHandler_Event')
		");

		$alters = array();

		$alters[] = array(
			'table' => 'xf_team_field',
			'field' => 'parent_tab_id',
			'alterQuery' => 'varchar(25) not null default \'\''
		);

		// added 1.0.9 BETA 1 modified on version 1.2
		$alters[] = array(
			'table' => 'xf_team',
			'field' => 'cover_date',
			'alterQuery' => 'int(10) unsigned not null'
		);
		/* END COVER DATA !*/

		/* begin 1.1.2*/
		$alters[] = array(
			'table' => 'xf_team_category',
			'field' => 'default_cover_path',
			'alterQuery' => 'varchar(100) not null default \'\''
		);
		$alters[] = array(
			'table' => 'xf_team_category',
			'field' => 'ribbon_styling',
			'alterQuery' => 'blob default null'
		);

		$alters[] = array(
			'table' => 'xf_team_member',
			'field' => 'req_message',
			'alterQuery' => 'text default null'
		);

		$alters[] = array(
			'table' => 'xf_team_privacy',
			'field' => 'always_req_message',
			'alterQuery' => 'tinyint(1) unsigned not null default \'1\''
		);

		$alters[] = array(
			'table' => 'xf_team_category',
			'field' => 'icon_date',
			'alterQuery' => 'int(10) unsigned not null default \'0\''
		);
		/* end 1.1.2 */

		/* 1.1.3 News fields */
		$alters[] = array(
			'table' => 'xf_team_privacy',
			'field' => 'allow_member_event',
			'alterQuery' => 'tinyint(1) unsigned not null default \'0\''
		);
		$alters[] = array(
			'table' => 'xf_team_comment',
			'field' => 'content_type',
			'alterQuery' => 'varbinary(25) not null'
		);
		/* END 1.1.3 */

		/* 1.2.0 RC2 */
		$alters[] = array(
			'table' => 'xf_team_privacy',
			'field' => 'disable_tabs',
			'alterQuery' => 'varbinary(100) not null'
		);
		/* END 1.2.0 RC2*/

		/* 1.2.2 */
		$alters[] = array(
			'table' => 'xf_team_member_role',
			'field' => 'is_staff',
			'alterQuery' => 'tinyint(1) unsigned not null default \'0\''
		);
		/* */

		// upload attachment to event
		$alters[] = array(
			'table' => 'xf_team_event',
			'field' => 'attach_count',
			'alterQuery' => 'smallint(5) unsigned not null default \'0\''
		);

		/* alert options for member on team! */
		$alters[] = array(
			'table' => 'xf_team_member',
			'field' => 'send_alert',
			'alterQuery' => 'tinyint(1) unsigned not null default \'1\''
		);
		$alters[] = array(
			'table' => 'xf_team_member',
			'field' => 'send_email',
			'alterQuery' => 'tinyint(1) unsigned not null default \'0\''
		);
		/* end */
		/* END 2.2.3 extra fields. */

		/* 2.1.1 fields */
		$alters[] = array(
			'table' => 'xf_team_category',
			'field' => 'default_privacy',
			'alterQuery' => 'varbinary(25) not null default \'open\''
		);

		// in case. prevent duplicate column!
		$disableColumn = $db->fetchOne('SHOW COLUMNS FROM `xf_team_category` LIKE \'disable_tabs\'');
		if ($disableColumn)
		{
			// good
			try
			{
				$db->query('ALTER TABLE `xf_team_category` CHANGE `disable_tabs` `disable_tabs_default` varbinary(100) not null default \'\'');
			}
			catch(Zend_Db_Exception $e) {}
		}
		else
		{
			try
			{
				$db->query("ALTER TABLE xf_team_category ADD COLUMN disable_tabs_default varbinary(100) not null default ''");
			}
			catch (Zend_Db_Exception $e) {}
		}


		$alters[] = array(
			'table' => 'xf_team_comment',
			'field' => 'likes',
			'alterQuery' => 'int unsigned not null default \'0\''
		);
		$alters[] = array(
			'table' => 'xf_team_comment',
			'field' => 'like_users',
			'alterQuery' => 'mediumblob not null'
		);
		/* END 2.1.1 */

		/* 2.1.2 */
		$avatarDate = $db->fetchOne('SHOW COLUMNS FROM `xf_team` LIKE \'avatar_date\'');
		if (!empty($avatarDate))
		{
			$db->query('ALTER TABLE `xf_team` CHANGE `avatar_date` `team_avatar_date` int unsigned not null default \'0\'');
		}

		/* 2.1.4 versionID 167 */
		$alters[] = array(
			'table' => 'xf_team_event',
			'field' => 'likes',
			'alterQuery' => 'int(10) unsigned not null default \'0\''
		);
		$alters[] = array(
			'table' => 'xf_team_event',
			'field' => 'like_users',
			'alterQuery' => 'blob not null'
		);

		// 2.2.2
		$alters[] = array(
			'table' => 'xf_team',
			'field' => 'view_count',
			'alterQuery' => 'int(10) unsigned not null default 0 AFTER member_count'
		);
		$alters[] = array(
			'table' => 'xf_team_privacy',
			'field' => 'rules',
			'alterQuery' => 'mediumtext not null'
		);
		$alters[] = array(
			'table' => 'xf_team_privacy',
			'field' => 'last_update_rule',
			'alterQuery' => 'int(10) unsigned not null default \'0\''
		);
		$alters[] = array(
			'table' => 'xf_team_privacy',
			'field' => 'last_update_user_id',
			'alterQuery' => 'int(10) unsigned not null default \'0\''
		);
		$alters[] = array(
			'table' => 'xf_team_profile',
			'field' => 'invite_count',
			'alterQuery' => 'int(10) unsigned not null default 0 AFTER member_request_count'
		);

		// 2.2.3
		$alters[] = array(
			'table' => 'xf_team_profile',
			'field' => 'remove_inactive_date',
			'alterQuery' => 'int(10) unsigned not null default 0'
		);
		$alters[] = array(
			'table' => 'xf_team_member',
			'field' => 'last_reminder_date',
			'alterQuery' => 'int(10) unsigned not null default 0'
		);

		// 2.2.4



		foreach($alters as $alter) {
			try
			{
				$db->query('ALTER TABLE `' . $alter['table'] . '` ADD COLUMN `' . $alter['field'] . '` ' . $alter['alterQuery']);
			}
			catch(Zend_Db_Exception $e) {}
		}

		Nobita_Teams_Container::getModel('XenForo_Model_ContentType')->rebuildContentTypeCache();
	}

	private static function uninstallCustomized() {
		$db = XenForo_Application::get('db');

		$contentTypes = array(
			'team',
			'team_member',
			'team_post',
			'team_comment',
			'team_event',
		);
		$contentTypesQuoted = $db->quote($contentTypes);

		XenForo_Db::beginTransaction($db);

		$contentTypeTables = array(
			'xf_attachment',
			'xf_content_type',
			'xf_content_type_field',
			'xf_deletion_log',
			'xf_liked_content',
			'xf_moderation_queue',
			'xf_moderator_log',
			'xf_news_feed',
			'xf_report',
			'xf_user_alert',
			'xf_search_index',
		);

		foreach ($contentTypeTables as $table)
		{
			$db->delete($table, 'content_type IN (' . $contentTypesQuoted . ')');
		}

		// let these be cleaned up over time
		$db->update('xf_attachment', array('unassociated' => 1), 'content_type IN (' . $contentTypesQuoted . ')');

		// remove key from data registry
		$db->delete('xf_data_registry', 'data_key = ' . $db->quote('groupThreadsWidget'));
		$db->delete('xf_data_registry', 'data_key = ' . $db->quote(Nobita_Teams_Listener::DATA_REG_THREADS));

		XenForo_Application::setSimpleCacheData(TEAM_DATAREGISTRY_KEY, false);
		XenForo_Application::setSimpleCacheData('groupsLastCached', false);
		XenForo_Application::setSimpleCacheData('gRecentThreads', false);
		XenForo_Application::setSimpleCacheData('gTrendingThreads', false);

		XenForo_Db::commit($db);

		Nobita_Teams_Container::getModel('XenForo_Model_ContentType')->rebuildContentTypeCache();

		Nobita_Teams_sonnb_XenGallery_Installer::uninstall();
		Nobita_Teams_XenGallery_Installer::uninstall($db);
	}
}
