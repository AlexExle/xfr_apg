<?php

class Nobita_Teams_Model_Team extends Nobita_Teams_Model_Abstract
{
	const FETCH_PROFILE = 0x01;
	const FETCH_PRIVACY = 0x02;
	const FETCH_FEATURED = 0x04;

	const FETCH_CATEGORY = 0x08;
	const FETCH_FIELD = 0x10;
	const FETCH_USER = 0x20;

	const FETCH_DELETION_LOG = 0x40;

	const PRIVACY_OPEN 		= 'open';
	const PRIVACY_CLOSED 	= 'closed';
	const PRIVACY_SECRET 	= 'secret';

	public static $userDataColumns = array(
		'user_id' 			=> 'user_user_id',
		'username' 			=> 'user_username',
		'avatar_date' 		=> 'user_avatar_date',
		'gender'			=> 'user_gender',
		'gravatar' 			=> 'user_gravatar'
	);

	public static $teamDataColumns = array(
		'team_id'			=> 'team_id',
		'title'				=> 'title',
		'team_avatar_date'	=> 'team_avatar_date',
		'user_id'			=> 'team_user_id',
		'username'			=> 'team_username',
		'team_state'		=> 'team_state',
		'privacy_state'		=> 'privacy_state'
	);

	public function getSimpleTeamColumns($alias = 'team')
	{
		$selectQuery = '';
		foreach (self::$teamDataColumns as $dataColumn => $columnAlias) {
			$selectQuery .= $alias . '.' . $dataColumn . ' as ' . $columnAlias . ',';
		}

		$selectQuery = substr($selectQuery, 0, strlen($selectQuery) - 1);

		return $selectQuery;
	}

	public function getTeamDataFromArray(array $data)
	{
		$prefix = 'team_';
		$collected = array();

		foreach(array_keys($data) as $dataKey) {
			if(strpos($dataKey, $prefix) === 0) {
				$teamKey = substr($dataKey, strlen($prefix));

				$value = $data[$dataKey];
				if($teamKey == 'title') {
					$value = XenForo_Helper_String::censorString($value);
				}

				$collected[$teamKey] = $value;
			}
		}

		return $collected;
	}

