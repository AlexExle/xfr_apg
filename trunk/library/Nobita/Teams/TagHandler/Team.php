<?php

class Nobita_Teams_TagHandler_Team extends XenForo_TagHandler_Abstract
{
	public function getPermissionsFromContext(array $context, array $parentContext = null)
	{
		$minTags = null;
		if (array_key_exists('min_tags', $context))
		{
			$minTags = $context['min_tags'];
		}
		elseif (array_key_exists('min_tags', $parentContext))
		{
			$minTags = $parentContext['min_tags'];
		}

		if ($minTags === null)
		{
			throw new Exception("Context must be a group and a category or just a category");
		}

		$visitor = XenForo_Visitor::getInstance();

		if (!empty($context['team_id']))
		{
			$team = $context;
			$category = $parentContext;
		}
		else
		{
			$team = null;
			$category = $context;
		}

		if ($team)
		{
			if ($team['user_id'] == $visitor['user_id']
				&& XenForo_Permission::hasPermission($visitor['permissions'], 'Teams', 'manageOwnTag')
			)
			{
				$removeOthers = true;
			}
			else
			{
				$removeOthers = XenForo_Permission::hasPermission($visitor['permissions'], 'Teams', 'manageAnyTag');
			}
		}
		else
		{
			$removeOthers = false;
		}

		return array(
			'edit' => Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canEditTags($team, $category),
			'removeOthers' => $removeOthers,
			'minTotal' => $minTags
		);
	}

	public function getBasicContent($id)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getTeamById($id);
	}

	public function getContentDate(array $content)
	{
		return $content['team_date'];
	}

	public function getContentVisibility(array $content)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->isVisible($content);
	}

	public function updateContentTagCache(array $content, array $cache)
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
		$dw->setExistingData($content['team_id']);
		$dw->set('tags', $cache);
		$dw->save();
	}

	public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->getTeamsByIds($ids, array(
			'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
					| Nobita_Teams_Model_Team::FETCH_PROFILE
					| Nobita_Teams_Model_Team::FETCH_PRIVACY
					| Nobita_Teams_Model_Team::FETCH_USER
		));
	}

	public function canViewResult(array $result, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canViewTeamAndContainer($result, $result, $null, $viewingUser);
	}

	public function prepareResult(array $result, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->prepareTeam($result, $result, $viewingUser);
	}

	public function renderResult(XenForo_View $view, array $result)
	{
		return $view->createTemplateObject('Team_search_result_team', array(
			'team' => $result,
			'category' => array(
				'team_category_id' => $result['team_category_id'],
				'category_title' => $result['category_title']
			)
		));
	}

}
