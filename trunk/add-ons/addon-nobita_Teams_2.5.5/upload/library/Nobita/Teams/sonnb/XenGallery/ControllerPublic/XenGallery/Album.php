<?php

class Nobita_Teams_sonnb_XenGallery_ControllerPublic_XenGallery_Album extends XFCP_Nobita_Teams_sonnb_XenGallery_ControllerPublic_XenGallery_Album
{
	public function actionIndex()
	{
		$response = parent::actionIndex();
		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$params = $response->params;
			if (! empty($params['album']['team_id']))
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					$this->_buildLink(TEAM_ROUTE_PREFIX . '/albums', $params['album'])
				);
			}
		}

		return $response;
	}

	public function actionEdit()
	{
		$response = parent::actionEdit();
		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$params = $response->params;
			if (!empty($params['album']['team_id']))
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					$this->_buildLink(TEAM_ROUTE_PREFIX . '/albums/edit', $params['album'])
				);
			}
		}

		return $response;
	}

	public function actionPrivacy()
	{
		$this->_assertRegistrationRequired();
		$album = $this->_getAlbumOrError();

		if (!empty($album['team_id']))
		{
			$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
			$team = $teamModel->getTeamById($album['team_id']);

			if ($team)
			{
				// album on Team.. You can't change the privacy of album
				throw $this->getNoPermissionResponseException();
			}
		}

		return parent::actionPrivacy();
	}

	public function actionSave()
	{
		$GLOBALS[Nobita_Teams_Listener::XENGALLERY_CONTROLLERPUBLIC_ALBUM_ACTIONSAVE] = $this;

		return parent::actionSave();
	}

	public function SocialGroups_actionSave(XenForo_DataWriter $dw)
	{
		$teamId = $this->_input->filterSingle('team_id', XenForo_Input::UINT);
		if (empty($teamId))
		{
			return;
		}

		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
		$team = $teamModel->getFullTeamById($teamId, array(
			'join' => Nobita_Teams_Model_Team::FETCH_CATEGORY
		));

		if (!$team)
		{
			// not found? so using default XenGallery
			return;
		}

		if (!$teamModel->canViewTeamAndContainer($team, $team, $error))
		{
			// don't have permission to view Group
			return;
		}

		if (!$teamModel->canViewTabAndContainer('photos', $team, $team))
		{
			// disable this tab... so can't make any albums
			return;
		}

		$dw->set('team_id', $team['team_id']);
		unset($GLOBALS[Nobita_Teams_Listener::XENGALLERY_CONTROLLERPUBLIC_ALBUM_ACTIONSAVE]);
	}
}