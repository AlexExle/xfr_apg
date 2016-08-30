<?php

class Nobita_Teams_sonnb_XenGallery_Model_Album extends XFCP_Nobita_Teams_sonnb_XenGallery_Model_Album
{
	public $forceJoinGroup = false;

	public function prepareAlbumConditions(array $conditions, array &$fetchOptions)
	{
		$showPhoto = Nobita_Teams_Option::get('showPhotosOnIndex');
		$requestGroupId = Nobita_Teams_Helper_Photo::$groupId;

		if (!is_null($requestGroupId))
		{
			// like media index. do not include any media
			// from social groups
			$conditions['team_id'] = $requestGroupId;
		}

		$result = parent::prepareAlbumConditions($conditions, $fetchOptions);
		$sqlConditions = array($result);

		$db = $this->_getDb();

		if (isset($conditions['team_id']))
		{
			// good.. if 0 value special so get all albums which does not
			// belong to any teams
			$sqlConditions[] = 'album.team_id = ' . $db->quote($conditions['team_id']);
		}

		if (count($sqlConditions) > 1)
		{
			return $this->getConditionsForClause($sqlConditions);
		}
		else
		{
			return $result;
		}
	}

	public function prepareAlbumFetchOptions(array $fetchOptions)
	{
		$response = parent::prepareAlbumFetchOptions($fetchOptions);
		extract($response);

		if(isset($fetchOptions['nobita_Teams_joinTeam']) || $this->forceJoinGroup)
		{
			$selectFields .= ',team.team_id,team.title as team_title,team.team_state,team.privacy_state as team_privacy_state,
				team.user_id as team_user_id,team.team_category_id';
			$joinTables .= '
				LEFT JOIN xf_team AS team ON (team.team_id = album.team_id)';
		}

		return compact('selectFields', 'joinTables', 'orderClause');
	}

	public function prepareAlbum(array $album, array $fetchOptions = array(), $viewingUser = array())
	{
		$album = parent::prepareAlbum($album, $fetchOptions, $viewingUser);
		if(array_key_exists('team_title', $album) && $album['team_title'])
		{
			$album['teamData'] = $this->_extractTeamDataFromAlbum($album);
		}

		return $album;
	}

	public function canViewAlbum(array $album, &$errorPhraseKey = '', array $viewingUser = null, $hash = null)
	{
		if($team = $this->_extractTeamDataFromAlbum($album))
		{
			if(!Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canViewTeamAndContainer($team, $team))
			{
				return false;
			}
		}

		return parent::canViewAlbum($album, $errorPhraseKey, $viewingUser, $hash);
	}

	private function _extractTeamDataFromAlbum(array $album)
	{
		if(array_key_exists('team_title', $album) && $album['team_title'])
		{
			return array(
				'team_id' => $album['team_id'],
				'title' => $album['team_title'],
				'team_state' => $album['team_state'],
				'privacy_state' => $album['team_privacy_state'],
				'user_id' => $album['team_user_id'],
				'team_category_id' => $album['team_category_id']
			);
		}

		return array();
	}
}
