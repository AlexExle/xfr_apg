<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Template
{
	protected static $_canViewTournamentsTemplatePerms = null;    
    
	public static function navigationTabs(&$extraTabs, $selectedTabId)
	{
        if (XenForo_Visitor::getInstance()->hasPermission('xfa_tourn', 'canView'))
		{	
    		$extraTabs['tournaments'] = array(
    			'title'         => new XenForo_Phrase('xfa_tourn_tournaments'),
    			'href'          => XenForo_Link::buildPublicLink('full:tournaments'),
				'linksTemplate' => 'xfa_tourn_tab_links',    			
    			'position'      => 'middle'
    		);
		}
	} 
	
	public static function template_create(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
    	/* "Cache" perm to avoid recalling has permission */
		if (self::$_canViewTournamentsTemplatePerms === null)
		{
			self::$_canViewTournamentsTemplatePerms = XenForo_Visitor::getInstance()->hasPermission('xfa_tourn', 'canView');
		}

        /* Need to check if not already present in the params (might be in the document pages) */
		if (!isset($params['canViewTournaments']))
		{
			$params['canViewTournaments'] = self::$_canViewTournamentsTemplatePerms;
		}
	}	
}