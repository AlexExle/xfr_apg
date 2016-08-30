<?php

class Nobita_Teams_ControllerPublic_XenGallery_Album extends Nobita_Teams_ControllerPublic_XenGallery_Abstract
{
	public function actionIndex()
	{
		$albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);

		$controllerRequest = new Zend_Controller_Request_Http();

		$controllerRequest->setParam('album_id', $albumId);

		$routeMatch = new XenForo_RouteMatch();
		$controllerResponse = new Zend_Controller_Response_Http();

		$albumController = new sonnb_XenGallery_ControllerPublic_XenGallery_Album(
			$controllerRequest, $controllerResponse, $routeMatch
		);

		$albumController->preDispatch('index', get_class($albumController));
		$controllerResponse = $albumController->{'actionIndex'}();

		$albumParams = false;
		try
		{
			$albumParams = $controllerResponse->params;
		}
		catch(Exception $e) {}

		if (!$albumParams)
		{
			return $this->responseError(new XenForo_Phrase('Teams_failed_to_loading_gallery_content'));
		}

		if (empty($albumParams['album']['team_id']))
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
				$this->_buildLink('gallery/albums', $albumParams['album'])
			);
		}
		$album = $albumParams['album'];

		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable($album['team_id']);
		if (!$this->_getTeamModel()->canViewTabAndContainer('photos', $team, $category, $error))
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

		// sometime call from _postDispatch did not work
		$this->_getTeamModel()->logTeamView($team['team_id']);

		$viewParams = array_merge($albumParams, array(
			'team' => $team,
			'teamCategory' => $category
		));

		return $this->_getTeamHelper()->getTeamViewWrapper('photos', $team, $category,
			$this->responseView('sonnb_XenGallery_ViewPublic_Album_View', 'Team_xengallery_album_view', $viewParams)
		);
	}

	public function actionCreate()
	{
		list($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable();

		if (!$this->_getTeamAlbumModel()->canCreateAlbum($error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$albumPrivacy = array(
			'allow_view' => 'everyone',
			'allow_comment' => 'everyone',
			'allow_download' => 'everyone',
			'allow_add_photo' => 'none',
			'allow_add_video' => 'none'
		);

		$album = array(
			'album_id' => 0,
			'title' => '',
			'description' => '',
			'album_state' => 'visible',
			'category_id' => 0,
			'collection_id' => 0,
			'album_location' => '',
			'cover_content_id' => 0,

			'album_privacy' => $albumPrivacy
		);

		return $this->_getAlbumEditOrResponse($album, $team, $category);
	}

	protected function _getAlbumEditOrResponse(array $album, array $team, array $category)
	{
		$this->_assertCanViewTab($team, $category);
		$this->_assertCanUploadContents();

		$xenOptions = XenForo_Application::getOptions();

		$contents = array();
		$totalPhotos = 0;

		$contentPerPage = $xenOptions->sonnbXG_photoPerPage;
		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));

		$contentDataParams = array(
			'hash' => md5(uniqid('', true)),
			'album_id' => $album['album_id']
		);

		if (!empty($album['album_id']))
		{
			$totalPhotos = $album['content_count'];
			$conditions = $this->_getContentModel()->getPermissionBasedContentFetchConditions();
			$contentFetchOptions = array(
				'join' => sonnb_XenGallery_Model_Content::FETCH_DATA
							| sonnb_XenGallery_Model_Content::FETCH_PHOTO
							| sonnb_XenGallery_Model_Content::FETCH_VIDEO,
				'page' => $page,
				'perPage' => $contentPerPage
			);

			$contents = $this->_getContentModel()->getContentsByAlbumId($album['album_id'], $conditions, $contentFetchOptions);
			$contents = $this->_getContentModel()->prepareContents($contents, $contentFetchOptions);

			foreach ($contents as $contentId => $content)
			{
				if (!$this->_getContentModel()->canViewContent($content))
				{
					unset($contents[$contentId]);
				}
			}
		}

		$viewParams = array(
			'team' => $team,
			'category' => $category,

			'album' => $album,

			'contents' => $contents,
			'page' => $page,
			'perPage' => $contentPerPage,
			'pageNavParams' => array(),
			'totalPhotos' => $totalPhotos,
			'canEmbedVideos' => $this->_getGalleryModel()->canEmbedVideo(),
			'disableLocation' => $xenOptions->sonnb_XG_disableLocation,

			'contentDataParams' => $contentDataParams,
			'photoDataConstraints' => $this->_getPhotoModel()->getPhotoDataConstraints($xenOptions->sonnbXG_enableResize ? true: false),

			'categories' => $this->_getCategoryModel()->getAllCachedCategories(),
			'group_albumPrivacy' => 1
		);

		return $this->_getTeamHelper()->getTeamViewWrapper('photos', $team, $category,
			$this->responseView('sonnb_XenGallery_ViewPublic_Album_Edit', 'sonnb_xengallery_album_edit', $viewParams)
		);
	}

	public function actionEdit()
	{
		$album = $this->_getAlbumOrError();
		if (!$album['canEdit'])
		{
			throw $this->_throwFriendlyNoPermission('sonnb_xengallery_you_do_not_have_permission_to_edit_this_album');
		}
		if (!empty($album['albumStreams']))
		{
			$album['stream_name'] = implode(', ', $album['albumStreams']);
		}

		list ($team, $category) = $this->_getTeamHelper()->assertTeamValidAndViewable($album['team_id']);
		return $this->_getAlbumEditOrResponse($album, $team, $category);
	}

	protected function _assertCanViewTab(array $team, array $category)
	{
		if (!$this->_getTeamModel()->canViewTabAndContainer('photos', $team, $category, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
	}

	protected function _getAlbumOrError($albumId = null, $fetchCover = false)
	{
		if ($albumId === null)
		{
			$albumId = $this->_input->filterSingle('album_id', XenForo_Input::UINT);
		}

		$fetchElements = $this->_getAlbumFetchElements();

		/* @var $galleryHelper sonnb_XenGallery_ControllerHelper_Gallery */
		$galleryHelper = $this->getHelper('sonnb_XenGallery_ControllerHelper_Gallery');

		$album = $galleryHelper->assertAlbumValidAndViewable($albumId, $fetchElements['options']);

		if ($fetchCover)
		{
			$album = $this->_getAlbumModel()->attachCoverToAlbum($album, $fetchElements['options']);
		}

		return $album;
	}

	protected function _getAlbumFetchElements(array $conditions = array())
	{
		$albumModel = $this->_getAlbumModel();
		$visitor = XenForo_Visitor::getInstance();

		$albumFetchConditions = $conditions + $albumModel->getPermissionBasedAlbumFetchConditions();

		$albumFetchOptions = array(
			'join' => sonnb_XenGallery_Model_Album::FETCH_USER | sonnb_XenGallery_Model_Album::FETCH_COVER_PHOTO,
			'likeUserId' => $visitor['user_id'],
			'watchUserId' => $visitor['user_id'],
			'followingUserId' => $visitor['user_id']
		);

		return array(
			'conditions' => $albumFetchConditions,
			'options' => $albumFetchOptions
		);
	}

	protected function _getTeamAlbumModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_XenGallery_Album');
	}
}
