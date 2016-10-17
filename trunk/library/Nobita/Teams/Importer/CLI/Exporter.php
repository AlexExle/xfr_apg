<?php

require __DIR__.'/bootstrap.php';

if(!function_exists('dd')) {
	function dd() {
		array_map(function($var) {
			Zend_Debug::dump($var);
		}, func_get_args());

		die;
	};
}

/**
 *
 *   DO NOT EDIT THE FOLLOWING CODE.
 *
 *   @license TruongLuu <truonglv@outlook.com>
 */

abstract class ExportBase extends Printable
{
	/**
	 * XenForo Database Source
	 */
	protected $_db;

	/**
	 * Vbulletin Database Source
	 */
	protected $_sourceDb;

	/**
	 * Vbulletin Table Prefix
	 */
	protected $_prefix;

	public function __construct()
	{
		$this->_db = XenForo_Application::getDb();
		$this->test();

		$this->_prefix = TABLE_PREFIX;
	}

	abstract public function run();

	public function test()
	{
		$this->_sourceDb = Zend_Db::factory('mysqli', array(
			'host' 		=> DB_HOST,
			'port' 		=> DB_PORT,
			'dbname' 	=> DB_DBNAME,
			'username' 	=> DB_USERNAME,
			'password' 	=> DB_PASSWORD,
			'charset' 	=> 'utf8',
			'adapterNamespace' => 'Zend_Db_Adapter',
		));
		$this->_sourceDb->getConnection();
	}

	public function progressingBar($currentProgress, $total)
	{
		if($currentProgress > $total) {
			return;
		}

		$barSize = 60;

		$progressing = ($currentProgress / $total);
		$bar = floor($progressing * $barSize);

		$status = "\r";
		$status .= str_repeat("\033[45m \033[0m", $bar);

		if($bar < $barSize) {
			$status .= ">";
			$status .= str_repeat(' ', $barSize - $bar);
		} else {
			$status .= "\033[45m\033[0m";
		}

		$output = number_format($progressing*100, 0);
		$status .= " $output% $currentProgress/$total";
		
		echo $status;

		flush();

		if($currentProgress == $total)  {
			echo "\n\n";
		}
	}

	protected function _safeUtf8String($string, $length = false)
	{
		$entities = true;
		if (preg_match('/[\x80-\xff]/', $string))
		{
			$newString = false;
			if (function_exists('iconv'))
			{
				$newString = @iconv($this->_charset, 'utf-8//IGNORE', $string);
			}
			if (!$newString && function_exists('mb_convert_encoding'))
			{
				$newString = @mb_convert_encoding($string, 'utf-8', $this->_charset);
			}
			$string = ($newString ? $newString : preg_replace('/[\x80-\xff]/', '', $string));
		}

		$string = utf8_unhtml($string, $entities);
		$string = preg_replace('/[\xF0-\xF7].../', '', $string);
		$string = preg_replace('/[\xF8-\xFB]..../', '', $string);

		if($length) 
		{
			$string = substr($string, 0, $length);
		}

		return $string;
	}

	protected function _getFields($tableName)
	{
		$structure = $this->_db->describeTable($tableName);
		$fields = array();

		foreach($structure as $schema)
		{
			$fields[$schema['COLUMN_NAME']] = $this->_castValueToType($schema['DATA_TYPE'], $schema['DEFAULT']);
		}
		
		return $fields;
	}

	protected function _castValueToType($dataType, $value)
	{
		$dataType = preg_replace('/[^a-z]/', '', $dataType);
		if(substr($dataType, 0, 4) == 'enum') {
			$dataType = 'enum';
		}

		switch($dataType)
		{
			case 'int':
			case 'smallint':
			case 'tinyint':
				return intval($value); break;
			case 'float':
			case 'floatunsigned':
				return floatval($value); break;
			case 'text':
			case 'varchar':
			case 'mediumtext':
			case 'varbinary':
			case 'binary':
			case 'blob':
			case 'mediumblob':
			case 'enum':
				return trim(strval($value)); break;
			default:
				throw new InvalidArgumentException("Unknown data type: $dataType\n");break;
		}
	}

