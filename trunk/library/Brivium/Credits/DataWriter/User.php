<?php
class Brivium_Credits_DataWriter_User extends XFCP_Brivium_Credits_DataWriter_User
{
	protected function _getFields()
	{
		$result = parent::_getFields();
		$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
		if($currencies){
			foreach($currencies AS $currency){
				$result['xf_user'][$currency['column']] = array('type' => self::TYPE_FLOAT, 'default' => 0);
			}
		}
		return $result;
	}

    protected function _preSave()
    {
        if (isset($GLOBALS['BRC_ControllerAdmin_User'])) {
            $controller = $GLOBALS['BRC_ControllerAdmin_User'];
            if (XenForo_Visitor::getInstance()->hasAdminPermission('BRC_editUserCredits'))
			{
				$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
				foreach($currencies AS $currency){
					$credits = 0;
					$credits = $controller->getInput()->filterSingle($currency['column'], XenForo_Input::NUM);
					$this->set($currency['column'], $credits);
				}
			}
        }
        parent::_preSave();
    }
}