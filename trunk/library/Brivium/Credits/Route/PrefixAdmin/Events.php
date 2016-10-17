<?php

/**
 * Route prefix handler for events in the admin control panel.
 *
 * @package Brivium_Credits
 */
class Brivium_Credits_Route_PrefixAdmin_Events implements XenForo_Route_Interface
{
	/**
	 * Match a specific route for an already matched prefix.
	 *
	 * @see XenForo_Route_Interface::match()
	 */
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'event_id');
		return $router->getRouteMatch('Brivium_Credits_ControllerAdmin_Event', $action, 'BR_credits');
	}

	/**
	 * Method to build a link to the specified page/action with the provided
	 * data and params.
	 *
	 * @see XenForo_Route_BuilderInterface
	 */
	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'event_id', 'action_id');
	}
}