	protected function _getOutputLine(array $data)
	{
		$db = $this->_db;
		$data = array_map(function($value) use($db) {
			$quoted = $db->quote($value);
			$enclosed = "\0";

			if(substr($quoted, 0, 1) == "'") {
				$quoted = substr($quoted, 1);
			}

			if(substr($quoted, -1, 1) == "'") {
				$quoted = substr($quoted, 0, strlen($quoted) - 1);
			}

			return $enclosed . $quoted . $enclosed;
		}, $data);

		return implode(",", $data)."\r\n";
	}
}

class Category extends ExportBase
{
	public function run()
	{
		$start = microtime(true);
		$this->info('Begin exporting categories data.');

		$sourceDb = $this->_sourceDb;

		$total = $sourceDb->fetchOne("SELECT COUNT(*) FROM {$this->_prefix}socialgroupcategory");

		if(empty($total)) {
			$this->info('No categories to exporting.');
			exit;
		}

		$categories = $sourceDb->fetchAll("SELECT * FROM {$this->_prefix}socialgroupcategory");
		$progress = 1;

		$fields = $this->_getFields('xf_team_category');

		$saveAs = TEMP_DIR.'/xf_team_category.txt';
		$fp = fopen($saveAs, 'w+');

		foreach($categories as $category) {
			$this->progressingBar($progress, $total);

			$mapData = array_merge($fields, array(
				'team_category_id' => $category['socialgroupcategoryid'],
				'category_title' => $this->_safeUtf8String($category['title'], 100),
				'category_description' => $this->_safeUtf8String($category['description']),
				'display_order' => $category['displayorder'],
				'team_count' => $category['groups'],
			));

			fwrite($fp, $this->_getOutputLine(array_values($mapData)));
			$progress++;
		}

		fclose($fp);

		$timing = microtime(true) - $start;
		$this->info("Completed export categories. Timing: ".number_format($timing, 2)." seconds");
	}
}

