<?php

class Nobita_Teams_Importer_Waindigo_Social extends Nobita_Teams_Importer_Abstract
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
		return '[Nobita] Social Groups: Waindigo Social Groups';
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
			$db->query('SELECT social_forum_id FROM xf_social_forum LIMIT 1');
		}
		catch (Zend_Db_Exception $e)
		{
			$errors[] = new XenForo_Phrase('Teams_waindigo_addon_not_installed');
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

		$parent['threads'] = array(
			'title' => new XenForo_Phrase('Teams_import_threads'),
			'depends' => array('groups')
		);

		return $parent;
	}

	public function stepCategories($start, array $options)
	{
		$categories = $this->_sourceDb->fetchAll('SELECT * FROM xf_node WHERE node_type_id = ?', array('SocialCategory'));

		$total = 0;
		$model = $this->_importModel;

		XenForo_Db::beginTransaction();

		foreach($categories as $category)
		{
			$importData = array(
				'category_title' => $category['title'],
				'category_description' => $category['description'],
				'display_order' => $category['display_order'],
			);

			$categoryId = $this->_importModel->group_importCategory(
				$category['node_id'], $importData, $this->_categoryDwName
			);

			$this->_importModel->logImportData('nobita_groups_category', $category['node_id'], $categoryId);
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
		$categories = $model->getImportContentMap('nobita_groups_category');

		if ($options['max'] === false)
		{
			$options['max'] = $this->_sourceDb->fetchOne('SELECT MAX(social_forum_id) FROM xf_social_forum');
		}

		$sDb = $this->_sourceDb;
		$groups = $sDb->fetchAll(
			$sDb->limit('
				SELECT *
				FROM xf_social_forum
				WHERE social_forum_id > '.$sDb->quote($start).'
				ORDER BY social_forum_id
			', $options['limit'])
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
			$next = $group['social_forum_id'];
			$categoryId = isset($categories[$group['node_id']]) ? $categories[$group['node_id']] : null;

			if (!$categoryId)
			{
				// invalid category just go on
				continue;
			}

			$groupDw = XenForo_DataWriter::create($this->_teamDwName);
			$groupDw->bulkSet(array(
				'title' => $group['title'],
				'team_state' => $group['group_state'],
				'tag_line' => $group['title'],
				'custom_url' => $group['url_portion'],
				'member_count' => $group['member_count'],
				'team_date' => $group['created_date'],
				'team_category_id' => $categoryId,
				'team_avatar_date' => $group['logo_date'],
				'user_id' => $group['user_id'],

				'always_moderate_join' => $group['social_forum_moderated'] ? 1 : 0,
				'privacy_state'	=> $group['social_forum_open'] ? 'open' : 'closed',
			));

			$groupDw->save();
			$groupId = $groupDw->get('team_id');

			if ($group['sticky'])
			{
				Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->featureTeam($groupDw->getMergedData());
			}

			if ($group['logo_date'])
			{
				/* @var $socialModel Waindigo_SocialGroups_Model_SocialForumAvatar */
				$socialModel = Nobita_Teams_Container::getModel('Waindigo_SocialGroups_Model_SocialForumAvatar');
				$filePath = $socialModel->getAvatarFilePath(
					$group['social_forum_id'], 'l', empty($this->_config['dir']['data']) ? null : $this->_config['dir']['data']
				);

				$newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
				if (file_exists($filePath) && $newTempFile)
				{
					file_put_contents($newTempFile, file_get_contents($filePath));

					try
					{
						Nobita_Teams_Container::getModel('Nobita_Teams_Model_Logo')->applyLogo($groupId, $newTempFile);
					}
					catch(XenForo_Exception $e) {}
				}
				@unlink($newTempFile);
			}

			$model->logImportData('nobita_groups_group', $group['social_forum_id'], $groupId);
			$total++;
		}

		XenForo_Db::commit();

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
			$options['max'] = $sourceDb->fetchOne('SELECT MAX(social_forum_member_id) FROM xf_social_forum_member');
		}

		$model = $this->_importModel;
		$groups = $model->getImportContentMap('nobita_groups_group');

		$members = $sourceDb->fetchAll(
			$sourceDb->limit('SELECT * FROM xf_social_forum_member WHERE social_forum_member_id > ' . $sourceDb->quote($start), $options['limit'])
		);

		if (!$members)
		{
			return true;
		}

		$next = 0;
		$total = 0;

		foreach($members as $member)
		{
			$next = $member['social_forum_member_id'];

			$groupId = isset($groups[$member['social_forum_id']]) ? $groups[$member['social_forum_id']] : null;
			if (!$groupId)
			{
				continue;
			}

			$hasImported = $this->_db->fetchRow('SELECT * FROM xf_team_member WHERE team_id = ? AND user_id = ?', array($groupId, $member['user_id']));
			if ($hasImported)
			{
				continue;
			}

			$memberDw = XenForo_DataWriter::create($this->_memberDwName, XenForo_DataWriter::ERROR_SILENT);

			if ($member['is_social_forum_moderator'] || $member['is_social_forum_creator'])
			{
				$memberGroup = 'admin';
			}
			else
			{
				$memberGroup = 'member'; // default for all
			}

			$memberDw->bulkSet(array(
				'team_id' => $groupId,
				'user_id' => $member['user_id'],
				'position' => $memberGroup,
				'join_date' => $member['join_date'],
				'member_state' => $member['is_approved'] ? 'accept' : 'request'
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
				'max' => false
			), $options
		);

		$sourceDb = $this->_sourceDb;
		if ($options['max'] === false)
		{
			$options['max'] = $sourceDb->fetchOne('
				SELECT MAX(thread_id)
				FROM xf_thread
				WHERE social_forum_id > 0
			');
		}

		$model = $this->_importModel;
		$groups = $model->getImportContentMap('nobita_groups_group');

		$threads = $sourceDb->fetchAll($sourceDb->limit('
			SELECT *
			FROM xf_thread
			WHERE social_forum_id > 0
				AND (team_id = 0 OR team_id IS NULL)
				AND thread_id > ' . $sourceDb->quote($start) . '
			ORDER BY thread_id
		', $options['limit']));

		if (!$threads)
		{
			return true;
		}

		$total = 0;
		$next = 0;

		foreach($threads as $thread)
		{
			$next = $thread['thread_id'];

			$groupId = isset($groups[$thread['social_forum_id']]) ? $groups[$thread['social_forum_id']] : null;
			if (!$groupId)
			{
				continue;
			}

			$threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
			$threadDw->setExistingData($thread['thread_id']);

			$threadDw->set('team_id', $groupId);
			$threadDw->set('node_id', $this->_nodeId);

			$threadDw->set('discussion_type', 'team');

			$threadDw->save();

			$total++;
		}

		$this->_session->incrementStepImportTotal($total);
		return array($next, $options, $this->_getProgressOutput($next, $options['max']));
	}

}
