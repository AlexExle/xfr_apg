<?php

class Brivium_CreResIntegration_EventListeners_Listener
{
	public static function brcActionHandler(array &$actions)
	{
		$actions['ResourceGetPurchased'] = 'Brivium_CreResIntegration_ActionHandler_ResourceGetPurchased_ActionHandler';
		$actions['ResourcePurchased'] = 'Brivium_CreResIntegration_ActionHandler_ResourcePurchased_ActionHandler';
	}

	public static function threadViewPostDispatch($controller, $response, $controllerName, $action)
	{
		if (!($response instanceof XenForo_ControllerResponse_View))
		{
			return;
		}

		if ($response->viewName != 'XenForo_ViewPublic_Thread_View' || empty($response->params['resource']))
		{
			return;
		}
		$resource = $response->params['resource'];
		if (!$resource)
		{
			return;
		}

		$resourceModel = XenForo_Model::create('XenResource_Model_Resource');
		$purchasedModel = XenForo_Model::create('Brivium_CreResIntegration_Model_Purchased');
		if(!$response->params['canByPassDownload'] = $resourceModel->canDownloadWithoutPurchase($resource, $resource)){
			$response->params['resource']['purchased'] = $purchasedModel->checkResourcePurchased($resource['resource_id'], XenForo_Visitor::getUserId());
		}
	}
}