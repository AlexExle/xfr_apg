<?php

class Brivium_CreResIntegration_DataWriter_Resource extends XFCP_Brivium_CreResIntegration_DataWriter_Resource
{
	protected function _getFields()
	{
		$result = parent::_getFields();
		$result['xf_resource']['credit_price'] = array('type' => self::TYPE_FLOAT, 'default' => 0);
		$result['xf_resource']['brcri_currency_id'] = array('type' => self::TYPE_UINT, 	'default' => 0);
		return $result;
	}

	protected function _preSave()
	{
		$preSave = parent::_preSave();
		if (isset($GLOBALS['BRCRI_ControllerPublic_Resource'])) {
			$controller = $GLOBALS['BRCRI_ControllerPublic_Resource'];
			if(!$this->get('is_fileless')){
				$creditPrice = $controller->getInput()->filterSingle('credit_price', XenForo_Input::UNUM);
				$currencyId = $controller->getInput()->filterSingle('brcri_currency_id', XenForo_Input::UINT);
				if($creditPrice==0 || $currencyId == 0){
					$currencyId = 0;
					$creditPrice = 0;
				}
				if($creditPrice && $currencyId){
					$this->set('credit_price', $creditPrice);
					$this->set('brcri_currency_id', $currencyId);
				}
			}
			unset($GLOBALS['BRCRI_ControllerPublic_Resource']);
        }
        return $preSave;
    }
}