	public function getTeamById($teamId, array $fetchOptions = array())
	{
		if (empty($teamId))
		{
			return array();
		}

		$joinOptions = $this->prepareTeamFetchOptions($fetchOptions);
		return $this->_getDb()->fetchRow('
			SELECT team.*
			' . $joinOptions['selectFields'] . '
			FROM xf_team AS team
			' . $joinOptions['joinTables'] . '
			WHERE team.team_id = ?
		', $teamId);
	}

	public function getTeamByCustomUrl($url, array $fetchOptions = array())
	{
		if (!$url)
		{
			return false;
		}

		$joinOptions = $this->prepareTeamFetchOptions($fetchOptions);
		return $this->_getDb()->fetchRow('
			SELECT team.*
			' . $joinOptions['selectFields'] . '
			FROM xf_team AS team
			' . $joinOptions['joinTables'] . '
			WHERE team.custom_url = ?
		', $url);
	}

	public function getTeamsByIds(array $teamIds, array $fetchOptions = array())
	{
		if (empty($teamIds))
		{
			return array();
		}

		$joinOptions = $this->prepareTeamFetchOptions($fetchOptions);
		return $this->fetchAllKeyed('
			SELECT team.*
			' . $joinOptions['selectFields'] . '
			FROM xf_team AS team
			' . $joinOptions['joinTables'] . '
			WHERE team.team_id IN (' . $this->_getDb()->quote($teamIds) . ')
		', 'team_id');
	}

	public function getTeamsByTitles(array $titles, array $fetchOptions = array())
	{
		if (empty($titles))
		{
			return array();
		}
		$joinOptions = $this->prepareTeamFetchOptions($fetchOptions);
		return $this->fetchAllKeyed('
			SELECT team.*
			' . $joinOptions['selectFields'] . '
			FROM xf_team AS team
			' . $joinOptions['joinTables'] . '
			WHERE team.title IN (' . $this->_getDb()->quote($titles) . ')
		', 'team_id');
	}

	public function getTeamsByUrls(array $urls, array $fetchOptions = array())
	{
		if (empty($urls))
		{
			return array();
		}

		foreach ($urls as $id => $url)
		{
			if (!$url)
			{
				unset($urls[$id]);
			}
		}

		$joinOptions = $this->prepareTeamFetchOptions($fetchOptions);
		return $this->fetchAllKeyed('
			SELECT team.*
			' . $joinOptions['selectFields'] . '
			FROM xf_team AS team
			' . $joinOptions['joinTables'] . '
			WHERE team.custom_url IN (' . $this->_getDb()->quote($urls) . ')
		', 'team_id');
	}

	public function getTeamsIdsFromUrls(array $urls)
	{
		if (!$urls)
		{
			return array();
		}

		return $this->_getDb()->fetchPairs('
			SELECT custom_url, team_id
			FROM xf_team
			WHERE custom_url IN (' . $this->_getDb()->quote($urls) . ')
		');
	}

	public function getTeams(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause = $this->prepareTeamConditions($conditions, $fetchOptions);

		$joinOptions = $this->prepareTeamFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$orderClause = $this->prepareTeamOrderFetchOptions($fetchOptions, 'team_date');

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT team.*
				' . $joinOptions['selectFields'] . '
				FROM xf_team AS team
				' . $joinOptions['joinTables'] . '
				WHERE ' . $whereClause . '
				' . $orderClause . '
			',$limitOptions['limit'], $limitOptions['offset']
		),'team_id');
	}

	public function getTeamsYouAdmin($userId, array $conditions = array(), array $fetchOptions = array())
	{
		$staffIds = $this->_getMemberRoleModel()->getStaffIds();
		if(empty($staffIds))
		{
			return array();
		}

		$memberModel = $this->_getMemberModel();

		$conditions['user_id'] = $userId;
		$conditions['member_role_id'] = $staffIds;

		$this->addFetchOptionJoin($fetchOptions, Nobita_Teams_Model_Member::FETCH_TEAM_FULL);

		return $memberModel->getMembers($conditions, $fetchOptions);
	}

	public function countTeamsYouAdmin($userId, array $conditions = array())
	{
		$staffIds = $this->_getMemberRoleModel()->getStaffIds();
		if(empty($staffIds))
		{
			return 0;
		}

		$fetchOptions = array();
		$whereClause = $this->prepareTeamConditions($conditions, $fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_team AS team
				LEFT JOIN xf_team_member AS team_member ON
					(team_member.team_id = team.team_id)
			WHERE '. $whereClause .'
				AND team_member.member_role_id IN ('.$this->_getDb()->quote($staffIds).')
				AND team_member.user_id = ?
		', array($userId));
	}

	public function countTeamsYouJoined($userId, array $conditions)
	{
		$fetchOptions = array();
		$whereClause = $this->prepareTeamConditions($conditions, $fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_team AS team
				LEFT JOIN xf_team_member AS team_member ON
					(team_member.team_id = team.team_id)
			WHERE '. $whereClause .'
				AND team_member.user_id = ?
		', array($userId));
	}

	public function getFullTeamById($teamId, array $fetchOptions = array())
	{
		if (empty($teamId))
		{
			return array();
		}

		if (!isset($fetchOptions['join']))
		{
			$fetchOptions['join'] = 0;
		}
		$fetchOptions['join'] |= self::FETCH_PROFILE | self::FETCH_PRIVACY;

		return $this->getTeamById($teamId, $fetchOptions);
	}

	public function getAllTeamsByCategoryId($categoryId)
	{
		if (empty($categoryId))
		{
			return array();
		}

		$conditions = array(
			'team_category_id' => $categoryId,
			'deleted' => true,
			'moderated' => true
		);
		$fetchOptions = array(
			'join' => self::FETCH_PROFILE | self::FETCH_PRIVACY
		);

		return $this->getTeams($conditions, $fetchOptions);
	}

	public function countTeams(array $conditions)
	{
		$fetchOptions = array();
		$whereClause = $this->prepareTeamConditions($conditions, $fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_team AS team
			WHERE ' .$whereClause . '
		');
	}

	public function prepareTeamFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$db = $this->_getDb();

		if (!isset($fetchOptions['memberUserId']) && XenForo_Visitor::getUserId())
		{
			// alway join to member table
			$fetchOptions['memberUserId'] = XenForo_Visitor::getUserId();
		}

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_PROFILE)
			{
				$selectFields .=',
					profile.*';
				$joinTables .='
					LEFT JOIN xf_team_profile AS profile ON (profile.team_id = team.team_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_PRIVACY)
			{
				$selectFields .=',
					privacy.*';
				$joinTables .='
					LEFT JOIN xf_team_privacy AS privacy ON (privacy.team_id = team.team_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_DELETION_LOG)
			{
				$selectFields .= ',
					deletion_log.delete_date, deletion_log.delete_reason,
					deletion_log.delete_user_id, deletion_log.delete_username';
				$joinTables .= '
					LEFT JOIN xf_deletion_log AS deletion_log ON
						(deletion_log.content_type = \'team\' AND deletion_log.content_id = team.team_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_USER)
			{
				$userDataColumns = self::$userDataColumns;
				$selectQuery = '';

				foreach ($userDataColumns as $dataColumn => $columnAlias) {
					$selectQuery .=',user.' . $dataColumn . ' as ' . $columnAlias;
				}

				$selectFields .= $selectQuery;
				$joinTables .='
					LEFT JOIN xf_user AS user ON (user.user_id = team.user_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_FEATURED)
			{
				$selectFields .=', feature.feature_date';
				$joinTables .='
					LEFT JOIN xf_team_feature AS feature ON (feature.team_id = team.team_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_CATEGORY)
			{
				$selectFields .=',
					category.*';
				$joinTables .='
					LEFT JOIN xf_team_category AS category ON (category.team_category_id = team.team_category_id)';
			}
		}

		if (isset($fetchOptions['banUserId']))
		{
			if (!empty($fetchOptions['banUserId']))
			{
				$selectFields .= ', banning.user_reason, banning.end_date as ban_expired_date';
				$joinTables .='
					LEFT JOIN xf_team_ban AS banning ON (
						banning.team_id = team.team_id AND banning.user_id = ' . $this->_getDb()->quote($fetchOptions['banUserId']) . '
					)';
			}
			else
			{
				$selectFields .=', 0 as ban_expired_date';
			}
		}

		if (isset($fetchOptions['memberUserId']) && $fetchOptions['memberUserId'] !== false)
		{
			if (!empty($fetchOptions['memberUserId']))
			{
				$dataColumns = Nobita_Teams_Model_Member::$memberDataColumns;
				$selectQuery = '';

				foreach ($dataColumns as $columnName => $columnAlias)
				{
					$selectQuery .= ',member.' . $columnName . ' as ' . $columnAlias;
				}

				$selectFields .= $selectQuery;
				$joinTables .='
					LEFT JOIN xf_team_member as member ON (
						member.team_id = team.team_id AND member.user_id = ' . $this->_getDb()->quote($fetchOptions['memberUserId']) . '
					)';
			}
			else
			{
				$selectFields .=', 0 as member_user_id';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables
		);
	}

	public function prepareTeamConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		$basicConditions = array(
			'team_id' 			=> array('field' => 'team_id'),
			'team_id_ex' 		=> array('field' => 'team_id', 'notEqual' => true),
			'team_category_id' 	=> array('field' => 'team_category_id'),
			'user_id' 			=> array('field' => 'user_id'),
			'privacy_state' 	=> array('field' => 'privacy_state'),
			'team_state' 		=> array('field' => 'team_state')
		);

		$sqlConditions = array_merge($sqlConditions,
			$this->preGetConditionsForClause('team', $basicConditions, $conditions)
		);

		if(!empty($conditions['title']))
		{
			$sqlConditions[] = 'team.title LIKE ' . XenForo_Db::quoteLike($conditions['title'], 'lr');
		}

		if (isset($conditions['deleted']) || isset($conditions['moderated']))
		{
			$sqlConditions[] = $this->prepareStateLimitFromConditions($conditions, 'team', 'team_state');
		}
		else
		{
			// sanity check: only get visible teams unless we've explicitly said to get something else
			$sqlConditions[] = 'team.team_state = \'visible\'';
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareTeamOrderFetchOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'team_date' 	=> 'team.team_date',
			'view_count' 	=> 'team.view_count',
			'member_count' 	=> 'team.member_count',
			'last_updated' 	=> 'team.last_updated',
			'thread_count' => 'team.thread_count',
			'thread_post_count' => 'team.thread_post_count',
			'random' 		=> 'RAND()'
		);

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

	public function prepareTeam(array $team, array $category = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if ($category)
		{
			$team['canWarn'] = $this->canWarnTeam($team, $category, $null, $viewingUser);
			$team['canReport'] = Nobita_Teams_Container::getModel('XenForo_Model_User')->canReportContent();
			$team['canDelete'] = $this->canDeleteTeam($team, $category, $null, $viewingUser);
			$team['canEdit'] = $this->canEditTeam($team, $category, $null, $viewingUser);
			$team['canUndelete'] = $this->canUndeleteTeam($team, $category, $null, $viewingUser);
			$team['getURLPortion'] = $this->canCustomizeUrlPortions($team, $category, $null, $viewingUser);
			$team['canApprove'] = $this->canApproveTeam($team, $category, $null, $viewingUser);
			$team['canUnapprove'] = $this->canUnapproveTeam($team, $category, $null, $viewingUser);
			$team['canFeatureUnfeature'] = $this->canFeatureUnfeatureTeam($team, $category, $null, $viewingUser);

			$logoModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Logo');
			$team['canUploadLogo'] = $logoModel->canUploadLogo($team, $category, $null, $viewingUser);
			$team['canReassign'] = $this->canReassignTeam($team, $category, $null, $viewingUser);

			$team['canUpdateRules'] = $this->canUpdateRules($team, $category, $null, $viewingUser);

			if (!isset($team['canInlineMod']))
			{
				$this->addInlineModOptionToTeam($team, $category, $viewingUser);
			}

			$team['canUploadCover'] = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Cover')->canUploadCover($team, $category, $null, $viewingUser);
			$team['canChooseRibbon'] = $this->canChooseRibbon($team, $category, $null, $viewingUser);

			/* 1.1.3 */
			$team['canAddEvent'] = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event')->canAddEvent($team, $category, $null, $viewingUser);

			/* 1.2.0 RC2 */
			$team['canManageTabs'] = $this->canManageTabs($team, $category, $null, $viewingUser);

			$team['isVisible'] = $this->isVisible($team);
			$team['isModerated'] = $this->isModerated($team);
			$team['isDeleted'] = $this->isDeleted($team);
			$team['viewBannedUsers'] = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Banning')->canViewBannedUsers($team, $category, $null, $viewingUser);

			$memberDataColumns = Nobita_Teams_Model_Member::$memberDataColumns;

			$team['memberInfo'] = array();
			foreach ($memberDataColumns as $columnName => $columnAlias)
			{
				if (array_key_exists($columnAlias, $team))
				{
					if (!is_null($team[$columnAlias]))
					{
						$team['memberInfo'][$columnName] = $team[$columnAlias];
					}

					unset($team[$columnAlias]);
				}
			}

			if (count($team['memberInfo']) == 1)
			{
				$team['memberInfo'] = array();
			}

			if ($team['memberInfo'])
			{
				$team['memberInfo'] = $this->_getMemberModel()->prepareMember($team['memberInfo'], $team, $viewingUser);
			}

			$team['canJoinTeam'] = $this->_getMemberModel()->canAsktoJoin($team, $category, $null, $viewingUser);
			$team['canLeaveTeam'] = !$team['canJoinTeam'] && !$this->isTeamOwner($team) && $viewingUser['user_id'];
			$team['canAcceptInvite'] = $this->_getMemberModel()->canAcceptInvite($team['memberInfo'], $team, $null, $viewingUser);

			$team['canMassAlert'] = $this->canSendMassAlerts($team, $category, $null, $viewingUser);

			if (isset($viewingUser['team_ribbon_id']) && !empty($viewingUser['user_id']))
			{
				if ($viewingUser['team_ribbon_id'] == $team['team_id'])
				{
					$team['canRemoveRibbonStyling'] = true;
				}
				else
				{
					$team['canChooseRibbonStyling'] = !empty($team['ribbon_text']) && !empty($team['memberInfo']);
				}
			}

			// 2.3.6
			$team['canEditTags'] = $this->canEditTags($team, $category, $null, $viewingUser);

			if(isset($team['staff_list']))
			{
				$team['staffList'] = json_decode($team['staff_list'], true);
			}
		}
		else
		{
			$team['canWarn'] = false;
			$team['canReport'] = false;
			$team['canDelete'] = false;
			$team['canEdit'] = false;
			$team['canUndelete'] = false;
			$team['getURLPortion'] = false;
			$team['canApprove'] = false;
			$team['canUnapprove'] = false;
			$team['canFeatureUnfeature'] = false;

			$team['canUploadAvatar'] = false;
			$team['canReassign'] = false;
			$team['canUpdateRules'] = false;
			$team['canInlineMod'] = false;

			$team['canUploadCover'] = false;
			$team['canChooseRibbon'] = false;

			$team['canAddEvent'] = false;
			$team['canManageTabs'] = false;
			$team['coverCropDetails'] = false;

			$team['memberInfo'] = array();
		}

		$team['title'] = XenForo_Helper_String::censorString($team['title']);
		$team['privacyTitle'] = new XenForo_Phrase('Teams_' . $team['privacy_state']);

		if (array_key_exists('disable_tabs', $team))
		{
			$team['disabledTabs'] = array_map('trim', explode(',', $team['disable_tabs']));
		}

		$userDataColumns = self::$userDataColumns;
		foreach ($userDataColumns as $dataColumn => $columnAlias)
		{
			if (array_key_exists($columnAlias, $team) && !is_null($team[$columnAlias]))
			{
				$team['user'][$dataColumn] = $team[$columnAlias];

				unset($team[$columnAlias]);
			}
		}

		// It maybe a bug: https://xenforo.com/community/threads/posts/976137/
		if (array_key_exists('tags', $team))
		{
			$team['tagsList'] = unserialize($team['tags']);
		}
		else
		{
			$team['tagsList'] = array();
		}

		if (empty($team['memberInfo']['last_view_date']))
		{
			$team['isNew'] = true;
		}
		else
		{
			$team['isNew'] = ($team['memberInfo']['last_view_date'] < $team['last_updated']);
		}

		return $team;
	}

	public function prepareTeamViewTabs(array $team, array $category, array $viewingUser = null)
	{
		$tabs = Nobita_Teams_Option::getTabsSupported(false);
		foreach($tabs as $tabType => &$tab)
		{
			$tab = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Tab')->buildTabComponents(
				$tabType, $tab, $team
			);
		}
		unset($tab);

		$team['tabs']  = $tabs;
		return $team;
	}

	public function prepareTeams(array $teams, array $category = null, array $viewingUser = null)
	{
		foreach ($teams as &$team)
		{
			if ($category === null && isset($team['category_title']))
			{
				$team = $this->prepareTeam($team, $team, $viewingUser);
			}
			else
			{
				$team = $this->prepareTeam($team, $category, $viewingUser);
			}

		}

		return $teams;
	}

	public function addInlineModOptionToTeam(array &$team, array $category, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$modOptions = array();
		$canInlineMod = ($viewingUser['user_id'] && (
			XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteAny')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'undelete')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'approveUnapprove')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editAny')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'featureUnfeature')
		));

		if ($canInlineMod)
		{
			$null = null;
			if ($this->canDeleteTeam($team, $team, 'soft', $null, $viewingUser))
			{
				$modOptions['delete'] = true;
			}

			if ($this->canUndeleteTeam($team, $team, $null, $viewingUser))
			{
				$modOptions['undelete'] = true;
			}

			if ($this->canApproveTeam($team, $team, $null, $viewingUser))
			{
				$modOptions['approve'] = true;
			}

			if ($this->canUnapproveTeam($team, $team, $null, $viewingUser))
			{
				$modOptions['unapprove'] = true;
			}

			if ($this->canFeatureUnfeatureTeam($team, $team, $null, $viewingUser))
			{
				$modOptions['feature'] = true;
				$modOptions['unfeature'] = true;
			}
		}

		$team['canInlineMod'] = (count($modOptions) > 0);

		return $modOptions;
	}

	public function getInlineModOptionsForTeams(array $teams, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$inlineModOptions = array();

		foreach ($teams AS $team)
		{
			$teamModOptions = $this->addInlineModOptionToTeam($team, $team, $viewingUser);
			$inlineModOptions += $teamModOptions;
		}

		return $inlineModOptions;
	}

	public function prepareTeamCustomFields(array $team, array $category, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$team['customFields'] = unserialize($team['custom_fields']);
		if (!is_array($team['customFields']))
		{
			$team['customFields'] = array();
		}

		$team['showExtraInfoTab'] = false;

		if (!isset($category['fieldCache']))
		{
			$category['fieldCache'] = @unserialize($category['field_cache']);
			if (!is_array($category['fieldCache']))
			{
				$category['fieldCache'] = array();
			}
		}
		if (!empty($category['fieldCache']['extra_tab']))
		{
			foreach ($category['fieldCache']['extra_tab'] AS $fieldId)
			{
				if (isset($team['customFields'][$fieldId]) && $team['customFields'][$fieldId] !== '')
				{
					$team['showExtraInfoTab'] = true;
					break;
				}
			}
		}

		$team['customFieldTabs'] = array();
		if (!empty($category['fieldCache']['new_tab']))
		{
			foreach ($category['fieldCache']['new_tab'] AS $fieldId)
			{
				if (isset($team['customFields'][$fieldId])
					&& (
						(is_string($team['customFields'][$fieldId]) && $team['customFields'][$fieldId] !== '')
						|| (is_array($team['customFields'][$fieldId]) && count($team['customFields'][$fieldId]))
					)
				) {
					$team['customFieldTabs'][] = $fieldId;
				}
			}
		}

		return $team;
	}

	public function deleteTeam($teamId, $deleteType, array $options = array())
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
		$dw->setExistingData($teamId);

		if ($deleteType == 'hard')
		{
			$dw->delete();
		}
		else if ($deleteType == 'soft')
		{
			$dw->set('team_state', 'deleted');
			$dw->save();
		}

		return $dw;
	}

	public function filterUnviewableTeams(array $teams, array $category = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		foreach ($teams as $key => $team)
		{
			$cat = ($category ? $category : $team);
			//if (!$this->canViewTeamAndContainer($team, $cat, $null, $viewingUser))
			if (!$this->canViewTeam($team, $cat, $null, $viewingUser))
			{
				// should be viewTeam only.
				unset($teams[$key]);
			}
		}

		return $teams;
	}

	public function getFeaturedTeamsInCategories(array $categoryIds, array $fetchOptions = array())
	{
		if (!$categoryIds)
		{
			return array();
		}

		if (isset($fetchOptions['join']) && $fetchOptions['join'] & self::FETCH_FEATURED)
		{
			$fetchOptions['join'] &= ~self::FETCH_FEATURED;
		}

		if (!empty($fetchOptions['order']) && $fetchOptions['order'] == 'random')
		{
			$orderClause = 'ORDER BY RAND()';
		}
		else
		{
			$orderClause = $this->prepareTeamOrderFetchOptions($fetchOptions, 'feature.feature_date DESC');
		}

		$joinOptions = $this->prepareTeamFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT team.*, feature.feature_date
					' . $joinOptions['selectFields'] . '
				FROM xf_team_feature AS feature
				INNER JOIN xf_team AS team ON (team.team_id = feature.team_id)
					' . $joinOptions['joinTables'] . '
				WHERE team.team_category_id IN (' . $this->_getDb()->quote($categoryIds) . ')
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'team_id');
	}

	public function featureTeam(array $team, $featureDate = null)
	{
		$db = $this->_getDb();

		if ($featureDate === null)
		{
			$featureDate = XenForo_Application::$time;
		}

		XenForo_Db::beginTransaction($db);

		$result = $db->query("
			INSERT INTO xf_team_feature
				(team_id, feature_date)
			VALUES
				(?, ?)
			ON DUPLICATE KEY UPDATE
				feature_date = VALUES(feature_date)
		", array($team['team_id'], $featureDate));

		if ($result->rowCount() == 1 && $team['team_state'] == 'visible')
		{
			// insert with a visible team
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Category', XenForo_DataWriter::ERROR_SILENT);
			if (!$dw->setExistingData($team['team_category_id']))
			{
				return false;
			}

			$dw->updateFeaturedCount(1);
			$dw->save();

			XenForo_Model_Log::logModeratorAction('team', $team, 'feature');
		}

		XenForo_Db::commit($db);

		return true;
	}

	public function unfeatureTeam(array $team)
	{
		$db = $this->_getDb();

		XenForo_Db::beginTransaction($db);

		$deleteRecord = $db->delete('xf_team_feature', 'team_id = ' . $db->quote($team['team_id']));
		if ($deleteRecord && $team['team_state'] == 'visible')
		{
			$catDw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Category', XenForo_DataWriter::ERROR_SILENT);
			if (!$catDw->setExistingData($team['team_category_id']))
			{
				return false;
			}

			//$catDw->updateFeaturedCount(-1); maybe it error.
			$catDw->updateFeaturedCount();
			$catDw->save();

			XenForo_Model_Log::logModeratorAction('team', $team, 'unfeature');
		}

		XenForo_Db::commit($db);

		return true;
	}

	public function canAddForum(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		if(!$this->canViewTabAndContainer('forums', $team, $category, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		return $this->isTeamOwner($team, $viewingUser);
	}

	public function canViewAttachments(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if($this->isTeamMember($team['team_id'], $viewingUser))
		{
			return true;
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'viewAttachment');
	}

	public function canEditTags(array $team = null, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		if (!XenForo_Application::getOptions()->enableTagging)
		{
			return false;
		}

		$this->standardizeViewingUserReference($viewingUser);

		if ($team)
		{
			if ($team['user_id'] == $viewingUser['user_id'])
			{
				return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'manageOwnTag');
			}

			return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'manageAnyTag');
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'manageOwnTag')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'manageAnyTag'))
		{
			return true;
		}

		return false;
	}

	public function canEditForum(array $forum, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return $this->isTeamOwner($team, $viewingUser);
	}

	public function canDeleteForum(array $forum, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return $this->isTeamOwner($team, $viewingUser);
	}

	public function canEditTitleAndTagLine(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id']) return false;

		if ($viewingUser['user_id'] == $team['user_id'])
		{
			return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editTitleTagLineSelf');
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editAny');
	}

	public function canFeatureUnfeatureTeam(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return ($viewingUser['user_id']
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'featureUnfeature')
		);
	}

	public function canApproveUnapproveTeam(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return ($viewingUser['user_id']
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'approveUnapprove')
		);
	}

	public function canApproveTeam(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return ($viewingUser['user_id']
			&& $this->isModerated($team)
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'approveUnapprove')
		);
	}

	public function canUnapproveTeam(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return ($viewingUser['user_id']
			&& $this->isVisible($team)
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'approveUnapprove')
		);
	}

	public function canCustomizeUrlPortions(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($this->isTeamOwner($team, $viewingUser)
			&& ($team['custom_url'] === null || empty($team['custom_url']))
		)
		{
			return true;
		}

		return false;
	}

	public function canEditTeam(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editAny'))
		{
			return true;
		}

		return ($this->isTeamOwner($team, $viewingUser)
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'updateSelf'));
	}

	public function canDeleteTeam(array $team, array $category, $type = 'soft', &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id']) { return false; }

		if ($type == 'hard')
		{
			return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'hardDeleteAny');
		}

		if ($this->isTeamOwner($team, $viewingUser))
		{
			return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteSelf');
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteAny');
	}

	public function canViewTeams(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'view');
	}

