<?php

class Nobita_Teams_ControllerPublic_Ajax extends Nobita_Teams_ControllerPublic_Abstract
{
	public function actionNewsFeed()
	{
		$session = XenForo_Application::getSession();
		$isRobot = $session->get('isRobot');
		if(!$isRobot) {
			// Normal requesting? .. Need Http POST method only.
			$this->_assertPostOnly();
		}

		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage = Nobita_Teams_Option::get('messagesPerPage');

		$dateStart = $this->_input->filterSingle('date', XenForo_Input::UINT);

		$newsFeedModel = $this->_getNewsFeedModel();

		$conditions = array();
		if($dateStart)
		{
			$conditions['event_date'] = array('<', $dateStart);
		}

		$newsFeeds = $newsFeedModel->getNewsFeedForTeam($team['team_id'], $conditions, array(
			'page' => $page,
			'perPage' => $perPage,
		));

		$newsFeeds = $newsFeedModel->fillOutNewsFeedItems($newsFeeds);
		$total = $newsFeedModel->countNewsFeedForTeam($team['team_id']);

		$pageNavParams = array(
			'team_id' => $team['team_id']
		);

		if($dateStart)
		{
			$pageNavParams['date'] = $dateStart;
		}

		$this->canonicalizePageNumber($page, $perPage, $total, Nobita_Teams_Link::buildTeamLink('ajax/news-feed'));
		$maxPage = ceil($total / $perPage);
		$isNextPage = ($page < $maxPage) ? true : false;

		$nextParams = array_merge(array(
			'page' => ($page + 1)
		), $pageNavParams);
		$nextPageUrl = Nobita_Teams_Link::buildTeamLink('canonical:ajax/news-feed', false, $nextParams);

		$viewParams = array(
			'newsFeeds' => $newsFeeds,
			'page' => $page,
			'perPage' => $perPage,
			'total' => $total,
			'pageNavParams' => $pageNavParams,
			'isNextPage' => $isNextPage,
			'nextPageUrl' => $nextPageUrl,
			'isRobot' => $isRobot,

			'team' => $team,
			'category' => $category,
			'categoryBreadcrumbs' => $this->_getCategoryModel()->getCategoryBreadcrumb($category),
			'canViewAttachments' => $this->_getTeamModel()->canViewAttachments($team, $category),
		);

		return $this->responseView('Nobita_Teams_ViewPublic_Ajax_NewsFeed', 'Team_newsfeed', $viewParams);
	}
}
