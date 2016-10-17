<?php
class Brivium_Credits_Route_Prefix_CreditPayment implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('Brivium_Credits_ControllerPublic_CreditPayment', $routePath, 'BR_credits');
	}
}