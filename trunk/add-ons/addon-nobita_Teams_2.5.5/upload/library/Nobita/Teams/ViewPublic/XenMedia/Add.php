<?php

class Nobita_Teams_ViewPublic_XenMedia_Add extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$template = $this->createTemplateObject('xengallery_media_add_form', $this->_params);
		$this->_params['mediaEntryArea'] = $template;
	}

}