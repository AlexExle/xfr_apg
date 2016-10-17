<?php

class Brivium_Credits_ViewAdmin_Credits_Transactions extends XenForo_ViewAdmin_Base
{
	public function renderRaw()
	{
		$transactions = $this->_params['transactions'];
		$this->_response->setHeader('Content-type', 'application/octet-stream', true);
		$this->setDownloadFileName($transactions['filename']);
		$this->_response->setHeader('ETag', $transactions['export_date'], true);
		$this->_response->setHeader('Content-Length', $transactions['file_size'], true);
		$this->_response->setHeader('X-Content-Type-Options', 'nosniff');
		return new XenForo_FileOutput($this->_params['transactionsFile']);
	}
}