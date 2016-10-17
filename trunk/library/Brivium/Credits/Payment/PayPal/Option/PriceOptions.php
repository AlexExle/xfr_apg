<?php

/**
 * Helper for choosing what happens by default to spam threads.
 *
 * @package XenForo_Options
 */
abstract class Brivium_Credits_Payment_PayPal_Option_PriceOptions
{
	public static function renderOptionStep(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$choices = !empty($preparedOption['option_value']['step_price'])?$preparedOption['option_value']['step_price']:array();

		$listPriceStep = array();
		foreach($choices AS $step=>$cost){
			$listPriceStep[] = array(
				'step'	=>	$step,
				'cost'	=>	$cost,
			);
		}
		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption' => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		return $view->createTemplateObject('BRST_option_template_price', array(
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption' => $preparedOption,
			'formatParams' => $preparedOption['formatParams'],
			'editLink' => $editLink,

			'choices' => $listPriceStep,
			'nextCounter' => count($choices)
		));
	}

	public static function verifyOptionStep(array &$priceSteps, XenForo_DataWriter $dw, $fieldName)
	{
		$listPriceStep = array();
		if(!empty($priceSteps['step_price'])){
			foreach($priceSteps['step_price'] AS $priceStep){
				if(!empty($priceStep['step']) && $priceStep['step'] > 0 && !empty($priceStep['cost']) && $priceStep['cost'] > 0){
					$listPriceStep[$priceStep['step']] = $priceStep['cost'];
				}
			}
			asort($listPriceStep);
			$priceSteps['step_price'] = $listPriceStep;
		}
		return true;
	}
}