<?php

class Nobita_Teams_Importer_vBulletin_vB38x extends Nobita_Teams_Importer_Abstract
{
	/**
	 * @var integer
	 */
	protected $_nodeId;

	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $_sourceDb;

	/**
	 * @var string
	 */
	protected $_prefix;

	protected $_charset = 'windows-1252';

	/**
	 * @var array
	 */
	protected $_config;

	public static function getName()
	{
		return '[Nobita] Social Groups: vBulletin Social Groups 3.8.x/4.2.x';
	}

	public function configure(XenForo_ControllerAdmin_Abstract $controller, array &$config)
	{
		$this->_checkIfRetainKeysEnabled($controller);

		if ($config)
		{
			if ($errors = $this->validateConfiguration($config))
			{
				return $controller->responseError($errors);
			}

			$this->_bootstrap($config);

			return true;
		}
		else
		{
			$configPath = getcwd() . '/includes/config.php';
			if (file_exists($configPath) && is_readable($configPath))
			{
				$config = array();
				include($configPath);

				$viewParams = array('input' => $config);
			}
			else
			{
				$viewParams = array('input' => array
				(
					'MasterServer' => array
					(
						'servername' => 'localhost',
						'port' => 3306,
						'username' => '',
						'password' => '',
					),
					'Database' => array
					(
						'dbname' => '',
						'tableprefix' => ''
					),
					'Mysqli' => array
					(
						'charset' => ''
					),
				));
			}

			$nodeOptions = XenForo_Option_NodeChooser::getNodeOptions(0, false, 'Forum');
			$viewParams += array(
				'nodeOptions' => $nodeOptions
			);

			return $controller->responseView('Nobita_Teams_ViewAdmin_Import_Config', 'Team_import_vbulletin_config', $viewParams);
		}
	}

