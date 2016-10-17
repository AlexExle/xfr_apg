<?php

// sometime when you disable addon
// this file still running so it make your board
// under risks. So redefined these conts
$constants = array(
	'TEAM_DATAREGISTRY_KEY' => 'Teams_group_perms',
	'TEAM_ROUTE_PREFIX' 	=> Nobita_Teams_Option::get('routePrefix')
);

foreach($constants as $defineName => $defineValue) {
	if (!defined($defineName)) define($defineName, $defineValue);
}

class Nobita_Teams_Helper_Activity
{
	protected $_activities 			= array();

	protected $_output 				= array();

	protected $_modelCache 			= array();
	protected $_defaultModelClasses = array(
		'banning' 			=> 'Nobita_Teams_Model_Banning',
		'category' 			=> 'Nobita_Teams_Model_Category',
		'comment' 			=> 'Nobita_Teams_Model_Comment',
		'event' 			=> 'Nobita_Teams_Model_Event',
		'member' 			=> 'Nobita_Teams_Model_Member',
		'post' 				=> 'Nobita_Teams_Model_Post',
		'team' 				=> 'Nobita_Teams_Model_Team',

		'xenmedia_media'	=> 'XenGallery_Model_Media',
		'forum'				=> 'XenForo_Model_Forum'
	);

	protected $_defaultModelCache 	= array();

	protected $_paramIds			= array();
	protected $_fetchOptions		= array();

	protected $_results 			= array();
	protected $_extraData			= array();

	public function __construct(array $activities)
	{
		$this->_activities = $activities;

		foreach($this->_defaultModelClasses as $modelKey => $modelName)
		{
			if (! class_exists($modelName))
			{
				// BUG: https://nobita.me/threads/374/
				continue;
			}

			$this->_defaultModelCache[$modelKey] = $this->_getModelCache($modelName);
		}
	}

	public function output()
	{
		if (!XenForo_Visitor::getInstance()->hasPermission('Teams', 'view'))
		{
			$this->_output = false;
			return $this->_output;
		}

		$fetchOptions = array();
		foreach($this->_activities as $key => $activity)
		{
			if (strpos($activity['controller_name'], 'Nobita_Teams_ControllerPublic') === false)
			{
				continue;
			}

			$this->_preOutput($activity);
		}

		if (!empty($this->_output))
		{
			return $this->_output;
		}

		$this->_postResultsToOutput();
		$this->_finalPostOutput();

		return $this->_output;
	}

	protected function _finalPostOutput()
	{
		if (empty($this->_results))
		{
			$this->_output = false;
			return $this->_output;
		}

		$output = array();

		foreach($this->_activities as $key => $activity)
		{
			$controllerName = str_replace('Nobita_Teams_ControllerPublic_', '', $activity['controller_name']);
			$controllerName = strtolower($controllerName);

			$params = $activity['params'];

			if (!isset($this->_results[$controllerName]))
			{
				continue;
			}

			$results = $this->_results[$controllerName];
			$extraData = isset($this->_extraData[$controllerName]) ? $this->_extraData[$controllerName] : array();

			if ($controllerName == 'xenmedia_media')
			{
				$methodOutput = '_outputXenMediaActivities';
			}
			else
			{
				$methodOutput = '_output'. ucfirst($controllerName).'Activities';
			}

			if (method_exists($this, $methodOutput))
			{
				$output[$key] = call_user_func_array(array($this, $methodOutput), array($results, $params, $extraData));
			}
		}

		$this->_output = $output;
	}

	protected function _getParamId($params, $key)
	{
		if (!is_array($params))
		{
			$params = array($params);
		}

		if (array_key_exists($key, $params) && !empty($params[$key]))
		{
			return $params[$key];
		}

		return null;
	}

