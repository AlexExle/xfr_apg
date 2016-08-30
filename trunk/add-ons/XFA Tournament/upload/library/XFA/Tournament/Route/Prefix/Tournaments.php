<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Route_Prefix_Tournaments implements XenForo_Route_Interface
{	
	protected $_subComponents = array(
		'categories' => array(
			'intId'         => 'tournament_category_id',
			'title'         => 'category_title',
			'actionPrefix'  => 'category'
		)
	);    
    
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$controller = 'XFA_Tournament_ControllerPublic_Tournament';
		
		$action = $router->getSubComponentAction($this->_subComponents, $routePath, $request, $controller);

		if ($action === false)
		{
			$action = $router->resolveActionWithIntegerParam($routePath, $request, 'tournament_id');
		}
		
		return $router->getRouteMatch($controller, $action, 'tournaments');
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		$link = XenForo_Link::buildSubComponentLink($this->_subComponents, $outputPrefix, $action, $extension, $data);
		if (!$link)
		{
			if ($data && isset($data['tournament_title']))
			{
				$data['title'] = $data['tournament_title'];
			}

			$link = XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'tournament_id', 'title');
		}
		return $link;
	}    
}