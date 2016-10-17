<?php

require __DIR__.'/bootstrap.php';

abstract class Importable extends Printable
{
	protected $_db;

	public function __construct()
	{
		$this->_db = XenForo_Application::getDb();
	}

	abstract public function import();

	public function truncateTable($tableName)
	{
		$this->_db->query("TRUNCATE TABLE $tableName");
	}

	public function runQuery($tableName, $truncate = false)
	{
		if($truncate) {
			$this->truncateTable($tableName);
		}

		$dbConfig = XenForo_Application::getConfig()->db;

		$loginMysql = sprintf('mysql --local-infile -h%s -u%s -p%s %s -N -q -e',
			$dbConfig->host, $dbConfig->username, $dbConfig->password, $dbConfig->dbname
		);

		$charset = 'utf8';
		$localFile = $this->getExportDataPath($tableName);

		$runCommand = sprintf('
			%s "SET NAMES %s;
				LOAD DATA LOCAL INFILE \'%s\'
				IGNORE INTO TABLE %s
				CHARACTER SET %s
				FIELDS TERMINATED BY \',\' ENCLOSED BY \'\\0\'
				LINES TERMINATED BY \'\\r\\n\';"
		', 
			$loginMysql, $charset, $localFile, $tableName, $charset
		);

		passthru($runCommand);
	}

	public function getExportDataPath($tableName)
	{
		$path = TEMP_DIR . '/' . $tableName . '.txt';

		if(!file_exists($path)) {
			$this->info("The file $path could not be found.");
			die;
		}

		return realpath($path);
	}

	public function copyPaste($source, $destination)
	{
		$dir = opendir($source);
		XenForo_Helper_File::createDirectory($destination, true);

		while(false !== ($file = readdir($dir)))
		{
			if(($file != '.') && ($file != '..'))
			{
				if(is_dir($source . '/' . $file))
				{
					$this->copyPaste($source . '/' . $file, $destination . '/' . $file);
				}
				else
				{
					copy($source . '/' . $file, $destination . '/' . $file);
				}
			}
		}

		closedir($dir);
	}
}

class Category extends Importable
{
	public function import()
	{
		$start = microtime(true);
		$this->info('Begin importing categories.');

		// Try to truncate table
		$this->runQuery('xf_team_category', true);

		$timing = microtime(true) - $start;
		$this->info('Completed import categories. Timing: '.number_format($timing, 2).' seconds');
	}
}

class Group extends Importable
{
	public function import()
	{
		$start = microtime(true);
		$this->info('Begin importing groups.');

		// Try to truncate table
		$this->runQuery('xf_team', true);
		$this->runQuery('xf_team_privacy', true);
		$this->runQuery('xf_team_profile', true);

		$externalPath = XenForo_Helper_File::getExternalDataPath();
		$this->copyPaste(TEMP_DIR.'/logos', $externalPath.'/testcopied');

		$timing = microtime(true) - $start;
		$this->info('Completed import groups. Timing: '.number_format($timing, 2).' seconds');
	}
}

class Member extends Importable
{
	public function import()
	{
		$start = microtime(true);
		$this->info('Begin importing group members.');

		// Try to truncate table
		$this->runQuery('xf_team_member', true);

		$timing = microtime(true) - $start;
		$this->info('Completed import group members. Timing: '.number_format($timing, 2).' seconds');
	}
}