	public function validateConfiguration(array &$config)
	{
		$errors = array();

		$config['db']['prefix'] = preg_replace('/[^a-z0-9_]/i', '', $config['db']['prefix']);

		try
		{
			$db = Zend_Db::factory('mysqli',
				array(
					'host' => $config['db']['host'],
					'port' => $config['db']['port'],
					'username' => $config['db']['username'],
					'password' => $config['db']['password'],
					'dbname' => $config['db']['dbname'],
					'charset' => $config['db']['charset']
				)
			);
			$db->getConnection();
		}
		catch (Zend_Db_Exception $e)
		{
			$errors[] = new XenForo_Phrase('source_database_connection_details_not_correct_x', array('error' => $e->getMessage()));
		}

		if ($errors)
		{
			return $errors;
		}

		try
		{
			$db->query('
				SELECT groupid
				FROM ' . $config['db']['prefix'] . 'socialgroup
				LIMIT 1
			');
		}
		catch (Zend_Db_Exception $e)
		{
			if ($config['db']['dbname'] === '')
			{
				$errors[] = new XenForo_Phrase('please_enter_database_name');
			}
			else
			{
				$errors[] = new XenForo_Phrase('table_prefix_or_database_name_is_not_correct');
			}
		}

		if (!empty($config['attachmentPath']))
		{
			if (!file_exists($config['attachmentPath']) || !is_dir($config['attachmentPath']))
			{
				$errors[] = new XenForo_Phrase('attachments_directory_not_found');
			}
		}

		$nodeId = isset($_POST['node_id']) ? $_POST['node_id'] : 0;
		if ($nodeId)
		{
			$forum = Nobita_Teams_Container::getModel('XenForo_Model_Forum')->getForumById($nodeId);
			if (!$forum)
			{
				$errors[] = new XenForo_Phrase('requested_forum_not_found');
			}

			if ($errors)
			{
				return $errors;
			}

			$config['node'] = array(
				'node_id' => $forum['node_id'],
				'title' => $forum['title']
			);
		}

		if (!$errors)
		{
			$defaultLanguageId = $db->fetchOne('
				SELECT value
				FROM ' . $config['db']['prefix'] . 'setting
				WHERE varname = \'languageid\'
			');
			$defaultCharset = $db->fetchOne('
				SELECT charset
				FROM ' . $config['db']['prefix'] . 'language
				WHERE languageid = ?
			', $defaultLanguageId);
			if (!$defaultCharset || str_replace('-', '', strtolower($defaultCharset)) == 'iso88591')
			{
				$config['charset'] = 'windows-1252';
			}
			else
			{
				$config['charset'] = strtolower($defaultCharset);
			}
		}

		return $errors;
	}

	protected function _bootstrap(array $config)
	{
		if ($this->_sourceDb)
		{
			return;
		}

		@set_time_limit(0);

		$this->_nodeId = isset($config['node']['node_id']) ? $config['node']['node_id'] : 0;
		$this->_config = $config;

		$this->_sourceDb = Zend_Db::factory('mysqli',
			array(
				'host' => $config['db']['host'],
				'port' => $config['db']['port'],
				'username' => $config['db']['username'],
				'password' => $config['db']['password'],
				'dbname' => $config['db']['dbname'],
				'charset' => $config['db']['charset']
			)
		);

		if (empty($config['db']['charset']))
		{
			$this->_sourceDb->query('SET character_set_results = NULL');
		}

		$this->_prefix = preg_replace('/[^a-z0-9_]/i', '', $config['db']['prefix']);
		if (!empty($config['charset']))
		{
			$this->_charset = $config['charset'];
		}
	}

	public function getSteps()
	{
		$steps = parent::getSteps();
		unset($steps['members']);

		$steps['groups']['title'] = new XenForo_Phrase('Teams_import_groups_and_members');

		$steps['threads'] = array(
			'title' => new XenForo_Phrase('Teams_import_threads'),
			'depends' => array('groups')
		);

		return $steps;
	}

	public function stepCategories($start, array $options)
	{
		$categories = $this->_sourceDb->fetchAll('
			SELECT *
			FROM ' . $this->_prefix . 'socialgroupcategory
		');

		$model = $this->_importModel;
		$total = 0;
		XenForo_Db::beginTransaction();

		foreach($categories as $category)
		{
			$categoryDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Category');

			$categoryDw->bulkSet(array(
				'category_title' => $this->_convertToUtf8($category['title']),
				'category_description' => $this->_convertToUtf8($category['description'], true),
				'display_order' => $category['displayorder'],
			));

			$categoryDw->save();
			$categoryId = $categoryDw->get('team_category_id');

			$model->logImportData('nsg_vb38x_category', $category['socialgroupcategoryid'], $categoryId);
			$total++;
		}

		XenForo_Db::commit();
		$this->_session->incrementStepImportTotal($total);

		return true;
	}

	public function stepGroups($start, array $options)
	{
		$options = array_merge(array(
				'limit' => 100,
				'max' => false
			), $options
		);

		$model = $this->_importModel;

		$categories = $model->getImportContentMap('nsg_vb38x_category');
		$vbUsers = $model->getImportContentMap('user');

		if ($options['max'] === false)
		{
			$options['max'] = $this->_sourceDb->fetchOne('
				SELECT MAX(groupid)
				FROM ' . $this->_prefix . 'socialgroup
			');
		}

		$sDb = $this->_sourceDb;
		$groups = $sDb->fetchAll(
			$sDb->limit('
				SELECT socialgroup.*, user.username
				FROM ' . $this->_prefix . 'socialgroup AS socialgroup
					LEFT JOIN '. $this->_prefix .'user AS user ON (user.userid = socialgroup.creatoruserid)
				WHERE socialgroup.groupid > ' . $sDb->quote($start)
			,$options['limit'])
		);

		if (!$groups)
		{
			return true;
		}

		XenForo_Db::beginTransaction();

		$next = 0;
		$total = 0;

		foreach($groups as $group)
		{
			$next = $group['groupid'];

			$categoryId = $this->_mapLookUp($categories, $group['socialgroupcategoryid'], null);
			if (! $categoryId)
			{
				continue;
			}

			$groupState = 'visible';

			$privacyState = 'open';
			if ($group['type'] == 'moderated')
			{
				$privacyState = 'closed';
			}
			elseif ($group['type'] == 'inviteonly')
			{
				$privacyState = 'secret';
			}

			$userId = $this->_mapLookUp($vbUsers, $group['creatoruserid'], $group['creatoruserid']);

			$groupDw = XenForo_DataWriter::create($this->_teamDwName);
			$groupDw->bulkSet(array(
				'title' => substr($this->_convertToUtf8($group['name'], true), 0, 100),
				'team_state' => $groupState,
				'tag_line' => substr($this->_convertToUtf8($group['name'], true), 0, 255),
				'custom_url' => null,
				'team_date' => $group['dateline'],
				'team_category_id' => $categoryId,

				'user_id' => $userId,
				'username' => substr($this->_convertToUtf8($group['username'], true), 0, 50),

				'always_moderate_join' => ($privacyState == 'secret') ? 1 : 0,
				'privacy_state'	=> $privacyState,
				'about' => $this->_convertToUtf8($group['description'], true)
			));

			$groupDw->setExtraData(Nobita_Teams_DataWriter_Team::IMPORT_EXTERNAL_DATA_MODE, true);

			$groupDw->save();
			$newGroupId = $groupDw->get('team_id');

			$groupLogo = $this->_sourceDb->fetchRow('
				SELECT *
				FROM ' . $this->_prefix . 'socialgroupicon
				WHERE groupid = ?
			', array($group['groupid']));

			if ($groupLogo)
			{
				// upload new logo
				$newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
				file_put_contents($newTempFile, $groupLogo['filedata']);

				try
				{
					Nobita_Teams_Container::getModel('Nobita_Teams_Model_Logo')->applyLogo($newGroupId, $newTempFile);
				}
				catch(XenForo_Exception $e) {}

				@unlink($newTempFile);
			}

			if ($newGroupId)
			{
				$members = $this->_sourceDb->fetchAll('
					SELECT member.*, user.username
					FROM '. $this->_prefix .'socialgroupmember AS member
						LEFT JOIN '. $this->_prefix .'user AS user ON (user.userid = member.userid)
					WHERE member.groupid = ?
					ORDER BY member.dateline
				', array($group['groupid']));

				foreach ($members as $member)
				{
					$this->_importMember($member, $newGroupId);
				}
			}

			$model->logImportData('nsg_vb38x_group', $group['groupid'], $newGroupId);
			$total++;
		}

		XenForo_Db::commit();
		$this->_session->incrementStepImportTotal($total);

		return array($next, $options, $this->_getProgressOutput($next, $options['max']));
	}

	public function stepMembers($start, array $options)
	{
		// DO nothing.
		// @see _importMember();
	}

	protected function _importMember(array $member, $groupId)
	{
		$vbUsers = $this->_importModel->getImportContentMap('user');
		$userId = $this->_mapLookUp($vbUsers, $member['userid'], $member['userid']);

		$memberDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Member', XenForo_DataWriter::ERROR_SILENT);

		$memberDw->set('user_id', $userId);
		$memberDw->set('username', substr($this->_convertToUtf8($member['username'], true), 0, 50));

		$memberDw->set('team_id', $groupId);
		$memberDw->set('join_date', $member['dateline']);
		$memberDw->set('member_state', ($member['type'] == 'member') ? 'accept' : 'request');
		$memberDw->set('member_role_id', 'member');

		$memberDw->setExtraData(Nobita_Teams_DataWriter_Member::BYPASS_USER_VERIFICATION, true);
		$memberDw->save();
	}


	public function stepThreads($start, array $options)
	{
		$options = array_merge(array(
			'limit' => 100,
			'max' => false,
			'postDateStart' => 0,
			'postLimit' => 800
		), $options);

		$sourceDb = $this->_sourceDb;
		$nodeId = $this->_nodeId;

		$prefix = $this->_prefix;

		if (! $nodeId)
		{
			return true;
		}

		if ($options['max'] === false)
		{
			$options['max'] = $sourceDb->fetchOne('
				SELECT MAX(discussionid)
				FROM '. $this->_prefix .'discussion
				WHERE groupid <> 0
			');
		}

		$threads = $sourceDb->fetchAll($sourceDb->limit('
			SELECT discussion.*, groupmessage.postuserid, groupmessage.postusername, groupmessage.dateline,
				groupmessage.state, groupmessage.title, groupmessage.pagetext
			FROM ' . $this->_prefix . 'discussion AS discussion
				LEFT JOIN ' . $this->_prefix . 'groupmessage AS groupmessage ON
					(discussion.firstpostid = groupmessage.gmid)
			WHERE discussion.groupid <> 0 AND discussion.discussionid >= '. $sourceDb->quote($start) .'
			ORDER BY discussion.discussionid
		', $options['limit']));

		if (! $threads)
		{
			return true;
		}

		$model = $this->_importModel;

		$groups = $model->getImportContentMap('nsg_vb38x_group');
		$vbUsers = $model->getImportContentMap('user');

		XenForo_Db::beginTransaction();

		$next = 0;
		$total = 0;
		$totalPosts = 0;

		foreach($threads as $thread)
		{
			if (trim($thread['title']) === '')
			{
				continue;
			}

			$groupId = $this->_mapLookUp($groups, $thread['groupid']);
			if (! $groupId)
			{
				continue;
			}

			$postDateStart = $options['postDateStart'];

			$next = $thread['discussionid'] + 1; // uses >=, will be moved back down if need to continue
			$options['postDateStart'] = 0;

			$maxPosts = $options['postLimit'] - $totalPosts;
			$posts = $sourceDb->fetchAll($sourceDb->limit('
				SELECT message.*,
					IF(user.username IS NULL, message.postusername, user.username) AS username
				FROM '. $prefix .'groupmessage AS message
				LEFT JOIN '. $prefix .'user AS user ON (message.postuserid = user.userid)
				WHERE message.discussionid = ' . $sourceDb->quote($thread['discussionid']) . '
					AND message.dateline > '. $sourceDb->quote($postDateStart) .'
				ORDER BY message.dateline
			', $maxPosts));

			if (! $posts)
			{
				if ($postDateStart)
				{
					$total++;
				}
				continue;
			}

			$import = array(
				'title' => $this->_convertToUtf8($thread['title']),
				'node_id' => $this->_nodeId,
				'user_id' => $model->mapUserId($thread['postuserid'], $thread['postuserid']),
				'username' => $this->_convertToUtf8($thread['postusername'], true),
				'discussion_open' => 1,
				'post_date' => $thread['dateline'],
				'last_post_date' => $thread['lastpost'],
				'last_post_username' => $this->_convertToUtf8($thread['lastposter'], true),
				'team_id' => $groupId,
				'discussion_type' => 'team'
			);

			switch($thread['state'])
			{
				case 'moderation': $import['discussion_state'] = 'moderated'; break;
				case 'deleted': $import['discussion_state'] = 'deleted'; break;
				default: $import['discussion_state'] = 'visible'; break;
			}

			$threadId = $model->group_importThread($thread['discussionid'], $import, 'nsg_vb38x_thread');
			if (!$threadId)
			{
				continue;
			}

			$position = -1;
			$import = array(); // reset

			if ($threadId)
			{
				$quotedPostIds = array();

				$threadTitleRegex = '#^(re:\s*)?' . preg_quote($thread['title'], '#') . '$#i';

				$userIdMap = $model->getUserIdsMapFromArray($posts, 'postuserid');

				foreach ($posts AS $i => $post)
				{
					if ($post['title'] !== '' && !preg_match($threadTitleRegex, $post['title']))
					{
						$post['pagetext'] = '[b]' . htmlspecialchars_decode($post['title']) . "[/b]\n\n" . ltrim($post['pagetext']);
					}

					if (trim($post['username']) === '')
					{
						$post['username'] = 'Guest';
					}

					$post['pagetext'] = $this->_convertToUtf8($post['pagetext']);

					$import = array(
						'thread_id' => $threadId,
						'user_id' => $this->_mapLookUp($userIdMap, $post['postuserid'], 0),
						'username' => $this->_convertToUtf8($post['username'], true),
						'post_date' => $post['dateline'],
						'message' => $post['pagetext'],
						'attach_count' => 0,
						'ip' => $post['ipaddress']
					);
					switch ($post['state'])
					{
						case 'moderation': $import['message_state'] = 'moderated'; $import['position'] = $position; break;
						case 'deleted': $import['message_state'] = 'deleted'; $import['position'] = $position; break;
						default: $import['message_state'] = 'visible'; $import['position'] = ++$position; break;
					}

					$post['xf_post_id'] = $model->group_importPost($post['gmid'], $import, 'nsg_vb38x_post');

					$options['postDateStart'] = $post['dateline'];
					$totalPosts++;

					if (stripos($post['pagetext'], '[quote=') !== false)
					{
						if (preg_match_all('/\[quote=("|\'|)(?P<username>[^;\n\]]*);\s*(?P<gmid>\d+)\s*\1\]/siU', $post['pagetext'], $quotes, PREG_SET_ORDER))
						{
							$post['quotes'] = array();

							foreach ($quotes AS $quote)
							{
								$quotedPostId = intval($quote['gmid']);

								$quotedPostIds[] = $quotedPostId;

								$post['quotes'][$quote[0]] = array($quote['username'], $quotedPostId);
							}
						}
					}

					$posts[$i] = $post;
				}

				$postIdMap = (empty($quotedPostIds) ? array() : $model->getImportContentMap('nsg_vb38x_post', $quotedPostIds));

				$db = XenForo_Application::getDb();

				foreach ($posts AS $post)
				{
					if (!empty($post['quotes']))
					{
						$postQuotesRewrite = $this->_rewriteQuotes($post['pagetext'], $post['quotes'], $postIdMap);

						if ($post['pagetext'] != $postQuotesRewrite)
						{
							$db->update('xf_post', array('message' => $postQuotesRewrite), 'post_id = ' . $db->quote($post['xf_post_id']));
						}
					}
				}
			}

			if (count($posts) < $maxPosts)
			{
				// done this thread
				$total++;
				$options['postDateStart'] = 0;
			}
			else
			{
				// not necessarily done the thread; need to pick it up next page
				break;
			}
		}

		if ($options['postDateStart'])
		{
			// not done this thread, need to continue with it
			$next--;
		}

		XenForo_Db::commit();

		$this->_session->incrementStepImportTotal($total);

		return array($next, $options, $this->_getProgressOutput($next - 1, $options['max']));
	}

	protected function _rewriteQuotes($message, array $quotes, array $postIdMap)
	{
		foreach ($quotes AS $quote => &$replace)
		{
			list($username, $postId) = $replace;

			$replace = sprintf('[quote="%s, post: %d"]', $username, $this->_mapLookUp($postIdMap, $postId));
		}

		return str_replace(array_keys($quotes), $quotes, $message);
	}

}
