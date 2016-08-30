<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/
  
class XFA_Tournament_ViewPublic_Tournament_View extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		XenForo_Application::set('view', $this);

		$bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));
		$this->_params['tournament']['description'] = $bbCodeParser->render($this->_params['tournament']['description']);
		$this->_params['tournament']['rules'] = $bbCodeParser->render($this->_params['tournament']['rules']);
	}
}