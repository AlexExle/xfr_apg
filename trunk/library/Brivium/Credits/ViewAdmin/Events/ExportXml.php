<?php

class Brivium_Credits_ViewAdmin_Events_ExportXml extends XenForo_ViewAdmin_Base
{
	public function renderXml()
	{
		$this->setDownloadFileName('brivium-credits-events.xml');
		return $this->_params['xml']->saveXml();
	}
}