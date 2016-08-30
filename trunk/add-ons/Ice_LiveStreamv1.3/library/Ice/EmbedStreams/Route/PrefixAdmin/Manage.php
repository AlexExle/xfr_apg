<?php

class Ice_EmbedStreams_Route_PrefixAdmin_Manage implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'stream_id');
	
		return $router->getRouteMatch('Ice_EmbedStreams_ControllerAdmin_Manage', $action, 'applications');
	}
	
	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'stream_id');
	}
	
}