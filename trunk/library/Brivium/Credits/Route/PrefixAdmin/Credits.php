<?php
class Brivium_Credits_Route_PrefixAdmin_Credits implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('Brivium_Credits_ControllerAdmin_Credit', $routePath, 'BR_credits');
	}
}