	protected function _preOutput(array $activity)
	{
		$controllerName = str_replace('Nobita_Teams_ControllerPublic_', '', $activity['controller_name']);
		$params = $activity['params'];

		$controllerName = strtolower($controllerName);
		switch($controllerName)
		{
			case 'browsegroup':
				$this->_output[] = new XenForo_Phrase('Teams_browsing_groups');
				break;

			case 'category':
				if ($this->_getParamId($params, 'team_category_id'))
				{
					$this->_paramIds['category:getCategoriesByIds'][] = $params['team_category_id'];
				}
				break;

			case 'comment':
				if ($this->_getParamId($params, 'comment_id'))
				{
					$this->_paramIds['comment:getCommentsByIds'][] = $params['comment_id'];
					$this->_fetchOptions[$controllerName] = array(
						'join' => Nobita_Teams_Model_Comment::FETCH_TEAM
					);
				}
				break;

			case 'post':
				if ($this->_getParamId($params, 'post_id'))
				{
					$this->_paramIds['post:getPostsByIds'][] = $params['post_id'];
					$this->_fetchOptions[$controllerName] = array(
						'join' => Nobita_Teams_Model_Post::FETCH_TEAM
					);
				}
				break;

			case 'event':
				$this->_getEventParams($params, $controllerName);
				break;

			case 'member':
				if ($this->_getParamId($params, 'team_id'))
				{
					$this->_paramIds['member:ignored:replace_team_getTeamsByIds'][] = $params['team_id'];
					$this->_fetchOptions[$controllerName] = array(
						'join' => Nobita_Teams_Model_Team::FETCH_PRIVACY
							| Nobita_Teams_Model_Team::FETCH_PROFILE
							| Nobita_Teams_Model_Team::FETCH_CATEGORY
					);
				}
				break;

			case 'xenmedia_media':
				if ($this->_getParamId($params, 'media_id'))
				{
					$this->_paramIds['xenmedia_media:getMediaByIds:depends_team_getTeamsByIds'][] = $params['media_id'];
					$this->_fetchOptions[$controllerName] = array(
						'join' => Nobita_Teams_Model_Team::FETCH_PRIVACY
							| Nobita_Teams_Model_Team::FETCH_PROFILE
							| Nobita_Teams_Model_Team::FETCH_CATEGORY
					);
				}
				elseif ($this->_getParamId($params, 'team_id'))
				{
					$this->_paramIds['xenmedia_media:ignored:replace_team_getTeamsByIds'][] = $params['team_id'];
					$this->_fetchOptions[$controllerName] = array(
						'join' => Nobita_Teams_Model_Team::FETCH_PRIVACY
							| Nobita_Teams_Model_Team::FETCH_PROFILE
							| Nobita_Teams_Model_Team::FETCH_CATEGORY
					);
				}
				break;

			case 'team':
				$this->_getTeamParams($params, $controllerName);
				break;
			case 'forum':
				if ($this->_getParamId($params, 'node_id') AND $this->_getParamId($params, 'team_id'))
				{
					$this->_paramIds['forum:getForumsByIds:depends_team_getTeamsByIds'][] = $params['node_id'];
					$this->_fetchOptions[$controllerName] = array(
						'join' => Nobita_Teams_Model_Team::FETCH_PRIVACY
							| Nobita_Teams_Model_Team::FETCH_PROFILE
							| Nobita_Teams_Model_Team::FETCH_CATEGORY
					);

					$this->_extraData['forum']['teamIds'][] = $this->_getParamId($params, 'team_id');
				}
				break;
		}
	}

	protected function _outputForumActivities(array $results, array $params, array $extraData = array())
	{
		$nodeId = $this->_getParamId($params, 'node_id');
		$teamId = $this->_getParamId($params, 'team_id');

		if (isset($results[$nodeId]) AND isset($extraData[$teamId]))
		{
			$forum = $results[$nodeId];
			$team = $extraData[$teamId];

			$teamModel = $this->_defaultModelCache['team'];

			if ($teamModel->canViewTeamAndContainer($team, $team))
			{
				return array(
					new XenForo_Phrase('Teams_viewing_team_forum'),
					$forum['title'],
					XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/forums', $forum, array('id' => $team['team_id'])),
					false
				);
			}

			return new XenForo_Phrase('Teams_viewing_team');
		}
	}

	protected function _outputTeamActivities(array $results, array $params)
	{
		$teamId = $this->_getParamId($params, 'team_id');
		$customUrl = $this->_getParamId($params, 'custom_url');

		$team = false;
		if (isset($results[$teamId]))
		{
			$team = $results[$teamId];
		}
		elseif ($customUrl)
		{
			foreach($results as $teamId => $teamFilter)
			{
				if ($teamFilter['custom_url'] == $customUrl)
				{
					$team = $teamFilter;
					break;
				}
			}
		}

		if ($team)
		{
			if ($this->_defaultModelCache['team']->canViewTeam($team, $team))
			{
				return array(
					new XenForo_Phrase('Teams_viewing_team'),
					$team['title'],
					XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX, $team),
					false
				);
			}
			else
			{
				return new XenForo_Phrase('Teams_viewing_team');
			}
		}

