<?php

class Nobita_Teams_TagHandler_Event extends XenForo_TagHandler_Abstract
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
			throw new Exception("Context must be a event and a team or just a team");
		}

		$visitor = XenForo_Visitor::getInstance();

		if (!empty($context['event_id']))
		{
			$event = $context;
			$team = $parentContext;
		}
		else
		{
			$event = null;
			$team = $context;
		}

		if ($event)
		{
			if ($event['user_id'] == $visitor['user_id']
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
			'edit' => Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event')->canEditTags($event, $team, $team),
			'removeOthers' => $removeOthers,
			'minTotal' => $minTags
		);
	}

	public function getBasicContent($id)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event')->getEventById($id);
	}

	public function getContentDate(array $content)
	{
		return $content['publish_date'];
	}

	public function getContentVisibility(array $content)
	{
		return true;
	}

	public function updateContentTagCache(array $content, array $cache)
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Event');
		$dw->setExistingData($content['event_id']);
		$dw->set('tags', $cache);
		$dw->save();
	}

	public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event')->getEventsByIds($ids, array(
			'join' => Nobita_Teams_Model_Event::FETCH_TEAM
					| Nobita_Teams_Model_Event::FETCH_USER
		));
	}

	public function canViewResult(array $result, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event')->canViewEventAndContainer($result, $result, $result, $null, $viewingUser);
	}

	public function prepareResult(array $result, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event')->prepareEvent($result, $result, $result, $viewingUser);
	}

	public function renderResult(XenForo_View $view, array $result)
	{
		return $view->createTemplateObject('Team_search_result_event', array(
			'event' => $result,
			'team' => $result
		));
	}
}
