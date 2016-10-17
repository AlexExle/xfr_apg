<?php
class Brivium_CreResIntegration_Route_PrefixAdmin_ResourceLog implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('Brivium_CreResIntegration_ControllerAdmin_ResourceLog', $routePath, 'BR_credits');
	}
}