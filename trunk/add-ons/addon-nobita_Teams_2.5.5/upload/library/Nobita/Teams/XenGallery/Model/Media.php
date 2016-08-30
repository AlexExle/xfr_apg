<?php

class Nobita_Teams_XenGallery_Model_Media extends XFCP_Nobita_Teams_XenGallery_Model_Media
{
	public function prepareMediaConditions(array $conditions, array &$fetchOptions)
	{
		$showPhoto = Nobita_Teams_Option::get('showPhotosOnIndex');
		if (empty(Nobita_Teams_Helper_Photo::$groupId))
		{
			// like media index. do not include any media
			// from social groups
			if (empty($showPhoto))
			{
				$conditions['social_group_id'] = 0;
			}
			else
			{
				$fetchOptions['joinGroup'] = true;
			}
		}
		else
		{
			// fetch media associate with special group
			$conditions['social_group_id'] = Nobita_Teams_Helper_Photo::$groupId;
		}

		$result = parent::prepareMediaConditions($conditions, $fetchOptions);
		$sqlConditions = array($result);

		if (isset($conditions['social_group_id']))
		{
			$sqlConditions[] = 'media.social_group_id = ' . $this->_getDb()->quote($conditions['social_group_id']);
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

	public function prepareMediaFetchOptions(array $fetchOptions, array $conditions = array())
	{
		$response = parent::prepareMediaFetchOptions($fetchOptions, $conditions);
		extract($response);

		if (isset($fetchOptions['joinGroup']) || !empty($conditions['social_group_id']))
		{
			$selectFields .=',xf_team.privacy_state as team_privacy_state';
			$joinTables .='
				LEFT JOIN xf_team AS xf_team ON (xf_team.team_id = media.social_group_id)
			';
		}

		return compact('selectFields', 'joinTables', 'orderClause');
	}

	public function canViewMediaItem(array $media, &$errorPhraseKey = '', array $viewingUser = null)
	{
		if (!empty($media['social_group_id']) && isset($media['team_user_id']))
		{
			return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->canViewTeam(
				$media, $media, $errorPhraseKey, $viewingUser
			);
		}

		return parent::canViewMediaItem($media, $errorPhraseKey, $viewingUser);
	}


}
