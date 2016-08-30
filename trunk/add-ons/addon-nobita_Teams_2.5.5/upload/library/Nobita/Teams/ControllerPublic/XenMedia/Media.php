<?php

class Nobita_Teams_ControllerPublic_XenMedia_Media extends Nobita_Teams_ControllerPublic_Abstract
{
	protected function _preDispatch($action)
	{
		if (!Nobita_Teams_AddOnChecker::getInstance()->isXenMediaExistsAndActive())
		{
			throw $this->getNoPermissionResponseException();
		}

		return parent::_preDispatch($action);
	}

	public function actionIndex()
	{
		$mediaId = $this->_input->filterSingle('media_id', XenForo_Input::UINT);
		if ($mediaId)
		{
			return $this->responseReroute(__CLASS__, 'view');
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
			$this->_buildLink(TEAM_ROUTE_PREFIX)
		);
	}

	public function actionView()
	{
		$mediaId = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		$controllerRequest = new Zend_Controller_Request_Http();
		$controllerRequest->setParam('media_id', $mediaId);

		$routeMatch = new XenForo_RouteMatch();
		$controllerResponse = new Zend_Controller_Response_Http();

		$mediaController = new XenGallery_ControllerPublic_Media($controllerRequest, $controllerResponse, $routeMatch);
		$mediaController->preDispatch('view', get_class($mediaController));

		$controllerResponse = $mediaController->{'actionView'}();

		$mediaParams = $controllerResponse->params;

		if (isset($mediaParams['media']['social_group_id']))
		{
			list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable($mediaParams['media']['social_group_id']);

			// make sure that visitor can view the tabs PHOTOS
			// and all content belong to.

			$teamMediaModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_XenMedia_Media');
			if (!$teamMediaModel->canViewMedia($mediaParams['media'], $team, $category, $error))
			{
				// Fixed the bug user can view media on closed team or secret team
				if (Nobita_Teams_Option::get('showPhotosOnIndex') && $team['privacy_state'] != 'open')
				{
					return $this->responseError(new XenForo_Phrase('Teams_you_should_a_member_to_view_photo', array(
						'group_link' => $this->_buildLink('canonical:' . TEAM_ROUTE_PREFIX, $team),
						'group_title' => $team['title']
					)));
				}
				else
				{
					throw $this->getErrorOrNoPermissionResponseException($error);
				}
			}
		}
		else
		{
			// move to index
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$this->_buildLink(TEAM_ROUTE_PREFIX)
			);
		}

		return $this->_getTeamHelper()->getTeamViewWrapper('photos', $team, $category,
			$this->responseView('XenGallery_ViewPublic_Media_View', 'Team_xenmedia_view', array_merge($mediaParams, array(
				'team' => $team,
				'category' => $category
			)))
		);
	}

	public function actionAdd()
	{
		list ($team, $category) = $this->_getTeamValidAndViewable();

		return $this->_getMediaEditOrResponse($team, $category);
	}

	protected function _getMediaEditOrResponse(array $team, array $category)
	{
		$this->_assertViewableMediaTab($team, $category);
		$xenCatId = Nobita_Teams_Option::get('XenMediaCategoryId');

		$mediaCategoryModel = Nobita_Teams_Container::getModel('XenGallery_Model_Category');
		$mediaModel = Nobita_Teams_Container::getModel('XenGallery_Model_Media');

		$mediaCategory = $mediaCategoryModel->getCategoryById($xenCatId);

		if (!$mediaCategory)
		{
			throw $this->getNoPermissionResponseException();
		}
		$mediaCategory = $mediaCategoryModel->prepareCategory($mediaCategory);

		$teamMediaModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_XenMedia_Media');
		if (!$teamMediaModel->canAddMedia($team, $category, $mediaCategory, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
		$container = $mediaCategory;

		$mediaSites = Nobita_Teams_Container::getModel('XenForo_Model_BbCode')->getAllBbCodeMediaSites();
		$allowedSites = XenForo_Application::getOptions()->xengalleryMediaSites;

		foreach ($mediaSites AS $key => $mediaSite)
		{
			if (!in_array($mediaSite['media_site_id'], $allowedSites))
			{
				unset ($mediaSites[$key]);
			}
		}

		$viewParams = array(
			'team' => $team,
			'category' => $category,

			'container' => $container,
			'mediaSites' => $mediaSites,
			'imageUploadParams' => $mediaModel->getAttachmentParams($container),
			'videoUploadParams' => $mediaModel->getAttachmentParams($container, 'video'),
			'imageUploadConstraints' => $mediaModel->getUploadConstraints(),
			'videoUploadConstraints' => $mediaModel->getUploadConstraints('video'),
			'canUploadImage' => $container['canUploadImage'],
			'canUploadVideo' => false,
			'canEmbedVideo' => $container['canEmbedVideo'],
			'mediaCategoryId' => $mediaCategory['category_id'],

			'groupId' => $team['team_id']
		);

		return $this->_getTeamHelper()->getTeamViewWrapper('photos', $team, $category,
			$this->responseView('Nobita_Teams_ViewPublic_XenMedia_Add', 'Team_xenmedia_add', $viewParams)
		);
	}

	protected function _getTeamValidAndViewable()
	{
		$teamId = $this->_input->filterSingle('group_id', XenForo_Input::UINT);

		list ($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable($teamId);
		$this->_request->setParam('team_id', $team['team_id']);

		return array($team, $category);
	}

	protected function _assertViewableMediaTab(array $team, array $category)
	{
		if (!$this->_getTeamModel()->canViewTabAndContainer('photos', $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}

}
