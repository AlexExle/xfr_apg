<?php

class Nobita_Teams_XenForo_ControllerAdmin_Option extends XFCP_Nobita_Teams_XenForo_ControllerAdmin_Option
{
	public function actionSave()
	{
		$defaultRules = $this->getHelper('Editor')->getMessageText('default_rules', $this->_input);
		$defaultRules = XenForo_Helper_String::autoLinkBbCode($defaultRules);

		Nobita_Teams_Listener::$defaultRulesData = array(
			'rules' => $defaultRules,
			'controller' => $this
		);

		return parent::actionSave();
	}

	public function actionList()
	{
		$response = parent::actionList();
		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$viewParams = $response->params;
			if (!empty($viewParams['group']) && $viewParams['group']['group_id'] == 'nobita_Teams')
			{
				$this->_routeMatch->setSections('applications');
			}
		}

		return $response;
	}

}