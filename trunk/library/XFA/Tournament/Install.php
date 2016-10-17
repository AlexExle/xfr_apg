<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Install
{
	public static function install($previous)
	{
		$db = XenForo_Application::getDb();

        $tables = self::getTables();
        $data   = self::getData();
        $alters = self::getAlters();

        if (!$previous) /* Fresh install */
        {
            /* Create all the tables */
			foreach ($tables AS $tableSql)
			{
				try
				{
					$db->query($tableSql);
				}
				catch (Zend_Db_Exception $e) {}
			}
			
            /* Execute alters */
			foreach ($alters AS $alterSql)
			{
				try
				{
					$db->query($alterSql);
				}
				catch (Zend_Db_Exception $e) {}
			}
			
			/* Insert all the commands data */
			foreach ($data AS $dataSql)
			{
				try
				{
					$db->query($dataSql);
				}
				catch (Zend_Db_Exception $e) {}
			}
        }	
        else
        {
            /* Update from 1.0.0 to 2.0.0 */
            if ($previous['version_id'] < 902000090)
            {
    			try
    			{
    				$db->query("
    				    ALTER TABLE xfa_tourn_tournament
    				    ADD `type` ENUM('single_el','double_el','round_robin') NOT NULL DEFAULT 'single_el',
    				    ADD `third_place` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                        ADD `winner_id` int(10) unsigned not null,
                        ADD `winner_username` varchar(100) not null default ''
    				");
    			}
    			catch (Zend_Db_Exception $e) {}  
			
                /* Empty bracket table */
    			try
    			{
    				$db->query("
    				    TRUNCATE xfa_tourn_bracket
    				");
    			}
    			catch (Zend_Db_Exception $e) {}                 
			
                /* Execute alters */
        		foreach ($alters AS $alterSql)
        		{
        			try
        			{
        				$db->query($alterSql);
        			}
        			catch (Zend_Db_Exception $e) {}
        		}		

                /* Register defer to convert previous matches */
                XenForo_Application::defer('XFA_Tournament_Deferred_Upgrade_902010090_Bracket', array(), 'XFATournamentUpgrade902000090Bracket', true, XenForo_Application::$time + 5);
            }
            
            /* Update from 2.0.0 to 2.1.0 */
            if ($previous['version_id'] < 902010090)
            {
    			try
    			{
    				$db->query("
    				    ALTER TABLE xfa_tourn_tournament
    				    ADD `discussion_thread_id` int(10) unsigned not null,
    				    ADD `rules` text not null
    				");
    			}
    			catch (Zend_Db_Exception $e) {}  
    			
    			try
    			{
    				$db->query("
    				    ALTER TABLE xfa_tourn_category
                        ADD `thread_node_id` int unsigned not null default 0,
                        ADD `thread_prefix_id` int unsigned not null default 0
    				");
    			}
    			catch (Zend_Db_Exception $e) {}      			                
            }
            
            /* Update to 2.2.0 */
            if ($previous['version_id'] < 902020090)
            {
    			try
    			{
    				$db->query("ALTER TABLE xf_user ADD `xfa_tourn_new_alert` tinyint(1) unsigned not null default 0");
    			}
    			catch (Zend_Db_Exception $e) {}
            }            
        }
	}
	
	public static function uninstall()
	{
		$db = XenForo_Application::getDb();
		
        /* Delete all the tables */
		foreach (self::getTables() AS $tableName => $tableSql)
		{
			try
			{
				$db->query("DROP TABLE IF EXISTS `$tableName`");
			}
			catch (Zend_Db_Exception $e) {}
		}
		
        /* Delete alters */
		foreach (self::getUnalters() AS $alterSql)
		{
			try
			{
				$db->query($alterSql);
			}
			catch (Zend_Db_Exception $e) {}
		}		
	}
	
	public static function getTables()
	{
    	$tables = array();
    
		$tables['xfa_tourn_category'] = "
			CREATE TABLE IF NOT EXISTS `xfa_tourn_category` (
				`tournament_category_id` int(10) unsigned not null auto_increment,
				`category_title` varchar(100) not null,
				`category_description` text not null,
				`display_order` int(10) unsigned not null default '0',
				`tournament_count` int(10) unsigned not null default '0',
				`last_update` int(10) unsigned not null default '0',
				`last_tournament_title` varchar(100) not null default '',
				`last_tournament_id` int(10) unsigned not null default '0',
				`thread_node_id` int unsigned not null default 0,
				`thread_prefix_id` int unsigned not null default 0,
				PRIMARY KEY  (`tournament_category_id`)
			) ENGINE=InnoDB  default CHARSET=utf8
		";  
		
		$tables['xfa_tourn_tournament'] = "
			CREATE TABLE IF NOT EXISTS `xfa_tourn_tournament` (
			  `tournament_id` int(10) unsigned not null AUTO_INCREMENT,
			  `tournament_category_id` int(10) unsigned not null,
			  `title` varchar(100) not null default '',
			  `type` ENUM('single_el','double_el','round_robin') NOT NULL DEFAULT 'single_el',
			  `third_place` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			  `private` tinyint(1) unsigned not null default '0',
			  `automatic_generation` tinyint(1) unsigned not null default '0',
              `description` text not null,
              `rules` text not null,
			  `user_id` int(10) unsigned not null,
			  `username` varchar(100) not null default '',
			  `slots` int(10) unsigned not null default '0',
              `user_count` int(10) unsigned not null default '0',
			  `creation_date` int unsigned not null default 0,
			  `end_date` int unsigned not null default 0,
			  `last_update` int unsigned not null default 0,
			  `invites` mediumtext not null,
              `winner_id` int(10) unsigned not null,
              `winner_username` varchar(100) not null default '',
              `discussion_thread_id` int(10) unsigned not null,
			  PRIMARY KEY (`tournament_id`),
			  KEY `category` (`tournament_category_id`)
			) ENGINE=InnoDB  default CHARSET=utf8
		";		
		
		$tables['xfa_tourn_participant'] = "
			CREATE TABLE IF NOT EXISTS `xfa_tourn_participant` (
			  `participant_id` int(10) unsigned not null AUTO_INCREMENT,
			  `tournament_id` int(10) unsigned not null,
			  `user_id` int(10) unsigned not null,
			  `username` varchar(100) not null default '',
			  PRIMARY KEY (`participant_id`),
			  KEY `tournament` (`tournament_id`)
			) ENGINE=InnoDB  default CHARSET=utf8
		";
		
		$tables['xfa_tourn_bracket'] = "
			CREATE TABLE IF NOT EXISTS `xfa_tourn_bracket` (
			  `bracket_id` int(10) unsigned not null AUTO_INCREMENT,
			  `tournament_id` int(10) unsigned not null,
			  `bracket` mediumtext not null,
			  PRIMARY KEY (`bracket_id`),
			  KEY `tournament` (`tournament_id`)
			) ENGINE=InnoDB default CHARSET=utf8
		";			
		
		return $tables;
	}
	
	public static function getData()
	{
    	$data = array();
    	  
		$data['xf_content_type'] = "
			INSERT IGNORE INTO xf_content_type
				(content_type, addon_id, fields)
			VALUES
				('xfa_tourn', 'xfa_tourn', '')
		";

		$data['xf_content_type_field'] = "
			INSERT IGNORE INTO xf_content_type_field
				(content_type, field_name, field_value)
			VALUES
				('xfa_tourn', 'alert_handler_class', 'XFA_Tournament_AlertHandler_Tournament')
		";    	  
    	    	
		$data['xfa_tourn_category'] = "
		    INSERT INTO `xfa_tourn_category` (`tournament_category_id`, `category_title`, `category_description`, `display_order`, `tournament_count`, `last_update`, `last_tournament_title`, `last_tournament_id`) VALUES
(1, 'Example Category', 'A default category added upon install', 0, 0, 0, '', 0)
		";
		
    	return $data;
	}
	
	public static function getAlters()
	{
		$alters = array();

		$alters['xf_user'] = "
		    ALTER TABLE xf_user
		    ADD `xfa_tourn_wins` int(10) unsigned not null default 0,
		    ADD `xfa_tourn_new_alert` tinyint(1) unsigned not null default 0
        ";

		return $alters;
	}	
	
	public static function getUnalters()
	{
		$alters = array();

		$alters['xf_user'] = "ALTER TABLE xf_user DROP `xfa_tourn_wins`,DROP `xfa_tourn_new_alert`";

		return $alters;
	}	
}