		return new XenForo_Phrase('Teams_viewing_team_list');
	}

	protected function _outputXenMediaActivities(array $results, array $params, array $extraData = array())
	{
		if (! class_exists('XenMedia_Model_Media'))
		{
			return new XenForo_Phrase('viewing_unknown_page');
		}

		if ($this->_getParamId($params, 'media_id'))
		{
			$mediaId = $params['media_id'];
			if (isset($results[$mediaId]))
			{
				$media = $results[$mediaId];

				if (isset($extraData[$media['social_group_id']]))
				{
					$group = $extraData[$media['social_group_id']];

					$groupMediaModel = $this->_getModelCache('Nobita_Teams_Model_XenMedia_Media');
					if ($groupMediaModel->canViewMedia($media, $group, $group))
					{
						return array(
							new XenForo_Phrase('Teams_viewing_group_media'),
							$media['media_title'],
							XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/media', $media),
							false
						);
					}
					elseif ($this->_defaultModelCache['team']->canViewTeamAndContainer($group, $group))
					{
						return array(
							new XenForo_Phrase('Teams_viewing_team'),
							$group['title'],
							XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX, $group),
							false
						);
					}
					else
					{
						return new XenForo_Phrase('Teams_viewing_team');
					}
				}
			}
		}
		elseif($this->_getParamId($params, 'team_id'))
		{
			$teamId = $params['team_id'];

			if (isset($results[$teamId]))
			{
				$group = $results[$teamId];

				if ($this->_defaultModelCache['team']->canViewTeam($group, $group))
				{
					return array(
						new XenForo_Phrase('Teams_viewing_team'),
						$group['title'],
						XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX, $group),
						false
					);
				}
				else
				{
					return new XenForo_Phrase('Teams_viewing_team');
				}
			}
		}

		return new XenForo_Phrase('viewing_unknown_page');
	}

	protected function _outputMemberActivities(array $results, array $params)
	{
		if (isset($params['team_id']))
		{
			$teamId = $params['team_id'];
			if (isset($results[$teamId]))
			{
				$team = $results[$teamId];
				if ($this->_defaultModelCache['team']->canViewTeam($team, $team))
				{
					return array(
						new XenForo_Phrase('Teams_viewing_team'),
						$team['title'],
						XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX, $team),
						false
					);
				}
				else
				{
					return new XenForo_Phrase('Teams_viewing_team');
				}
			}
		}

		return new XenForo_Phrase('viewing_unknown_page');
	}

	/**
	 * Print out current page of user viewing. By default alway return user viewing group,
	 * it will return more details if you have permission to viewable
	 *
	 * @param array $results
	 * @param array $params
	 *
	 * @return mixed
	 */
	protected function _outputEventActivities(array $results, array $params)
	{
		if (isset($params['team_id']))
		{
			$teamId = $params['team_id'];
			if (isset($results[$teamId]))
			{
				$team = $results[$teamId];
				if ($this->_defaultModelCache['team']->canViewTeam($team, $team))
				{
					return array(
						new XenForo_Phrase('Teams_viewing_team'),
						$team['title'],
						XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX, $team),
						false
					);
				}
			}
		}
		elseif (isset($params['event_id']))
		{
			$eventId = $params['event_id'];
			if (isset($results[$eventId]))
			{
				$event = $results[$eventId];

				if ($this->_defaultModelCache['event']->canViewEvent($event, $event, $event))
				{
					return array(
						new XenForo_Phrase('Teams_viewing_event'),
						$event['event_title'],
						XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/events', $event),
						false
					);
				}
				elseif ($this->_defaultModelCache['team']->canViewTeam($event, $event))
				{
					return array(
						new XenForo_Phrase('Teams_viewing_team'),
						$event['title'],
						XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX, $event),
						false
					);
				}
			}
		}

		return new XenForo_Phrase('Teams_viewing_team');
	}

	/**
	 * Print out current page of user viewing. By default alway return user viewing category,
	 * it will return more details if you have permission to viewable
	 *
	 * @param array $results
	 * @param array $params
	 *
	 * @return mixed
	 */
	protected function _outputCategoryActivities(array $results, array $params)
	{
		if (isset($results[$params['team_category_id']]))
		{
			$category = $results[$params['team_category_id']];

			if ($this->_defaultModelCache['category']->canViewCategory($category))
			{
				return array(
					new XenForo_Phrase('Teams_viewing_group_category'),
					$category['category_title'],
					XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX . '/categories', $category),
					false
				);
			}
		}

		return new XenForo_Phrase('Teams_viewing_group_category');
	}

	/**
	 * Print out current page of user viewing. By default alway return user viewing group,
	 * it will return more details if you have permission to viewable
	 *
	 * @param array $results
	 * @param array $params
	 *
	 * @return mixed
	 */
	protected function _outputCommentActivities(array $results, array $params)
	{
		if (isset($results[$params['comment_id']]))
		{
			$comment = $results[$params['comment_id']];

			if ($this->_defaultModelCache['team']->canViewTeam($comment, $comment))
			{
				return array(
					new XenForo_Phrase('Teams_viewing_team'),
					$comment['title'],
					XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX, $comment),
					false
				);
			}
		}

		return new XenForo_Phrase('Teams_viewing_team');
	}

	/**
	 * Print out current page of user viewing. By default alway return user viewing group,
	 * it will return more details if you have permission to viewable
	 *
	 * @param array $results
	 * @param array $params
	 *
	 * @return mixed
	 */
	protected function _outputPostActivities(array $results, array $params)
	{
		if (isset($results[$params['post_id']]))
		{
			$post = $results[$params['post_id']];

			if ($this->_defaultModelCache['team']->canViewTeam($post, $post))
			{
				return array(
					new XenForo_Phrase('Teams_viewing_team'),
					$post['title'],
					XenForo_Link::buildPublicLink(TEAM_ROUTE_PREFIX, $post),
					false
				);
			}
		}

		return new XenForo_Phrase('Teams_viewing_team');
	}

	protected function _getTeamParams(array $params, $controllerName)
	{
		$teamId = $this->_getParamId($params, 'team_id');
		$customUrl = $this->_getParamId($params, 'custom_url');

		if ($teamId)
		{
			$this->_paramIds['team:getTeamsByIds'][] = $teamId;
		}
		elseif ($customUrl)
		{
			$this->_paramIds['team:getTeamsByUrls'][] = $customUrl;
		}
		else
		{
			$this->_paramIds['groupController'] = true;
		}

		$this->_fetchOptions[$controllerName] = array(
			'join' => Nobita_Teams_Model_Team::FETCH_PRIVACY
					| Nobita_Teams_Model_Team::FETCH_PROFILE
					| Nobita_Teams_Model_Team::FETCH_CATEGORY
		);
	}

	protected function _getEventParams(array $params, $controllerName)
	{
		if (isset($params['team_id']))
		{
			$this->_paramIds['event:getEventsByIds:replace_team_getTeamsByIds'][] = $params['team_id'];
			$this->_fetchOptions[$controllerName] = array(
				'join' => Nobita_Teams_Model_Team::FETCH_PRIVACY
						| Nobita_Teams_Model_Team::FETCH_PROFILE
						| Nobita_Teams_Model_Team::FETCH_CATEGORY
			);
		}
		elseif (isset($params['custom_url']))
		{
			//way
		}
		elseif (isset($params['event_id']))
		{
			$this->_paramIds['event:getEventsByIds'][] = $params['event_id'];
			$this->_fetchOptions[$controllerName] = array(
				'join' => Nobita_Teams_Model_Event::FETCH_TEAM
			);
		}
	}

	protected function _postResultsToOutput()
	{
		if (empty($this->_paramIds))
		{
			return $this->_results;
		}

		if (isset($this->_paramIds['groupController']))
		{
			$this->_results['team'] = array(
				'groupController' => true
			); // got it

			unset($this->_paramIds['groupController']);
			return; // break
		}

		foreach($this->_paramIds as $paramKey => $paramValues)
		{
			$paramKeys = explode(':', $paramKey);

			// the key of ServiceModel.
			// @_see $_defaultModelClasses
			$modelKey = array_shift($paramKeys);
			$callbackFunction = array_shift($paramKeys);

			$originalModelKey = $modelKey;

			$extraData = array();
			if (!empty($paramKeys))
			{
				// wel.. it depend on other as well
				$parts = explode('_', array_shift($paramKeys));

				if ($parts[0] == 'replace')
				{
					// using the replace func as well
					$modelKey = $parts[1];
					$callbackFunction = $parts[2];
				}
				elseif ($parts[0] == 'depends')
				{
					// [0] => model key
					// [1] => method
					$extraData = array($parts[1], $parts[2]);
				}
			}

			$model = $this->_defaultModelCache[$modelKey];
			$fetchOptions = isset($this->_fetchOptions[$originalModelKey]) ? $this->_fetchOptions[$originalModelKey] : array();

			try
			{
				$this->_results[$originalModelKey] = call_user_func_array(
					array($model, $callbackFunction), array($paramValues, $fetchOptions)
				);
			}
			catch(Exception $e) {}

			$groupIds = array();
			if (!empty($this->_results[$originalModelKey]) && $extraData)
			{
				foreach($this->_results[$originalModelKey] as $result)
				{
					if (!empty($result['social_group_id']))
					{
						$groupIds[$originalModelKey][] = $result['social_group_id'];
					}
				}

				if ($originalModelKey == 'forum')
				{
					$groupIds = array_merge($this->_extraData[$originalModelKey]['teamIds'], $groupIds);
					unset($this->_extraData[$originalModelKey]['teamIds']);
				}
				$groupIds = array_unique($groupIds);

				$extraModel = $this->_defaultModelCache[$extraData[0]];

				$groups = call_user_func_array(array($extraModel, $extraData[1]), array($groupIds, $fetchOptions));
				$this->_extraData[$originalModelKey] = $groups;
			}
		}

		return $this->_results;
	}

	protected function _getModelCache($class)
	{
		if (!isset($this->_modelCache[$class]))
		{
			$this->_modelCache[$class] = Nobita_Teams_Container::getModel($class);
		}

		return $this->_modelCache[$class];
	}

}
