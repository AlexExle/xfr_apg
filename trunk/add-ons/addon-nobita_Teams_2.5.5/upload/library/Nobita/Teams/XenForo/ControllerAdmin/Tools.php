<?php

if(!Nobita_Teams_AddOnChecker::getInstance()->isActive('nobita_AvatarAlive')) {
	require_once ADDON_GROUP_DIR.'/ThirdParties/Facade.php';
	require_once ADDON_GROUP_DIR.'/ThirdParties/Factory.php';
	require_once ADDON_GROUP_DIR.'/ThirdParties/Iterator.php';
}

class Nobita_Teams_XenForo_ControllerAdmin_Tools extends XFCP_Nobita_Teams_XenForo_ControllerAdmin_Tools
{
	public function actionRebuildGroups()
	{
		$this->_routeMatch->setSections('applications');
		$this->assertAdminPermission('rebuildCache');

		/* @var $searchModel XenForo_Model_Search */
		$searchModel = Nobita_Teams_Container::getModel('XenForo_Model_Search');

		$searchContentTypeOptions = array();
		foreach ($searchModel->getSearchDataHandlers() AS $contentType => $handler)
		{
			$searchContentTypeOptions[$contentType] = $handler->getSearchContentTypePhrase();
		}

		$viewParams = array(
			'searchContentTypes' => $searchContentTypeOptions,
			'success' => $this->_input->filterSingle('success', XenForo_Input::BOOLEAN)
		);

		$containerParams = array(
			'hasManualDeferred' => Nobita_Teams_Container::getModel('XenForo_Model_Deferred')->countRunnableDeferreds(true)
		);
		return $this->responseView('XenForo_ViewAdmin_Tools_Rebuild', 'Team_tools_rebuild', $viewParams, $containerParams);
	}

	public function actionTriggerDeferred()
	{
		$input = $this->_input->filter(array(
			'cache' => XenForo_Input::STRING,
			'options' => XenForo_Input::ARRAY_SIMPLE,
		));
		$cache = $this->_input->filterSingle('cache', XenForo_Input::STRING);
		$response = parent::actionTriggerDeferred();

		if (strpos($cache, 'Nobita_Teams') !== false)
		{
			$this->_request->setParam('redirect',
				XenForo_Link::buildAdminLink('tools/rebuild/groups', false, array('success' => 1))
			);
		}

		return $response;
	}

	public function actionGroupsClearCache()
	{
		$facade = new File_Iterator_Facade;
		$files = $facade->getFilesAsArray(XenForo_Helper_File::getInternalDataPath().'/groups/');

		$deletedCount = 0;
		if(!empty($files))
		{
			foreach($files as $fileOrDir)
			{
				if(is_file($fileOrDir))
				{
					$deletedCount++;
				}

				@unlink($fileOrDir);
			}
		}

		$optionId = 'nobita_Teams';
		$optionGroup = Nobita_Teams_Container::getModel('XenForo_Model_Option')->getOptionGroupById($optionId);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('options/list', $optionGroup, array(
				'fileCount' => $deletedCount
			))
		);
	}
}