class Group extends ExportBase
{
	public function run()
	{
		$start = microtime(true);
		$this->info('Begin exporting groups data.');

		$sourceDb = $this->_sourceDb;
		$total = $sourceDb->fetchOne("SELECT COUNT(*) FROM {$this->_prefix}socialgroup");

		if(empty($total)) {
			$this->info('No groups to exporting.');
			exit;
		}

		$limit = 5000;
		$current = 0;
		$progress = 1;

		$handlers = array(
			'xf_team' => fopen(TEMP_DIR.'/xf_team.txt', 'w+'),
			'xf_team_profile' => fopen(TEMP_DIR.'/xf_team_profile.txt', 'w+'),
			'xf_team_privacy' => fopen(TEMP_DIR.'/xf_team_privacy.txt', 'w+'),
		);

		$fields = array(
			'xf_team' => $this->_getFields('xf_team'),
			'xf_team_profile' => $this->_getFields('xf_team_profile'),
			'xf_team_privacy' => $this->_getFields('xf_team_privacy')
		);

		XenForo_Helper_File::createDirectory(TEMP_DIR.'/logos');

		while($groups = $sourceDb->fetchAll('
			SELECT `group`.*, `user`.username, `icon`.dateline AS logo_date, `icon`.filedata,
				IF(`group`.type = "public", "open", IF(`group`.type = "moderated", "closed", "secret")) AS privacy_state
			FROM '. $this->_prefix .'socialgroup AS `group`
				LEFT JOIN '. $this->_prefix .'user AS `user` ON (`user`.userid = `group`.creatoruserid)
				LEFT JOIN '. $this->_prefix .'socialgroupicon AS `icon` ON (`icon`.groupid = `group`.groupid)
			ORDER BY `group`.groupid
			LIMIT '. ($current * 5000) .', '. $limit .'
		')) 
		{
			foreach($groups as $group) {
				$this->progressingBar($progress, $total);

				$mapData = array(
					'xf_team_privacy' => array_merge($fields['xf_team_privacy'], array(
						'team_id' => $group['groupid']
					)),
					'xf_team_profile' => array_merge($fields['xf_team_profile'], array(
						'team_id' => $group['groupid'],
						'about' => $this->_safeUtf8String($group['description'])
					)),
					'xf_team' => array_merge($fields['xf_team'], array(
						'team_id' => $group['groupid'],
						'title' => $this->_safeUtf8String($group['name'], 100),
						'user_id' => $group['creatoruserid'],
						'username' => $this->_safeUtf8String($group['username'], 50),
						'team_date' => $group['dateline'],
						'team_category_id' => $group['socialgroupcategoryid'],
						'last_updated' => $group['lastupdate'],
						'team_state' => 'visible',
						'team_avatar_date' => $group['logo_date'],
						'privacy_state' => $group['privacy_state'],
					)),
				);

				if($group['logo_date']) {
					// Move Vbulletin group logo to temporary directory
					$data = false;
					if($group['filedata']) {
						$data = $group['filedata'];
					} else {
						$iconPath = sprintf('%s/socialgroupicon_%d_%d.gif', ICON_PATH_DIR, $group['groupid'], $group['logo_date']);
						$data = @file_get_contents($iconPath);
					}

					if($data) {
						$logoModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Logo');

						$outputFile = $logoModel->getAvatarFilePath($group['groupid'], TEMP_DIR.'/logos');
						$directory = dirname($outputFile);
						XenForo_Helper_File::createDirectory($directory);

						file_put_contents($outputFile, $data);
					} else {
						$mapData['xf_team']['team_avatar_date'] = 0;
					}
				}
	
				foreach($mapData as $mapKey => $data) {
					fwrite($handlers[$mapKey], $this->_getOutputLine(array_values($data)));
				}

				$progress++;
			}
			
			$current++;
		}

		foreach($handlers as $handler) {
			fclose($handler);
		}

		$timing = microtime(true) - $start;
		$this->info('Completed export groups data. Timing: ' . number_format($timing, 2) .' seconds');
	}
}

class Member extends ExportBase
{
	public function run()
	{
		$start = microtime(true);
		$this->info('Begin exporting members data.');
		$sourceDb = $this->_sourceDb;

		$total = $sourceDb->fetchOne("SELECT COUNT(*) FROM {$this->_prefix}socialgroupmember");
		if(empty($total)) {
			$this->info('No members to exporting.');
			exit;
		}

		$handler = fopen(TEMP_DIR.'/xf_team_member.txt', 'w+');
		$fields = $this->_getFields('xf_team_member');

		$date = 0;
		$progress = 1;

		while($members = $sourceDb->fetchAll('
			SELECT `member`.*, `user`.username, IF(`member`.type = "member", "accept", "request") AS member_state
			FROM '. $this->_prefix .'socialgroupmember AS `member`
				LEFT JOIN '. $this->_prefix .'user AS `user` ON (`user`.userid = `member`.userid)
			WHERE `member`.dateline > ?
			ORDER BY `member`.dateline
			LIMIT 5000
		', array($date)))
		{
			$last = end($members);
			$date = $last['dateline'];

			foreach($members as $member) 
			{
				$this->progressingBar($progress, $total);

				$mapData = array_merge($fields, array(
					'team_id' => $member['groupid'],
					'join_date' => $member['dateline'],
					'member_state' => $member['member_state'],
					'username' => $this->_safeUtf8String($member['username'], 50),
					'user_id' => $member['userid'],
				));

				$group = $sourceDb->fetchRow("
					SELECT * 
					FROM {$this->_prefix}socialgroup
					WHERE creatoruserid = ? AND groupid = ?
				", array($member['userid'], $member['groupid']));

				$mapData['member_role_id'] = empty($group) ? 'member' : 'admin';

				fwrite($handler, $this->_getOutputLine(array_values($mapData)));
				$progress++;
			}
		}

		fclose($handler);

		$timing = microtime(true) - $start;
		$this->info('Completed export members data. Timing: '.number_format($timing, 2) .' seconds');
	}
}

class Thread extends ExportBase
{
	public function run()
	{
		$start = microtime(true);
		$this->info('Begin exporting threads data.');

		$sourceDb = $this->_sourceDb;
		$total = $sourceDb->fetchOne("SELECT COUNT(*) FROM {$this->_prefix}discussion WHERE groupid > 0");
		if(empty($total)) {
			$this->info('No threads to exporting.');
			exit;
		}

		try
		{
			$this->_db->query("ALTER TABLE xf_thread ADD COLUMN vb_discussion_id int unsigned not null default '0',
				ADD INDEX vb_discussion_id (vb_discussion_id)");
		}
		catch(Zend_Db_Exception $e) {}

		$fields = $this->_getFields('xf_thread');

		if(!isset($fields['vb_discussion_id']))
		{
			$this->info('Missing field vb_discussion_id in table xf_thread.');
			exit;
		}

		$handler = fopen(TEMP_DIR.'/xf_thread.txt', 'w+');

		$limit = 5000;
		$current = 0;
		$progress = 1;

		while($threads = $sourceDb->fetchAll('
			SELECT `thread`.*, `post`.*
			FROM '. $this->_prefix .'discussion AS `thread`
				LEFT JOIN '. $this->_prefix .'groupmessage AS `post` ON (`post`.gmid = `thread`.firstpostid)
			WHERE `thread`.groupid > 0
			ORDER BY `thread`.discussionid
			LIMIT '. ($current*$limit) .', '. $limit .'
		'))
		{
			foreach($threads as $thread)
			{
				$this->progressingBar($progress, $total);

				$mapData = array_merge($fields, array(
					'title' => $this->_safeUtf8String($thread['title'], 150),
					'user_id' => $thread['postuserid'],
					'username' => $this->_safeUtf8String($thread['postusername'], 50),
					'post_date' => $thread['dateline'],
					'discussion_state' => ($thread['state'] == 'moderation') ? 'moderated' : $thread['state'],
					'first_post_id' => $thread['firstpostid'],
					'last_post_id' => $thread['lastpost'],
					'last_post_user_id' => $thread['lastposterid'],
					'last_post_username' => $this->_safeUtf8String($thread['lastposter'], 50),
					'team_id' => $thread['groupid'],
					'discussion_type' => 'team',
					'vb_discussion_id' => $thread['discussionid'],
				));

				$viewCount = $sourceDb->fetchOne("
					SELECT COUNT(*) 
					FROM {$this->_prefix}groupmessage
					WHERE discussionid = ?
				", array($thread['discussionid']));

				$mapData['view_count'] = $viewCount;
				$mapData['reply_count'] = max(0, $viewCount - 1);

				fwrite($handler, $this->_getOutputLine(array_values($mapData)));

				$progress++;
			}
			$current++;
		}

		$timing = microtime(true) - $start;
		$this->info('Completed exporting threads data. Timing: '.number_format($timing, 2).' seconds');
	}
}

class Post extends ExportBase
{
	public function run()
	{
		$start = microtime(true);
		$this->info("Begin exporting posts data.");

		$sourceDb = $this->_sourceDb;
		$total = $sourceDb->fetchOne("
			SELECT COUNT(*) 
			FROM {$this->_prefix}groupmessage
		");

		if(empty($total)) {
			$this->info('No posts to exporting.');
			exit;
		}

		$current = 0;
		$limit = 5000;
		$progress = 1;

		try
		{
			$this->_db->query("ALTER TABLE xf_post ADD COLUMN vb_discussion_id int unsigned not null default '0',
				ADD INDEX vb_discussion_id (vb_discussion_id)");
		}
		catch(Zend_Db_Exception $e){}
		

		$fields = $this->_getFields('xf_post');

		if(!isset($fields['vb_discussion_id']))
		{
			$this->info('Missing field vb_discussion_id in the table xf_post.');
			exit;
		}

		$handler = fopen(TEMP_DIR.'/xf_post.txt', 'w+');
		// unset($fields['post_id']);

		while($posts = $sourceDb->fetchAll('
			SELECT `post`.*,IF(`user`.username IS NULL, `post`.postusername, `user`.username) AS username
			FROM '. $this->_prefix .'groupmessage AS `post`
				LEFT JOIN '. $this->_prefix .'user AS `user` ON (`user`.userid = `post`.postuserid)
			ORDER BY `post`.gmid
			LIMIT '. ($current * $limit) .', '. $limit .'
		'))
		{
			$position = 0;
			foreach($posts as $post)
			{
				$this->progressingBar($progress, $total);

				$mapData = array_merge($fields, array(
					'thread_id' => 0,
					'user_id' => $post['postuserid'],
					'username' => $this->_safeUtf8String($post['username'], 50),
					'post_date' => $post['dateline'],
					'message_state' => ($post['state'] == 'moderation') ? 'moderated' : $post['state'],
					'position' => $position,
					'message' => $this->_safeUtf8String($post['pagetext']),
					'vb_discussion_id' => $post['discussionid']
				));

				$position++;
				$progress++;

				fwrite($handler, $this->_getOutputLine(array_values($mapData)));
			}

			$current++;
		}

		fclose($handler);

		$timing = microtime(true) - $start;
		$this->info('Completed exporting posts data. Timing: '.number_format($timing, 2).' seconds');
	}
}

class Media extends ExportBase
{
	public function run()
	{
		$start = microtime(true);
		$this->info('Begin exporting group pictures data.');

		$pingStatus = null;
		$sourceDb = $this->_sourceDb;

		try
		{
			$pingStatus = $this->_db->fetchOne("SELECT 1 FROM xengallery_media");
		}
		catch(Zend_Db_Exception $e) {}

		if($pingStatus === null) {
			$this->info('XenForo Media Gallery not installed.');
			exit;
		}

		$total = $sourceDb->fetchOne("SELECT COUNT(*) FROM {$this->_prefix}socialgrouppicture");
		if(empty($total)) {
			$this->info('No pictures to exporting.');
			exit;
		}

		// Make sure we have the social column
		try
		{
			$this->_db->query("ALTER TABLE xengallery_media ADD COLUMN social_group_id int unsigned not null default 0");
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$this->_db->query("ALTER TABLE xengallery_media ADD INDEX social_group_id (social_group_id)");
		}
		catch(Zend_Db_Exception $e) {}

		try
		{
			$this->_db->query("ALTER TABLE xengallery_media ADD COLUMN vb_picture_id int unsigned not null default '0',
					ADD INDEX vb_picture_id (vb_picture_id)");
		}
		catch(Zend_Db_Exception $e){}
		try
		{
			$this->_db->query("ALTER TABLE xengallery_comment ADD COLUMN vb_picture_id int unsigned not null default '0',
					ADD INDEX vb_picture_id (vb_picture_id)");
		}
		catch(Zend_Db_Exception $e){}

		$fields = $this->_getFields('xengallery_media');
		$handler = fopen(TEMP_DIR.'/xengallery_media.txt', 'w+');

		if(!isset($fields['vb_picture_id']))
		{
			$this->info('Missing field vb_picture_id in the table xengallery_media');
			exit;
		}

		$current = 0;
		$progress = 1;
		$failed = 0;

		while($pictures = $sourceDb->fetchAll('
			SELECT `picture`.*, `album`.dateline, `group_picture`.groupid, `user`.username
			FROM '. $this->_prefix .'socialgrouppicture AS `group_picture`
				LEFT JOIN '. $this->_prefix .'picture AS `picture` ON (`picture`.pictureid = `group_picture`.pictureid)
				LEFT JOIN '. $this->_prefix .'user AS `user` ON (`user`.userid = `picture`.userid)
				LEFT JOIN '. $this->_prefix .'albumpicture AS `album` ON (`album`.pictureid = `picture`.pictureid)
			ORDER BY `picture`.pictureid
			LIMIT '. ($current * 5000) .', 5000
		'))
		{
			foreach($pictures as $picture)
			{
				$this->progressingBar($progress, $total);

				$mapData = array_merge($fields, array(
					'media_title' => $this->_safeUtf8String($picture['caption']),
					'media_date' => $picture['dateline'],
					'media_type' => 'image_upload',
					'media_state' => ($picture['state'] == 'moderation') ? 'moderated' : $picture['state'],
					'social_group_id' => $picture['groupid'],
					'user_id' => $picture['userid'],
					'username' => $picture['username'],
					'vb_picture_id' => $picture['pictureid'],
				));

				$stats = $sourceDb->fetchOne("
					SELECT MAX(dateline) AS last_comment_date, COUNT(*) AS total
					FROM {$this->_prefix}picturecomment
					WHERE pictureid = ?
				", array($picture['pictureid']));

				$mapData['last_comment_date'] = intval($stats['last_comment_date']);
				$mapData['comment_count'] = intval($stats['total']);
				$mapData['media_view_count'] = intval($stats['total']) + 1;

				$imageData = false;
				if($picture['filedata']) {
					$imageData = $picture['filedata'];
				} else {
					$path = sprintf('%s/%d/%d.picture', PICTURE_PATH_DIR, floor($picture['pictureid'] / 1000), $picture['pictureid']);
					$imageData = @file_get_contents($imageData);
				}

				$temp = TEMP_DIR.'/xengallery/'.$picture['pictureid'].'.temp';
				XenForo_Helper_File::createDirectory(dirname($temp));

				file_put_contents($temp, $imageData);

				$imageinfo = @getimagesize($temp);
				if(!is_array($imageinfo)) {
					// Picture error.
					$failed++;
					@unlink($temp);
				} else {
					fwrite($handler, $this->_getOutputLine(array_values($mapData)));
				}

				$progress++;
			}

			$current++;
		}

		fclose($handler);

		$timing = microtime(true) - $start;
		$this->info('Completed exporting pictures data. Failed pictures: ' . number_format($failed, 0). ' Timing: '.number_format($timing, 2) . ' seconds');
		
		$start = microtime(true);
		$this->info('Begin exporting picture comments data.');

		$total = $sourceDb->fetchOne("
			SELECT COUNT(*) 
			FROM {$this->_prefix}socialgrouppicture AS `group_picture`
				LEFT JOIN {$this->_prefix}picturecomment AS `comment` ON (
					`comment`.pictureid = `group_picture`.pictureid
				)
		");

		if(empty($total)) {
			$this->info('No comments to exporting.');
			exit;
		}

		$current = 0;
		$progress = 1;

		$handler = fopen(TEMP_DIR.'/xengallery_comment.txt', 'w+');
		$fields = $this->_getFields('xengallery_comment');

		if(!isset($fields['vb_picture_id']))
		{
			$this->info('Missing field vb_picture_id in the table xengallery_comment');
			exit;
		}

		while($comments = $sourceDb->fetchAll('
			SELECT `comment`.*, `picture`.pictureid
			FROM '. $this->_prefix .'socialgrouppicture AS `group_picture`
				LEFT JOIN '. $this->_prefix .'picturecomment AS `comment` ON (
					`comment`.pictureid = `group_picture`.pictureid
				)
				LEFT JOIN '. $this->_prefix .'picture AS `picture` ON (
					`picture`.pictureid = `group_picture`.pictureid
				)
			ORDER BY `comment`.commentid
			LIMIT '. ($current*5000) .',5000
		'))
		{
			foreach($comments as $comment)
			{
				$this->progressingBar($progress, $total);

				$commentData = array_merge($fields, array(
					// We will do later
					'content_id' => 0,
					'content_type' => 'media',
					'message' => $this->_safeUtf8String($comment['pagetext']),
					'user_id' => $comment['postuserid'],
					'username' => $this->_safeUtf8String($comment['postusername']),
					'comment_date' => $comment['dateline'],
					'comment_state' => ($comment['state'] == 'moderation') ? 'moderated' : $comment['state'],
					'vb_picture_id' => $comment['pictureid']
				));
				
				fwrite($handler, $this->_getOutputLine(array_values($commentData)));

				$progress++;
			}
			$current++;
		}

		fclose($handler);
		$timing = microtime(true) - $start;

		$this->info('Completed exporting comments. Timing: '.number_format($timing, 2) . ' seconds');
	}
}

echo <<<EOT

|---------------------------------------------------------------|
|---------------------------------------------------------------|
| \033[46m[Nobita] Social Groups: Export Data for Vbulletin 3.x or 4.x\033[0m  |
|                                                               |
| \033[32m@author:\033[0m          \033[35mTruonglv\033[0m                                    |
| \033[32m@email:\033[0m           \033[35mTruonglv@outlook.com\033[0m                        |
| \033[32m@home:\033[0m            \033[35mhttp://truongluu.com\033[0m                        |
| \033[32m@forum support:\033[0m   \033[35mhttp://nobita.me\033[0m                            |
|---------------------------------------------------------------|

Select data to exporting:

    \033[44m1: Categories\033[0m
    \033[44m2: Groups\033[0m
    \033[44m3: Members\033[0m
    \033[44m4: Threads\033[0m
    \033[44m5: Posts\033[0m
    \033[44m6: Pictures & Comments\033[0m
    \033[44m7: Everything\033[0m
\n
EOT;

$stdin = fopen('php://stdin', 'r');
$choice = intval(fgetc($stdin));

$options = array(
	1 => 'Category',
	2 => 'Group',
	3 => 'Member',
	4 => 'Thread',
	5 => 'Post',
	6 => 'Media'
);

if($choice == 7)
{
	// Export all data
	foreach($options as $class) {
		$obj = new $class();
		$obj->run();
	}
}
else if(isset($options[$choice]))
{
	// Export for special data
	$class = $options[$choice];
	$obj = new $class();

	$obj->run();
}
else
{
	echo <<<EOT
Please select the valid option.\n\n
EOT;
exit;
}





