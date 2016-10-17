<?php

class Brivium_Credits_ViewPublic_Credits_WithDraw extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		if(!empty($this->_params['formWithdrawTemplateName'])){
			$this->_params['contentHtml'] = $this->_renderer->createTemplateObject($this->_params['formWithdrawTemplateName'], $this->_params);
		}
	}
}