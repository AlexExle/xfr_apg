<?php
class Brivium_Credits_Payment_PayPal_Route_Prefix_Payment implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('Brivium_Credits_Payment_PayPal_ControllerPublic_Payment', $routePath, 'BR_credits');
	}
}