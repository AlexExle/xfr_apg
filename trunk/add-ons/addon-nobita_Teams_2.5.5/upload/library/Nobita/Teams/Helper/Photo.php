<?php

class Nobita_Teams_Helper_Photo
{
	/**
	 * @var XenForo_Controller
	 */
	protected static $controller;

	/**
	 * @var XenForo_Input
	 */
	protected static $input;

	/**
	 * The current request of groupId
	 *
	 * @var integer
	 */
	public static $groupId;

	public static function responseView(XenForo_Controller $controller, XenForo_Input $input, array $params = array())
	{
		self::$controller = $controller;
		self::$input = $input;
		self::$groupId = $params['team']['team_id'];

		$provider = Nobita_Teams_Option::get('photoProvider');

		if ($provider == 'sonnb_xengallery')
		{
			$params = array_merge($params, self::_sonnbPhotoIndexParams($params['team'], $params['category']));
		}
		else if ($provider == 'XenGallery')
		{
			$params = array_merge($params, self::_xenMediaPhotoIndexParams($params['team'], $params['category']));
		}

		// we reset.. keep it safe
		self::$groupId = null;

		return $controller->getHelper('Nobita_Teams_ControllerHelper_Team')
			->getTeamViewWrapper('photos', $params['team'], $params['category'],
				$controller->responseView($params['viewName'], $params['templateName'], $params)
			);
	}

	protected static function _sonnbPhotoIndexParams($team, $category)
	{
		$albumModel = self::$controller->getModelFromCache('Nobita_Teams_Model_XenGallery_Album');

		$options = XenForo_Application::get('options');
		$oldLoadedContentOption = $options->sonnbXG_loadedContent;

		// temporary reset loadedContent
		// should be revert when i got params
		$options->set('sonnbXG_loadedContent', 'album');

		$controllerRequest = new Zend_Controller_Request_Http();

		$routeMatch = new XenForo_RouteMatch();
		$controllerResponse = new Zend_Controller_Response_Http();

		$galleryController = new sonnb_XenGallery_ControllerPublic_XenGallery($controllerRequest, $controllerResponse, $routeMatch);
		$galleryController->preDispatch('index', get_class($galleryController));

		$controllerResponse = $galleryController->{'actionIndex'}();

		try
		{
			$galleryParams = $controllerResponse->params;
		}
		catch(Exception $e) {
			// sometime if not return of view (redirect, or error)
			// so should be catch that
			XenForo_Error::logException($e);

			throw self::$controller->responseException(
				self::$controller->responseError(new XenForo_Phrase('Teams_failed_to_loading_gallery_content'))
			);
		}

		$options->set('sonnbXG_loadedContent', $oldLoadedContentOption);
		unset($oldLoadedContentOption);

		$pageRoute = TEAM_ROUTE_PREFIX . '/photos';

		self::$controller->canonicalizePageNumber(
			$galleryParams['page'], $galleryParams['albumsPerPage'], $galleryParams['totalAlbums'], $pageRoute, $team
		);
		self::$controller->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink($pageRoute, $team, array('page' => $galleryParams['page']))
		);

		if (isset($galleryParams['canCreateAlbum']))
		{
			// no... dont using XenGallery permission
			// it should be depend of team as well
			unset($galleryParams['canCreateAlbum']);
		}
		$galleryParams['canCreateAlbum'] = $albumModel->canCreateAlbum();

		return array_merge($galleryParams, array(
			'provider' => 'sonnb_xengallery',
			'pageTitle' => new XenForo_Phrase('Teams_gallery'),
			'templateName' => 'Team_photo',
			'viewName' => 'sonnb_XenGallery_ViewPublic_Album_List',
			'pageRoute' => $pageRoute
		));
	}

	protected static function _xenMediaPhotoIndexParams($team, $category)
	{
		/* @var $teamMediaModel Nobita_Teams_Model_XenMedia_Media */
		$teamMediaModel = self::$controller->getModelFromCache('Nobita_Teams_Model_XenMedia_Media');

		/* @var $categoryModel XenGallery_Model_Category */
		$categoryModel = self::$controller->getModelFromCache('XenGallery_Model_Category');
		$mediaCategoryId = Nobita_Teams_Option::get('XenMediaCategoryId');

		$controllerRequest = new Zend_Controller_Request_Http();

		$routeMatch = new XenForo_RouteMatch();
		$controllerResponse = new Zend_Controller_Response_Http();

		$mediaController = new XenGallery_ControllerPublic_Media($controllerRequest, $controllerResponse, $routeMatch);
		$mediaController->preDispatch('index', get_class($mediaController));

		$controllerResponse = $mediaController->{'actionIndex'}();

		$mediaParams = false;
		$category = false;
		try
		{
			$params = $controllerResponse->params;

			if (isset($params['categories'][$mediaCategoryId]))
			{
				$category = $params['categories'][$mediaCategoryId];
			}

			if ($controllerResponse->subView instanceof XenForo_ControllerResponse_View)
			{
				// XenMedia using subView index.
				$mediaParams = $controllerResponse->subView->params;
			}
		}
		catch(Exception $e) {
			// sometime if not return of view (redirect, or error)
			// so should be catch that
			XenForo_Error::logException($e);

			throw self::$controller->responseException(
				self::$controller->responseError(new XenForo_Phrase('Teams_failed_to_loading_media_content'))
			);
		}

		if (!$category)
		{
			return array(
				'templateName' => 'Team_photo',
				'viewName' => '',
				'provider' => 'XenGallery',
				'noPermission' => true,
				'pageTitle' => $team['title']
			);
		}

		if (!$mediaParams)
		{
			XenForo_Error::logError("Can not get media content.");

			throw self::$controller->responseException(
				self::$controller->responseError(new XenForo_Phrase('Teams_failed_to_loading_media_content'))
			);
		}

		$pageRoute = TEAM_ROUTE_PREFIX . '/photos';
		self::$controller->canonicalizePageNumber(
			$mediaParams['page'], $mediaParams['perPage'], $mediaParams['mediaCount'], $pageRoute, $team
		);
		self::$controller->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink($pageRoute, $team, array('page' => $mediaParams['page']))
		);

		if (isset($mediaParams['canAddMedia']))
		{
			unset($mediaParams['canAddMedia']);
		}
		$mediaParams['canAddMedia'] = $teamMediaModel->canAddMedia($team, $category, $category);

		return array_merge($mediaParams, array(
			'templateName' => 'Team_photo',
			'viewName' => '',
			'provider' => 'XenGallery',
			'pageRoute' => $pageRoute,
			'pageTitle' => new XenForo_Phrase('Teams_media')
		));
	}


}
