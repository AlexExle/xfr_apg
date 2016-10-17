<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/
  
class XFA_Tournament_ViewPublic_Tournament_Add extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$description = (isset($this->_params['tournament']['description']) ? $this->_params['tournament']['description'] : '');

		$this->_params['descriptionEditorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'description', $description,
			array(
				'extraClass'  => 'NoAutoComplete',
				'autoSaveUrl' => ''
			)
		);
		
		$rules = (isset($this->_params['tournament']['rules']) ? $this->_params['tournament']['rules'] : '');

		$this->_params['rulesEditorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'rules', $rules,
			array(
				'extraClass'  => 'NoAutoComplete',
				'autoSaveUrl' => ''
			)
		);
	}
}