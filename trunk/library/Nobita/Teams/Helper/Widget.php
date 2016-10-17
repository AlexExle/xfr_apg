<?php

class Nobita_Teams_Helper_Widget
{
	const MOST_MEMBERS	= 'member_count';
	const NEW_GROUPS	= 'team_date';
	const MOST_VIEWED	= 'view_count';

	const LAST_ACTIVITY = 'last_updated';


	const RANDOM 		= 'random';

	protected static $_modelCache = array();

	public static function getFeaturedGroupsWidget($searchCategoryIds, array $fetchOptions = array())
	{
		$featuredCount = Nobita_Teams_Option::get('featureCount');

		if (empty($featuredCount))
		{
			return array();
		}

		if (!is_array($searchCategoryIds))
		{
			$searchCategoryIds = array($searchCategoryIds);
		}

		if (empty($searchCategoryIds))
		{
			return array();
		}

		$fetchOptions['order'] = self::RANDOM;
		$fetchOptions['limit'] = $featuredCount;

		if (!isset($fetchOptions['join']))
		{
			$fetchOptions = array_merge($fetchOptions, self::_getDefaultGroupFetchOptions());
		}

		$groupModel = self::_getModelCache('Nobita_Teams_Model_Team');
		$groups = $groupModel->getFeaturedTeamsInCategories($searchCategoryIds, $fetchOptions);

		return self::filterUnviewableAndPrepareTeams($groups);
	}

	public static function getSuggestedGroupsWidget($limit = null, array $viewableCategoryIds = null)
	{
		$userModel = self::_getModelCache('XenForo_Model_User');

		$memberModel = self::_getModelCache('Nobita_Teams_Model_Member');
		$groupModel = self::_getModelCache('Nobita_Teams_Model_Team');

		if (is_null($limit))
		{
			$limit = Nobita_Teams_Option::get('teamsPerPage');
		}

		if (is_null($viewableCategoryIds))
		{
			$viewableCategoryIds = self::_getModelCache('Nobita_Teams_Model_Category')->getViewableCategories();
			$viewableCategoryIds = array_keys($viewableCategoryIds);
		}

		$user = XenForo_Visitor::getInstance()->toArray();

		$userId = $user['user_id'];
		if (empty($userId))
		{
			return array();
		}

		$exTeamIds = $memberModel->getTeamIdsByUserId($userId);
		$followingUserIds = $userModel->getFollowingIdsForUser($userId);

		$suggestedGroupIds = array();

		if (empty($followingUserIds))
		{
			// user did not following any other members
			if (!empty($exTeamIds))
			{
				$suggestedGroupIds = $memberModel->getTeamIdsByUserIdsAndNotIn($exTeamIds, array());
			}
		}
		else
		{
			$suggestedGroupIds = $memberModel->getTeamIdsByUserIdsAndNotIn($exTeamIds, $followingUserIds);
		}

		$fetchOptions = array_merge(self::_getDefaultGroupFetchOptions(), array(
			'limit' => $limit
		));

		$fetchOptions['join'] |= Nobita_Teams_Model_Team::FETCH_USER;

		$conditions = array(
			'deleted' => false,
			'moderated' => false,
			'team_category_id' => $viewableCategoryIds
		);

		if ($suggestedGroupIds)
		{
			$fetchOptions['order'] = 'last_updated';
			$fetchOptions['direction'] = 'desc';

			$groups = $groupModel->getTeams(
				array_merge($conditions, array(
					'team_id' => $suggestedGroupIds,
				)),
				array_merge($fetchOptions, array(
					'order' => 'last_updated',
					'direction' => 'desc'
				))
			);
		}
		else
		{
			$groups = $groupModel->getTeams(
				array_merge($conditions, array(
					'team_id_ex' => $exTeamIds
				)),
				array_merge($fetchOptions, array(
					'order' => 'random'
				))
			);
		}

		return self::filterUnviewableAndPrepareTeams($groups);
	}

	public static function getMostGroupsWidgetByType($type, array $criteria = array(), array $fetchOptions = array())
	{
		$showGroups = Nobita_Teams_Option::get('topTeamsCount');

		if (empty($fetchOptions))
		{
			$fetchOptions = array_merge(self::_getDefaultGroupFetchOptions(),array(
				'limit' => $showGroups,
				'direction' => 'desc'
			));
		}

		if (empty($fetchOptions['limit']) && empty($fetchOptions['perPage']))
		{
			return array();
		}

		$supported = array(self::MOST_MEMBERS, self::NEW_GROUPS, self::LAST_ACTIVITY, self::MOST_VIEWED);
		if (!in_array($type, $supported))
		{
			return array();
		}

		$fetchOptions['order'] = $type;

		$teamModel = self::_getModelCache('Nobita_Teams_Model_Team');

		$groups = $teamModel->getTeams($criteria, $fetchOptions);
		if (!$groups)
		{
			return array();
		}

		return self::filterUnviewableAndPrepareTeams($groups);
	}

	public static function filterUnviewableAndPrepareTeams(array $teams)
	{
		$userId = XenForo_Visitor::getUserId();
		if(!empty($userId))
		{
			$teamIds = XenForo_Application::arrayColumn($teams, 'team_id');
			$teamIds = array_unique($teamIds);

			$memberModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');
			$members = $memberModel->getMembers(array(
				'user_id' => $userId,
				'team_id' => $teamIds
			), array(
				'join' => Nobita_Teams_Model_Member::FETCH_USER
			));

			foreach($members as $member)
			{
				$cacheKey = $memberModel->getTeamMemberCacheKey($member['team_id'], $member['user_id']);
				if(!XenForo_Application::isRegistered($cacheKey))
				{
					XenForo_Application::set($cacheKey, $member);
				}
			}
		}

		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		$teams = $teamModel->filterUnviewableTeams($teams);
		$teams = $teamModel->prepareTeams($teams);

		return $teams;
	}

	protected static function _getDefaultGroupFetchOptions()
	{
		return array(
			'join' => Nobita_Teams_Model_Team::FETCH_PROFILE
					| Nobita_Teams_Model_Team::FETCH_PRIVACY
					| Nobita_Teams_Model_Team::FETCH_CATEGORY,
			'memberUserId' => XenForo_Visitor::getUserId()
		);
	}

	protected static function _getModelCache($class)
	{
		if (!isset(self::$_modelCache[$class]))
		{
			self::$_modelCache[$class] = Nobita_Teams_Container::getModel($class);
		}

		return self::$_modelCache[$class];
	}

}
