<?php
class Ice_EmbedStreams_Install{
	
	public static function install($existingAddOn, $addOnData){
		
		$db = XenForo_Application::get('db');
		
		$db->query("
			CREATE TABLE IF NOT EXISTS `xf_ice_livestreams` (
			  `stream_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `username` varchar(40) COLLATE ascii_bin NOT NULL,
			  `stream_type` int(5) unsigned,
			  `stream_username` varchar(40) COLLATE ascii_bin NOT NULL,
			  `display_order` int(10) unsigned NOT NULL,
			  `live` tinyint(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`stream_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=ascii COLLATE=ascii_bin;
		");
		
		if($addOnData['version_id'] <= 15){
			print_r("derp");
			$db->query("ALTER TABLE xf_ice_livestreams DROP stream_type");
			$db->query("ALTER TABLE xf_ice_livestreams ADD stream_type tinyint(1) unsigned;");
		}
	}
	
	public static function uninstall(){
		
		$db = XenForo_Application::get('db');
		
		$db->query("
			DROP TABLE `xf_ice_livestreams`;
		");
		
	}
	
}