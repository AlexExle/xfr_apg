<?php

class Nobita_Teams_AlertHandler_Comment extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$commentModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment');
		$postModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post');
		$eventModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Event');

		$comments = $commentModel->getCommentsByIds($contentIds, array(
			'join' => Nobita_Teams_Model_Comment::FETCH_TEAM
			 		| Nobita_Teams_Model_Comment::FETCH_USER
		));

		$postIds = array();
		$eventIds = array();

		foreach($comments as &$comment)
		{
			if($comment['content_type'] == Nobita_Teams_Model_Comment::CONTENT_TYPE_POST)
			{
				$postIds[] = $comment['content_id'];
			}
			elseif($comment['content_type'] == Nobita_Teams_Model_Comment::CONTENT_TYPE_EVENT)
			{
				$eventIds[] = $comment['content_id'];
			}

			$comment = $commentModel->prepareComment($comment, $comment, $comment, $viewingUser);
		}

		if($postIds)
		{
			$posts = $postModel->getPostsByIds($postIds, array(
				'join' => Nobita_Teams_Model_Post::FETCH_TEAM
			));

			foreach($comments as &$comment)
			{
				if($comment['content_type'] != 'post')
				{
					continue;
				}

				if(isset($posts[$comment['content_id']]))
				{
					$comment['postData'] = $posts[$comment['content_id']];
				}
			}
		}

		if($eventIds)
		{
			$events = $eventModel->getEventsByIds($eventIds, array(
				'join' => Nobita_Teams_Model_Event::FETCH_TEAM
			));

			foreach($comments as &$comment)
			{
				if($comment['content_type'] != 'event')
				{
					continue;
				}

				if(isset($events[$comment['content_id']]))
				{
					$comment['postData'] = $events[$comment['content_id']];
				}
			}
		}

		return $comments;
	}

	public function canViewAlert(array $alert, $content, array $viewingUser)
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Comment')->canViewComment(
			$content, $content['teamData'], $content, $null, $viewingUser
		);
	}
}
