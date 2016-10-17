<?php

class Nobita_Teams_Deferred_Deferred extends XenForo_Deferred_Abstract
{
	public function execute(array $deferred, array $data, $targetRunTime, &$status)
	{
		$deferredModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Deferred');

		XenForo_Application::defer(
			Nobita_Teams_Model_Deferred::DEFERRED_CLASS, array(), 'nobita_Teams_deferred', false, XenForo_Application::$time + 120
		);

		try
		{
			$hasMore = $deferredModel->run($targetRunTime);
		}
		catch(Exception $e) 
		{
			$hasMore = false;
		}

		if ($hasMore)
		{
			return $data;
		}
		else
		{
			return false;
		}
	}

	public function canCancel()
	{
		return false;
	}

}