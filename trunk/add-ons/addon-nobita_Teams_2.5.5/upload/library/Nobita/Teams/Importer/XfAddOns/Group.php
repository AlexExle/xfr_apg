<?php

class Nobita_Teams_Importer_XfAddOns_Group extends Nobita_Teams_Importer_Abstract
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
	 * @var array
	 */
	protected $_config;

	public static function getName()
	{
		return '[Nobita] Social Groups: Importer for XFA Social Groups';
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
			$nodeOptions = XenForo_Option_NodeChooser::getNodeOptions(0, false, 'Forum');

			$viewParams = array(
				'nodeOptions' => $nodeOptions
			);

			return $controller->responseView('Nobita_Teams_ViewAdmin_Import_Config', 'Team_import_waindigo_config', $viewParams);
		}
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
				'charset' => 'utf8',
			)
		);
	}

	public function validateConfiguration(array &$config)
	{
		$errors = array();

		try
		{
			$db = Zend_Db::factory('mysqli',
				array(
					'host' => $config['db']['host'],
					'port' => $config['db']['port'],
					'username' => $config['db']['username'],
					'password' => $config['db']['password'],
					'dbname' => $config['db']['dbname'],
					'charset' => 'utf8',
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
			$db->query('SELECT group_id FROM xfa_group LIMIT 1');
		}
		catch (Zend_Db_Exception $e)
		{
			$errors[] = new XenForo_Phrase('Teams_xfa_group_not_installed');
		}

		if (!empty($config['dir']['data']))
		{
			if (!file_exists($config['dir']['data']) || !is_dir($config['dir']['data']))
			{
				$errors[] = new XenForo_Phrase('data_directory_not_found');
			}
		}
		else
		{
			$config['dir']['data'] = XenForo_Helper_File::getExternalDataPath();
		}

		if (!empty($config['dir']['internal_data']))
		{
			if (!file_exists($config['dir']['internal_data']) || !is_dir($config['dir']['internal_data']))
			{
				$errors[] = new XenForo_Phrase('internal_data_directory_not_found');
			}
		}
		else
		{
			$config['dir']['internal_data'] = XenForo_Helper_File::getInternalDataPath();
		}

		$nodeId = isset($config['node']['node_id']) ? $config['node']['node_id'] : 0;
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

		return $errors;
	}

	public function getSteps()
	{
		$parent = parent::getSteps();

		$parent['invites'] = array(
			'title' => new XenForo_Phrase('Teams_import_invites'),
			'depends' => array('groups')
		);

		$parent['threads'] = array(
			'title' => new XenForo_Phrase('Teams_import_threads'),
			'depends' => array('groups')
		);

		return $parent;
	}

	public function stepCategories($start, array $options)
	{
		$db = $this->_sourceDb;

		$categories = $db->fetchAll('SELECT * FROM xfa_group_category');

		$total = 0;
		$importModel = $this->_importModel;

		foreach($categories as $category)
		{
			$importData = array(
				'category_title' => $category['category_name'],
				'display_order' => $category['category_order'],
			);

			$categoryId = $importModel->group_importCategory(
				$category['category_id'], $importData, $this->_categoryDwName
			);

			$importModel->logImportData('nSocialGroups_category', $category['category_id'], $categoryId);
			$total++;
		}

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
		$categories = $model->getImportContentMap('nSocialGroups_category');

		if ($options['max'] === false)
		{
			$options['max'] = $this->_sourceDb->fetchOne('SELECT MAX(group_id) FROM xfa_group');
		}

		$sDb = $this->_sourceDb;
		$groups = $sDb->fetchAll(
			$sDb->limit('
				SELECT *
				FROM xfa_group
				WHERE group_id > '.$sDb->quote($start).'
				ORDER BY group_id
			',$options['limit'])
		);

		if (!$groups)
		{
			return true;
		}

		$next = 0;
		$total = 0;
		$config = $this->_config;

		foreach($groups as $group)
		{
			$next = $group['group_id'];
			$categoryId = isset($categories[$group['category_id']]) ? $categories[$group['category_id']] : null;

			if (!$categoryId)
			{
				// invalid category just go on
				continue;
			}

			$privacy = 'open';
			if($group['group_type'] == 'invite_only')
			{
				$privacy = 'secret';
			}
			else if($group['group_type'] == 'moderated')
			{
				$privacy = 'closed';
			}

			$owner = $sDb->fetchRow('
				SELECT *
				FROM xfa_group_owner
				WHERE group_id = ?
			', $group['group_id']);

			if(!$owner)
			{
				// Owner group could not be found.
				continue;
			}

			$groupDw = XenForo_DataWriter::create($this->_teamDwName);
			$groupDw->bulkSet(array(
				'title' 		=> $group['group_name'],
				'tag_line' 		=> $group['group_name'],

				'team_category_id' => $categoryId,
				'user_id'		=> $owner['user_id'],

				'privacy_state' => $privacy,
				'team_state' 	=> $group['group_state'],
				'about' 		=> $group['group_description'],

				'team_date'		=> $group['group_create_date'],
				'team_avatar_date' => $group['group_icon_last_update'] ?: 0,
			));

			$groupDw->save();
			$groupId = $groupDw->get('team_id');

			if($group['group_icon_last_update'])
			{
				$path = sprintf('%s/%s/%d.gif', $config['dir']['data'], XfAddOns_Groups_Helper_Image::ICONS_DIR, $group['group_id']);

				$tempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
				if (file_exists($path) && $tempFile)
				{
					file_put_contents($tempFile, file_get_contents($path));

					try
					{
						Nobita_Teams_Container::getModel('Nobita_Teams_Model_Logo')->applyLogo($groupId, $tempFile);
					}
					catch(XenForo_Exception $e) {}

					unlink($tempFile);
				}
			}

			$model->logImportData('nobita_groups_group', $group['group_id'], $groupId);
			$total++;
		}

		$this->_session->incrementStepImportTotal($total);
		return array($next, $options, $this->_getProgressOutput($next, $options['max']));
	}

	public function stepMembers($start, array $options)
	{
		$options = array_merge(array(
				'limit' => 100,
				'max' => false
			), $options
		);

		$sourceDb = $this->_sourceDb;
		if ($options['max'] === false)
		{
			$options['max'] = $sourceDb->fetchOne('SELECT MAX(member_id) FROM xfa_group_member');
		}

		$model = $this->_importModel;
		$groups = $model->getImportContentMap('nobita_groups_group');

		$members = $sourceDb->fetchAll(
			$sourceDb->limit('
				SELECT member.*, user.*
				FROM xfa_group_member AS member
					LEFT JOIN xf_user AS user ON (user.user_id = member.user_id)
				WHERE member.member_id > ' . $sourceDb->quote($start) . '
				ORDER BY member.member_id
			', $options['limit'])
		);

		if (!$members)
		{
			return true;
		}

		$db = XenForo_Application::getDb();

		$next = 0;
		$total = 0;

		foreach($members as $member)
		{
			$next = $member['member_id'];

			$groupId = isset($groups[$member['group_id']]) ? $groups[$member['group_id']] : 0;
			if(empty($groupId))
			{
				continue;
			}

			$imported = $db->fetchRow('SELECT team_id FROM xf_team_member WHERE team_id = ? AND user_id = ?', array(
				$groupId, $member['user_id']
			));

			if($imported)
			{
				continue;
			}

			$memberDw = XenForo_DataWriter::create($this->_memberDwName);
			$memberDw->bulkSet(array(
				'team_id' => $groupId,
				'user_id' => $member['user_id'],
				'position' => 'member',
				'join_date' => $member['join_date'],
				'member_state' => 'accept',
			));

			$memberDw->save();
			$total++;
		}

		$this->_session->incrementStepImportTotal($total);
		return array($next, $options, $this->_getProgressOutput($next, $options['max']));
	}

	public function stepInvites($start, array $options)
	{
		$options = array_merge(array(
				'limit' => 100,
				'max' => false
			), $options
		);

		$sourceDb = $this->_sourceDb;
		if ($options['max'] === false)
		{
			$options['max'] = $sourceDb->fetchOne('SELECT MAX(invite_id) FROM xfa_invite');
		}

		$model = $this->_importModel;
		$groups = $model->getImportContentMap('nobita_groups_group');

		$members = $sourceDb->fetchAll(
			$sourceDb->limit('
				SELECT member.*, user.*
				FROM xfa_invite AS member
					LEFT JOIN xf_user AS user ON (user.user_id = member.user_id)
				WHERE member.invite_id > ' . $sourceDb->quote($start) . '
				ORDER BY member.invite_id
			', $options['limit'])
		);

		if (!$members)
		{
			return true;
		}

		$db = XenForo_Application::getDb();

		$next = 0;
		$total = 0;

		foreach($members as $member)
		{
			$next = $member['invite_id'];

			$groupId = isset($groups[$member['group_id']]) ? $groups[$member['group_id']] : 0;
			if(empty($groupId))
			{
				continue;
			}

			$imported = $db->fetchRow('SELECT team_id FROM xf_team_member WHERE team_id = ? AND user_id = ?', array(
				$groupId, $member['user_id']
			));

			if($imported)
			{
				continue;
			}

			$memberDw = XenForo_DataWriter::create($this->_memberDwName);
			$memberDw->bulkSet(array(
				'team_id' => $groupId,
				'user_id' => $member['user_id'],
				'position' => 'member',
				'join_date' => $member['join_date'],
				'member_state' => 'request',
			));

			$memberDw->save();
			$total++;
		}

		$this->_session->incrementStepImportTotal($total);
		return array($next, $options, $this->_getProgressOutput($next, $options['max']));
	}

	public function stepThreads($start, array $options)
	{
		if (!$this->_nodeId)
		{
			return true;
		}

		$options = array_merge(array(
				'limit' => 100,
				'max' => false,
				'postDateStart' => 0,
				'postLimit' => 800
			), $options
		);

		$sourceDb = $this->_sourceDb;
		if ($options['max'] === false)
		{
			$options['max'] = $sourceDb->fetchOne('SELECT MAX(thread_id) FROM xfa_group_thread');
		}

		$model = $this->_importModel;
		$groups = $model->getImportContentMap('nobita_groups_group');

		$threads = $sourceDb->fetchAll($sourceDb->limit('
			SELECT *
			FROM xfa_group_thread
			WHERE thread_id > '.$sourceDb->quote($start).'
		', $options['limit']));

		if(!$threads)
		{
			return true;
		}

		$total = 0;
		$postCount = 0;
		$next = 0;

		foreach($threads as $thread)
		{
			if(!isset($groups[$thread['group_id']]))
			{
				continue;
			}
			$groupId = $groups[$thread['group_id']];

			$next = $thread['thread_id'] + 1;

			$postDateStart = $options['postDateStart'];
			$options['postDateStart'] = 0;

			$postRemainLimit = $options['postLimit'] - $postCount;
			$posts = $sourceDb->fetchAll($sourceDb->limit('
				SELECT post.*, user.username
				FROM xfa_group_post AS post
					LEFT JOIN xf_user AS user ON (user.user_id = post.user_id)
				WHERE post.thread_id = ? AND post.post_date > ?
				ORDER BY post.post_date
			', $postRemainLimit), array(
				$thread['thread_id'], $postDateStart
			));

			if(!$posts)
			{
				if ($postDateStart)
				{
					$total++;
				}
				continue;
			}

			$import = array(
				'title' => $thread['title'],
				'node_id' => $this->_nodeId,
				'user_id' => $thread['user_id'],
				'username' => $thread['username'],
				'discussion_open' => 1,
				'discussion_state' => $thread['discussion_state'],
				'post_date' => $thread['post_date'],

				'last_post_id' => $thread['last_post_id'],
				'last_post_date' => $thread['last_post_date'],
				'last_post_user_id' => $thread['last_post_user_id'],
				'last_post_username' => $thread['last_post_username'],

				'team_id' => $groupId,
				'discussion_type' => 'team'
			);

			$threadId = $model->group_importThread($thread['thread_id'], $import, 'nSocialGroup_thread');
			if (!$threadId)
			{
				continue;
			}

			$position = -1;

			foreach($posts as $post)
			{
				$postId = $post['post_id'];
				unset($post['ip_id'], $post['post_id']);

				$post['thread_id'] = $threadId;

				if($post['message_state'] == 'deleted')
				{
					$post['position'] = $position;
				}
				else
				{
					$post['position'] = ++$position;
				}

				$post['like_users'] = unserialize($post['like_users']);
				$postId = $model->group_importPost($postId, $post, 'nSocialGroup_post');

				$postCount++;
				$options['postDateStart'] = $post['post_date'];
			}

			if (count($posts) < $postRemainLimit)
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

		$this->_session->incrementStepImportTotal($total);
		return array($next, $options, $this->_getProgressOutput($next - 1, $options['max']));
	}
}
