<?php

class Brivium_Credits_ViewPublic_Credits_PurchaseCredits extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		if(!empty($this->_params['formPurchaseTemplateName'])){
			$this->_params['contentHtml'] = $this->_renderer->createTemplateObject($this->_params['formPurchaseTemplateName'], $this->_params);
		}
	}
}