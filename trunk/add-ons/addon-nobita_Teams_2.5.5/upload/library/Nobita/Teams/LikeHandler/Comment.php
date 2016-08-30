<?php

class Nobita_Teams_LikeHandler_Comment extends XenForo_LikeHandler_Abstract
{
	//TODO: allow user can like an comment
	public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Comment');
		$dw->setExistingData($contentId);
		$dw->set('likes', $dw->get('likes') + $adjustAmount);
		$dw->set('like_users', $latestLikes);
		$dw->save();
	}

	public function getContentData(array $contentIds, array $viewingUser)
	{
		$commentModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		$comments = $commentModel->getCommentsByIds($contentIds);

		$teamIds = array();
		foreach ($comments as $comment)
		{
			$teamIds[] = $comment['team_id'];
		}

		$teamIds = array_unique($teamIds);
		$teams = $teamModel->getTeamsByIds($teamIds, array(
			'join' => Nobita_Teams_Model_Team::FETCH_PRIVACY
					| Nobita_Teams_Model_Team::FETCH_PROFILE
					| Nobita_Teams_Model_Team::FETCH_CATEGORY
		));

		foreach ($comments as $commentId => $comment)
		{
			$team = isset($teams[$comment['team_id']]) ? $teams[$comment['team_id']] : false;
			if (!$team)
			{
				unset($comments[$commentId]);
			}

			if (!$teamModel->canViewTeamAndContainer($team, $team, $null, $viewingUser))
			{
				unset($comments[$commentId]);
			}
		}

		return $comments;
	}

	public function getListTemplateName()
	{
		return 'news_feed_item_team_comment_like';
	}

	public function batchUpdateContentUser($oldUserId, $newUserId, $oldUsername, $newUsername)
	{
		$postModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');
		$postModel->batchUpdateLikeUser($oldUserId, $newUserId, $oldUsername, $newUsername);
	}

}