	public function canReassignTeam(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($this->isTeamOwner($team, $viewingUser))
		{
			return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'reassignSelf');
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'reassignAny');
	}

	public function canUpdateRules(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'updateRules');
	}

	public function canAddSecretTeam(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (! $viewingUser['user_id'])
		{
			return false;
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'addSecretGroup');
	}

	/**
	 * Determine viewing user has permission to view
	 * Team. Not include content on page. Basic page only
	 *
	 * @return bool
	 */
	public function canViewTeam(array $team, array $category = null, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (is_null($category))
		{
			$category = $team;
		}
		$userId = false;

		if (array_key_exists('team_user_id', $team))
		{
			$userId = $team['team_user_id'];
		}
		else
		{
			$userId = $team['user_id'];
		}

		if (is_array($viewingUser))
		{
			if (! isset($viewingUser['global_permission_cache']))
			{
				$permissionCombinationId = isset($viewingUser['permission_combination_id'])
					? $viewingUser['permission_combination_id']
					: XenForo_Model_User::$guestPermissionCombinationId;
				$viewingUser = Nobita_Teams_Container::getModel('XenForo_Model_User')->setPermissionsOnVisitorArray($viewingUser, $permissionCombinationId);
			}
			$viewingUser['permissions'] = XenForo_Permission::unserializePermissions($viewingUser['global_permission_cache']);
		}

		if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'view'))
		{
			return false;
		}

		if ($this->isModerated($team))
		{
			if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'viewModerated'))
			{
				if (!$viewingUser['user_id'] || $viewingUser['user_id'] != $userId)
				{
					$errorPhraseKey = 'Teams_requested_team_not_found';
					return false;
				}
			}
		}
		elseif ($this->isDeleted($team))
		{
			if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'viewDeleted'))
			{
				$errorPhraseKey = 'Teams_requested_team_not_found';
				return false;
			}
		}

		if ($this->isSecret($team))
		{
			// good.
			return $this->canViewTeamSecret($team, $category, $errorPhraseKey, $viewingUser);
		}

		return true;
	}

	/**
	 * Determine viewing user has permission to view
	 * all content of team
	 *
	 * @return bool
	 */
	public function canViewTeamAndContainer(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$this->canViewTeam($team, $category, $errorPhraseKey, $viewingUser))
		{
			return false;
		}

		switch($team['privacy_state'])
		{
			case self::PRIVACY_OPEN:
				// nothing to do on public team
				return true;
			case self::PRIVACY_CLOSED:
				return $this->canViewTeamClosedAndContainer($team, $category, $errorPhraseKey, $viewingUser);
			case self::PRIVACY_SECRET:
				return $this->canViewTeamSecret($team, $category, $errorPhraseKey, $viewingUser);
			default:
				return false; // not match
		}
	}

	public function canViewTeamClosedAndContainer(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editAny')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteAny')
		)
		{
			// you is owner of your board or have any permission to manage group
			// so you should have permission to view all content
			return true;
		}

		return $this->isTeamMember($team['team_id'], $viewingUser);
	}

	public function canViewTeamSecret(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'viewSecret'))
		{
			return true;
		}

		return $this->isTeamMember($team['team_id'], $viewingUser);
	}

	/**
	 * Control wall type to display *member, moderator*
	 *
	 * @param $tabType
	 *
	 * Return bool true|false
	 */
	public function canViewMemberOrAdminTab($tabType = 'member', array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (! $viewingUser['user_id'])
		{
			return false;
		}

		/*
			@reason: http://nobita.me/posts/5478/
		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editAny')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteAny')
		)
		{
			// you is owner of your board or have any permission to manage group
			// so you should have permission to view all content
			return true;
		}*/

		if (strpos($tabType, 'wtype_') === false)
		{
			$tabId = sprintf('wtype_%s', $tabType);
		}

		$disabled = array_map('trim', explode(',', $team['disable_tabs']));

		if (in_array($tabId, $disabled))
		{
			$errorPhraseKey = 'requested_page_not_found';
			return false;
		}

		if ($tabType == 'member')
		{
			return $this->isTeamMember($team['team_id'], $viewingUser);
		}
		elseif ($tabType == 'moderator' OR $tabType == 'staff')
		{
			return $this->isTeamAdmin($team['team_id'], $viewingUser);
		}

		return false;
	}

	/**
	 * Control tabs to display *members, photos, events
	 * @params $tabId
	 */
	public function canViewTabAndContainer($tabId, array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

	//	$allowTabs = array('members', 'photos', 'events', 'extra', 'threads', 'rules');
	//	if (!in_array($tabId, $allowTabs)) return false;

		$photoProvider = Nobita_Teams_Option::get('photoProvider');

		if ($tabId == 'photos')
		{
			if ($photoProvider == 'disabled')
			{
				return false;
			}
			elseif (!Nobita_Teams_AddOnChecker::getInstance()->isActive('sonnb_xengallery')
				&& !Nobita_Teams_AddOnChecker::getInstance()->isXenMediaExistsAndActive())
			{
				$errorPhraseKey = 'requested_page_not_found';
				return false;
			}
		}

		if (!isset($team['disable_tabs']))
		{
			return false;
		}

		if (empty($category['allow_enable_disable_tabs']))
		{
			// version 2.4.0
			$disableTabsInCategory = array_map('trim', explode(',', $category['disable_tabs_default']));

			if (in_array($tabId, $disableTabsInCategory))
			{
				// Disable.
				return false;
			}
		}

		$disableTabs = array_map('trim', explode(',', $team['disable_tabs']));

		/*
			@reason http://nobita.me/posts/5478/
		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editAny')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteAny')
		)
		{
			// you is owner of your board or have any permission to manage group
			// so you should have permission to view all content
			return true;
		}*/

		if (!$this->isOpen($team))
		{
			// should be invisible to guest if team not public
			// @privacy error
			$user = $this->isTeamMember($team['team_id'], $viewingUser);
			if (!$viewingUser['user_id'] || empty($user))
			{
				$errorPhraseKey = 'requested_page_not_found';
				return false;
			}
		}

		switch ($tabId)
		{
			case 'members':
				/*if ($this->isTeamAdmin($team['team_id'], $viewingUser))
				{
					return true;
				}*/

				return !in_array('member_list', $disableTabs);
			case 'photos':
				if (in_array($tabId, $disableTabs)) return false;

				if (Nobita_Teams_AddOnChecker::getInstance()->isXenMediaExistsAndActive())
				{
					return Nobita_Teams_Container::getModel('XenGallery_Model_Media')->canViewMedia();
				}
				elseif(Nobita_Teams_AddOnChecker::getInstance()->isSonnbXenGalleryExistsAndActive())
				{
					return Nobita_Teams_Container::getModel('sonnb_XenGallery_Model_Gallery')->canViewGallery();
				}

				return false;
			case 'extra':
			case 'events':
			case 'threads':
			case 'forums':
			case 'rules':
				return !in_array($tabId, $disableTabs);
			case 'statsDaily':
				return !in_array($tabId, $disableTabs) && $this->isTeamAdmin($team['team_id']);
			default:
				throw new XenForo_Exception("Unknown the tab id: $tabId", false);
				return false;
		}
	}

	public function canWarnTeam(array $team, $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (empty($team['user_id']) || $team['warning_id'])
		{
			return false;
		}

		if (!empty($team['is_admin']) || !empty($team['is_moderator']))
		{
			return false;
		}

		if ($this->isTeamOwner($team, $viewingUser))
		{
			return false;
		}

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		return  XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'warn');
	}

	public function canUndeleteTeam(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return ($viewingUser['user_id']
			&& $this->isDeleted($team)
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'undelete')
		);
	}

	public function canPostOnTeam(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($this->isTeamOwner($team, $viewingUser))
		{
			return true; // team owner can post
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			// Not an member?
			if($team['allow_guest_posting'])
			{
				// Only accept posting to public team
				return $this->isOpen($team);
			}

			return false;
		}

		if($this->_getMemberRoleModel()->hasGeneralPermission($memberRecord['member_role_id'], 'bypassPosting'))
		{
			return true;
		}

		// Member of team?
		if($team['allow_member_posting'])
		{
			return $this->isTeamMember($team['team_id'], $viewingUser);
		}

		return false;
	}

	public function canChooseRibbon(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return ($this->isTeamOwner($team, $viewingUser)
			&& !empty($category['ribbonStyling'])
		);
	}

	public function isVisible(array $team)
	{
		return ($team['team_state'] == "visible");
	}

	public function isModerated(array $team)
	{
		return ($team['team_state'] == "moderated");
	}

	public function isDeleted(array $team)
	{
		return ($team['team_state'] == "deleted");
	}

	public function isSecret(array $team)
	{
		return ($team['privacy_state'] == self::PRIVACY_SECRET);
	}

	public function isClosed(array $team)
	{
		return ($team['privacy_state'] == self::PRIVACY_CLOSED);
	}

	public function isOpen(array $team)
	{
		return ($team['privacy_state'] == self::PRIVACY_OPEN);
	}

	public function getTeamsAndParentData(array $teamIds)
	{
		$categories = array();
		$teams = $this->getTeamsByIds($teamIds, array(
			'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
					| Nobita_Teams_Model_Team::FETCH_PROFILE
					| Nobita_Teams_Model_Team::FETCH_PRIVACY
		));

		if ($teams)
		{
			$categoryIds = array();
			foreach ($teams as $team)
			{
				$categoryIds[] = $team['team_category_id'];
			}

			$categories = $this->_getCategoryModel()->getCategoriesByIds($categoryIds);
			foreach ($teams as $teamId => $team)
			{
				if (!isset($categories[$team['team_category_id']]))
				{
					unset($teams[$teamId]);
				}
			}
		}

		return array($teams, $categories);
	}

	/**
	 * Gets team IDs in the specified range. The IDs returned will be those immediately
	 * after the "start" value (not including the start), up to the specified limit.
	 *
	 * @param integer $start IDs greater than this will be returned
	 * @param integer $limit Number of posts to return
	 *
	 * @return array List of IDs
	 */
	public function getTeamIdsInRange($start, $limit)
	{
		$db = $this->_getDb();

		return $db->fetchCol($db->limit('
				SELECT team_id
				FROM xf_team
				WHERE team_id > ?
				ORDER BY team_id
			', $limit), $start);
	}

	public function getTeamIdsInRangeByCategoryId($categoryId, $start, $limit)
	{
		$db = $this->_getDb();

		return $db->fetchCol($db->limit('
				SELECT team_id
				FROM xf_team
				WHERE team_id > ? AND team_category_id = ?
				ORDER BY team_id
			', $limit), array($start, $categoryId));
	}

	public function getMinTeamIdInCategory($categoryId)
	{
		return $this->_getDb()->fetchOne('SELECT MIN(team_id) FROM xf_team WHERE team_category_id = ?', array($categoryId));
	}

	public function applyUserRibbon($teamId, $userId)
	{
		$db = $this->_getDb();

		$db->query('
			UPDATE xf_user
			SET team_ribbon_id = ?
			WHERE user_id = ?
		', array($teamId, $userId));
	}

	public function removeUserRibbon($userId)
	{
		$db = $this->_getDb();

		$db->query('
			UPDATE xf_user
			SET team_ribbon_id = 0
			WHERE user_id = ?
		', array($userId));
	}

	public function updateAllRibbonForMember($teamId)
	{
		$db = $this->_getDb();

		$db->query('
			UPDATE xf_user
			SET team_ribbon_id = 0
			WHERE team_ribbon_id = ?
		', array($teamId));
	}

	public function canUploadAndManageAttachment(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return ($viewingUser['user_id']
			&& $this->_getCategoryModel()->canUploadAttachments($category)
			&& XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'uploadAttachment')
		);
	}

	/**
	 * Gets the set of attachment params required to allow uploading.
	 *
	 * @param array $team
	 * @param array $contentData Information about the content, for URL building
	 * @param array|null $nodePermissions
	 * @param array|null $viewingUser
	 * @param string|null $tempHash
	 *
	 * @return array|false
	 */
	public function getAttachmentParams(array $team, array $category, array $contentData, array $viewingUser = null, $tempHash = null)
	{
		if (empty($contentData['content_type']))
		{
			$contentData['content_type'] = 'team_post';
		}

		if ($this->canUploadAndManageAttachment($team, $category, $null, $viewingUser))
		{
			$existing = is_string($tempHash) && strlen($tempHash) == 32;
			$output = array(
				'hash' => $existing ? $tempHash : md5(uniqid('', true)),
				'content_type' => $contentData['content_type'],
				'content_data' => $contentData
			);
			if ($existing)
			{
				$attachmentModel = Nobita_Teams_Container::getModel('XenForo_Model_Attachment');
				$output['attachments'] = $attachmentModel->prepareAttachments(
					$attachmentModel->getAttachmentsByTempHash($tempHash)
				);
			}

			return $output;
		}
		else
		{
			return false;
		}
	}

	public function updateRibbonAssociations(array $team, array $category = null)
	{
		if ($category === null)
		{
			$category = $this->_getCategoryModel()->getCategoryById($team['team_category_id']);
		}

		if ($team['team_category_id'] != $category['team_category_id'])
		{
			return; // nothing
		}

		if ($team['ribbon_display_class'] == '')
		{
			return;
		}

		$ribbonClasses = @unserialize($category['ribbon_styling']);
		if (is_array($ribbonClasses))
		{

			if (!in_array($team['ribbon_display_class'], $ribbonClasses))
			{
				$this->_getDb()->update('xf_team_profile', array('ribbon_display_class' => '', 'ribbon_text' => ''),
					'team_id = ' . $this->_getDb()->quote($team['team_id'])
				);
			}
		}
	}

	/**
	 * Define the tab information to guest when viewing team.
	 * Return bool true|false
	 */
	public function assertDisplayInfoTabtoGuest(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'editAny')
			|| XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'deleteAny')
		)
		{
			// global permission!
			return false;
		}

		if (!$this->getTeamMemberRecord($team['team_id'], $viewingUser)
			|| $this->isTeamMemberAwaitingConfirm($team['team_id'], $viewingUser))
		{
			if ($this->isOpen($team))
			{
				return Nobita_Teams_Option::get('defaultInfoGuest');
			}
			else
			{
				return true;
			}
		}

		return false;
	}

	public function canManageTabs(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id']) return false;

		return $this->isTeamOwner($team, $viewingUser);
	}

	/* MASS ALERTS */
	public function canSendMassAlerts(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		$messageLength = Nobita_Teams_Option::get('massMessageLength');
		if (!$messageLength)
		{
			// disable this feature
			return false;
		}

		if($this->isTeamOwner($team, $viewingUser))
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'massAlert');
	}

	public function massAlert(array $team, $message = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'] || empty($message))
		{
			return false;
		}

		$users = $this->_getMemberModel()->getMembersByTeamId($team['team_id'], array(
			'alert' => 1,
			'member_state' => 'accept'
		));

		foreach ($users as $user)
		{
			if ($viewingUser['user_id'] == $user['user_id'])
			{
				continue;
			}

			XenForo_Model_Alert::alert($user['user_id'],
				$viewingUser['user_id'], $viewingUser['username'],
				'team', $team['team_id'],
				'mass_alert', array('alert_message' => $message)
			);
		}
	}

	public function logTeamView($teamId)
	{
		$this->_getDb()->query('
			INSERT ' . (XenForo_Application::get('options')->enableInsertDelayed ? 'DELAYED' : '') . ' INTO xf_team_view
				(team_id)
			VALUES
				(?)
		', $teamId);
	}

	public function updateTeamViews()
	{
		$db = $this->_getDb();

		$db->query('
			UPDATE xf_team
			INNER JOIN (
				SELECT team_id, COUNT(*) AS total
				FROM xf_team_view
				GROUP BY team_id
			) AS xf_tv ON (xf_tv.team_id = xf_team.team_id)
			SET xf_team.view_count = xf_team.view_count + xf_tv.total
		');

		$db->query('DELETE FROM xf_team_view');
	}

	public function updateManageTeamCountForUser($userId)
	{
		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
		$db = $this->_getDb();

		$staffIds = $memberRoleModel->getStaffIds();
		$teamCount = 0;

		if (empty($staffIds))
		{
			$teamCount = $this->countTeams(array(
				'user_id' => $userId,
				'team_state' => 'visible',
			));
		}
		else
		{
			$teamCount = $db->fetchOne('
				SELECT COUNT(*)
				FROM xf_team_member
				WHERE member_role_id IN (' . $db->quote($staffIds) . ')
					AND user_id = ?
			', array($userId));
		}

		$db->query("
			UPDATE xf_user
			SET manage_team_count = ?
			WHERE user_id = ?
		", array($teamCount, $userId));
	}
}
