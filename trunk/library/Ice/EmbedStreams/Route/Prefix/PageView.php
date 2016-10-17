<?php
class Ice_EmbedStreams_Route_Prefix_PageView implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('Ice_EmbedStreams_ControllerPublic_Index', $routePath, 'livestreams');
	}
}