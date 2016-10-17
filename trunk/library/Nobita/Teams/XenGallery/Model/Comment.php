<?php

class Nobita_Teams_XenGallery_Model_Comment extends XFCP_Nobita_Teams_XenGallery_Model_Comment
{
	public function getCommentsForBlockOrFeed($limit, array $fetchOptions)
	{
		if (!Nobita_Teams_AddOnChecker::getInstance()->isXenMediaExistsAndActive())
		{
			return parent::getCommentsForBlockOrFeed($limit, $fetchOptions);
		}

		// we on the way to override this function
		// by default: have no way to extend this

		$db = $this->_getDb();

		$viewingUser = $this->standardizeViewingUserReference();

		$privacyClause = '1=1';
		$noAlbums = '';
		if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'xengallery', 'viewOverride'))
		{
			$userId = 0;
			if (isset($fetchOptions['privacyUserId']))
			{
				$userId = $fetchOptions['privacyUserId'];
			}

			if (empty($fetchOptions['viewAlbums']))
			{
				$noAlbums = 'AND media.album_id = 0 AND album.album_id = 0';
			}

			$categoryClause = '';
			if (!empty($fetchOptions['viewCategoryIds']))
			{
				$categoryClause = 'OR IF(media.category_id > 0, media.category_id IN (' . $db->quote($fetchOptions['viewCategoryIds']) . '), NULL)';
			}

			$membersClause = '';
			if ($userId > 0)
			{
				$membersClause = 'OR media.media_privacy = \'members\'';
			}

			$privacyClause = '
				private.private_user_id IS NOT NULL
					OR shared.shared_user_id IS NOT NULL
					OR media.media_privacy = \'public\'
					OR albumviewperm.access_type = \'public\'
				' . $membersClause . '
				' . $categoryClause;
		}

		$whereClause = '';
		if (!empty($fetchOptions['comment_id']))
		{
			if (is_array($fetchOptions['comment_id']))
			{
				$whereClause = ' AND comment.comment_id IN(' . $db->quote($fetchOptions['comment_id']) . ')';
			}
			else
			{
				$whereClause = ' AND comment.comment_id = ' . $db->quote($fetchOptions['comment_id']);
			}
		}

		$joinGroup = '
		LEFT JOIN xf_team AS xf_team ON
			(xf_team.team_id = media.social_group_id)
		';

		$comments = $this->fetchAllKeyed($this->limitQueryResults('
			SELECT comment.*, media.media_title, media.media_type, media.media_id, media.media_state, media.attachment_id, media.media_tag, media.category_id,
				album.album_title, album.album_description, albumviewperm.access_type, albumviewperm.share_users, album.album_id, album.album_state, album.album_user_id, album.album_thumbnail_date, user.*, container.album_state AS albumstate,
				attachment.data_id, ' . XenForo_Model_Attachment::$dataColumns . ',xf_team.team_id, xf_team.team_state, xf_team.privacy_state
			FROM xengallery_comment AS comment
			LEFT JOIN xengallery_media AS media ON
				(comment.content_id = media.media_id AND comment.content_type = \'media\')
			' . $joinGroup . '
			LEFT JOIN xf_attachment AS attachment ON
				(attachment.attachment_id = media.attachment_id)
			LEFT JOIN xf_attachment_data AS data ON
				(data.data_id = attachment.data_id)
			LEFT JOIN xengallery_album AS album ON
				(comment.content_id = album.album_id AND comment.content_type = \'album\')
			LEFT JOIN xengallery_album_permission AS albumviewperm ON
				(album.album_id = albumviewperm.album_id AND albumviewperm.permission = \'view\')
			LEFT JOIN xengallery_album AS container ON
				(container.album_id = media.album_id)
			LEFT JOIN xf_user AS user ON
				(comment.user_id = user.user_id)
			LEFT JOIN xengallery_shared_map AS shared ON
				(shared.album_id = COALESCE(album.album_id, media.album_id) AND shared.shared_user_id = ' . $db->quote($fetchOptions['privacyUserId']) . ')
			LEFT JOIN xengallery_private_map AS private ON
				(private.album_id = COALESCE(album.album_id, media.album_id) AND private.private_user_id = ' .  $db->quote($fetchOptions['privacyUserId']) .')
			WHERE (container.album_state IS NULL OR container.album_state = \'visible\' OR album.album_state = \'visible\')
				AND (media.media_state IS NULL OR media.media_state = \'visible\')
				AND (album.album_state IS NULL OR album.album_state = \'visible\')
				AND ('. $privacyClause .')
				AND comment.comment_state = \'visible\' '
					. $noAlbums . $whereClause . '
			ORDER BY comment.comment_date DESC
			', $limit
		), 'comment_id');

		if ($comments)
		{
			foreach($comments as $commentId => $comment)
			{
				if (!empty($comment['team_id']) && $comment['privacy_state'] != Nobita_Teams_Model_Team::PRIVACY_OPEN)
				{
					// going to check permission on closed and secret
					if (!Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canViewTeamAndContainer($comment, $comment))
					{
						unset($comments[$commentId]);
					}
				}
			}
		}

		return $comments;
	}
}