class ThreadAndPost extends Importable
{
	public function import()
	{
		$start = microtime(true);
		$this->info('Begin importing threads & posts.');

		// Try to truncate table
		$this->runQuery('xf_thread', false);
		$this->runQuery('xf_post', false);

		$db = $this->_db;
		$discussionId = 0;

		while($threads = $db->fetchAll('
			SELECT thread_id, vb_discussion_id, team_id
			FROM xf_thread
			WHERE vb_discussion_id > ?
			ORDER BY vb_discussion_id
			LIMIT 2000
		', array($discussionId))
		)
		{
			$lastThread = end($threads);
			$discussionId = $lastThread['vb_discussion_id'];

			$nodeMap = array();

			foreach($threads as $thread)
			{
				$teamId = $thread['team_id'];
				if(empty($teamId))
				{
					continue;
				}

				if(!isset($nodeMap[$thread['team_id']]))
				{
					$nodeId = $db->fetchOne('SELECT node_id FROM xf_forum WHERE team_id = ?', $teamId);
					if(empty($nodeId))
					{
						$dw = XenForo_DataWriter::create('XenForo_DataWriter_Forum');
						$dw->bulkSet(array(
							'title' => 'Archive Forums',
							'node_type_id' => Nobita_Teams_Listener::NODE_TYPE_ID,
						));

						$dw->save();
						$nodeId = $dw->get('node_id');
						$db->update('xf_forum', array('team_id' => $teamId), 'node_id = '.$db->quote($nodeId));
					}

					$nodeMap[$thread['team_id']] = $nodeId;
				}

				$nodeId = $nodeMap[$thread['team_id']];

				$db->query('
					UPDATE xf_thread
					SET node_id = ?
					WHERE thread_id = ?
				', array($nodeId, $thread['thread_id']));

				$postIds = $db->fetchCol('
					SELECT post_id
					FROM xf_post
					WHERE vb_discussion_id = ?
				', array($thread['vb_discussion_id']));

				if($postIds)
				{
						$db->query('
						UPDATE xf_post
						SET thread_id = ?
						WHERE post_id IN ('. $db->quote($postIds) .')
					', array($thread['thread_id']));
				}
			}
		}

		$timing = microtime(true) - $start;
		
		$this->info('Completed import threads & posts. Timing: '.number_format($timing, 2).' seconds');
		$this->info('Please run query to drop column vb_discussion_id in the table xf_thread & xf_post');
	}
}

class MediaAndComment extends Importable
{
	public function import()
	{
		$start = microtime(true);
		$this->info('Begin importing media & comments');

		$this->runQuery('xengallery_media', false);
		$this->runQuery('xengallery_comment', false);

		$db = $this->_db;
		$mediaList = $db->fetchAll('
			SELECT media_id, vb_picture_id, user_id, media_title, media_date
			FROM xengallery_media
			WHERE vb_picture_id > \'0\'
		');

		foreach($mediaList as $media)
		{
			$tempFile = TEMP_DIR.'/xengallery/'.$media['vb_picture_id'].'.temp';
			if(!file_exists($tempFile))
			{
				continue;
			}

			$imageinfo = getimagesize($tempFile);
			$filename = '';
			if($imageinfo[2] == IMAGETYPE_PNG) {
				$filename = $media['media_title'] . '.png';
			} elseif($imageinfo[2] == IMAGETYPE_JPEG) {
				$filename = $media['media_title'] . '.jpg';
			}
			else if($imageinfo[2] == IMAGETYPE_GIF) {
				$filename = $media['media_title'] . '.gif';
			}

			if(empty($filename)) {
				continue;
			}

			$attachmentDataDw = XenForo_DataWriter::create('XenForo_DataWriter_AttachmentData');
			$attachmentDataDw->bulkSet(array(
				'user_id' => $media['user_id'],
				'upload_date' => $media['media_date'],
				'filename' => $filename,
				'width' => $imageinfo[0],
				'height' => $imageinfo[1],
			));

			$attachmentDataDw->setExtraData(XenForo_DataWriter_AttachmentData::DATA_FILE_DATA, file_get_contents($tempFile));
			$attachmentDataDw->save();

			$attachmentDw = XenForo_DataWriter::create('XenForo_DataWriter_Attachment');
			$attachmentDw->bulkSet(array(
				'content_type' => 'xengallery_media',
				'content_id' => $media['media_id'],
				'data_id' => $attachmentDataDw->get('data_id'),
				'attach_date' => $media['media_date']
			));

			$attachmentDw->save();

			$db->query('
				UPDATE xengallery_media
				SET attachment_id = ?,
					category_id = ?,
					media_privacy = \'category\'
				WHERE media_id = ?
			', array($attachmentDw->get('attachment_id'), XENGALLERY_MEDIA_CATEGORY_ID, $media['media_id']));
			
			$db->query('
				UPDATE xengallery_comment
				SET content_id = ?,
					content_type = ?
				WHERE vb_picture_id = ?
			', array(
				$media['media_id'], 'media', $media['vb_picture_id']
			));
		}

		$timing = microtime(true) - $start;
		$this->info('Completed import media & comments. Timing: '.number_format($timing, 2).' seconds');
		$this->info('Please run query to drop column vb_picture_id in table xengallery_media & xengallery_comment');
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

Select data to importing:

    \033[44m1: Categories\033[0m
    \033[44m2: Groups\033[0m
    \033[44m3: Members\033[0m
    \033[44m4: Threads & Posts\033[0m
    \033[44m5: Pictures & Comments\033[0m
    \033[44m6: Everything\033[0m
\n
EOT;

$stdin = fopen('php://stdin', 'r');
$choice = intval(fgetc($stdin));

$options = array(
	1 => 'Category',
	2 => 'Group',
	3 => 'Member',
	4 => 'ThreadAndPost',
	5 => 'MediaAndComment'
);

if($choice == 6)
{
	// Export all data
	foreach($options as $class) {
		$obj = new $class();
		$obj->import();
	}
}
else if(isset($options[$choice]))
{
	// Export for special data
	$class = $options[$choice];
	$obj = new $class();

	$obj->import();
}
else
{
	echo <<<EOT
Please select the valid option.\n\n
EOT;
exit;
}