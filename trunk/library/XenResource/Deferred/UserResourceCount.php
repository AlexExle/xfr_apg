<?php

class XenResource_Deferred_UserResourceCount extends XenForo_Deferred_Abstract
{
	public function execute(array $deferred, array $data, $targetRunTime, &$status)
	{
		$data = array_merge(array(
			'position' => 0,
			'batch' => 50
		), $data);
		$data['batch'] = max(1, $data['batch']);

		/* @var $resourceModel XenResource_Model_Resource */
		$resourceModel = XenForo_Model::create('XenResource_Model_Resource');

		/* @var $userModel XenForo_Model_User */
		$userModel = XenForo_Model::create('XenForo_Model_User');

		$userIds = $userModel->getUserIdsInRange($data['position'], $data['batch']);
		if (sizeof($userIds) == 0)
		{
			return true;
		}

		$resourceModel->rebuildUserResourceCounts($userIds);

		$data['position'] = end($userIds);

		$actionPhrase = new XenForo_Phrase('rebuilding');
		$typePhrase = new XenForo_Phrase('resource_counts');
		$status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

		return $data;
	}

	public function canCancel()
	{
		return true;
	}
}