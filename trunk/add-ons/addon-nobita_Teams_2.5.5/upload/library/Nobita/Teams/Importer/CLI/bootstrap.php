<?php

/**
 * The abosulute path to XenForo root directory
 *
 * @var string
 */
define('XENFORO_ROOT_DIR', '/Users/TruongLv/WebServer/xf15');

/**
 * The absolute path which store temporary data
 *
 * @var string
 */
define('TEMP_DIR', '/Users/TruongLv/WebServer/xf15/testcli');

/**
 * The table prefix used in vbulletin database
 * You might put the same value in the file includes/config.php
 *
 * $config['Database']['tableprefix']
 *
 * @var string
 */
define('TABLE_PREFIX', 'vb_');

/**
 * The absolute path which used store icon in vbulletin
 *
 * @var string
 */
define('ICON_PATH_DIR', '');

/**
 * The absolute path which used store pictures in vbulletin
 *
 * @var string
 */
define('PICTURE_PATH_DIR', '');

/**
 * The category which used store all social group pictures
 *
 * @var int
 */
define('XENGALLERY_MEDIA_CATEGORY_ID', 0);

/**
 * Define database constrants
 *
 * @var string
 */
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_DBNAME', 'truonglv_vb3x');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');


/**
 *
 *   DO NOT EDIT THE FOLLOWING CODE.
 *
 *   @license TruongLuu <truonglv@outlook.com>
 */

if (PHP_SAPI != 'cli')
{
	die('This script may only be run at the command line.');
}

if(!file_exists(XENFORO_ROOT_DIR.'/library/XenForo/Autoloader.php'))
{
	echo "\033[44mCould not find the XenForo Autoloader.\033[0m\n";
	exit;
}

if(!is_dir(TEMP_DIR) || !is_writable(TEMP_DIR))
{
	echo "\033[44mThe temporary directory not found or could not writable.\033[0m\n";
	exit;
}

if(!is_dir(ICON_PATH_DIR))
{
	echo "\033[42mWarning: The icon directory not found. Will be ignore export/import icon data.\033[0m\n\n";
}

require XENFORO_ROOT_DIR.'/library/XenForo/Autoloader.php';
XenForo_Autoloader::getInstance()->setupAutoloader(XENFORO_ROOT_DIR.'/library');
XenForo_Application::initialize(XENFORO_ROOT_DIR.'/library', XENFORO_ROOT_DIR);


abstract class Printable
{
	/**
	 * Print an infor message to screen
	 *
	 * @return void
	 */
	public function info($message)
	{
		echo "\r\033[42mInfo:\033[0m \033[36m {$message} \033[0m\n\n";
	}
}
