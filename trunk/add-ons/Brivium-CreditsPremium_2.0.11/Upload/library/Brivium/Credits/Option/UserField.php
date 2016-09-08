<?php

/**
 * Helper for choosing what happens by default to spam threads.
 *
 * @package XenForo_Options
 */
abstract class Brivium_Credits_Option_UserField
{
	/**
	 * Verifies and prepares the censor option to the correct format.
	 *
	 * @param XenForo_DataWriter $dw Calling DW
	 * @param string $fieldName Name of field/option
	 *
	 * @return true
	 */
	public static function verifyOption($option, XenForo_DataWriter $dw, $fieldName)
	{
		$fields = preg_split('/\s+/', trim($option));
		$otherField = '';
		$creditModel = XenForo_Model::create('Brivium_Credits_Model_Credit');
		foreach($fields AS $field){
			if(!$creditModel->checkIfExist('xf_user', $field)){
				$dw->error(new XenForo_Phrase("The field '$field' was not recognised."), $field);
			}
		}
	
		return true;
	}
}