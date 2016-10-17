<?php

class Brivium_Credits_ViewAdmin_Events_Add extends XenForo_ViewAdmin_Base
{
	/**
	 * Renders all actions, and splits them into groups according to
	 * their 100s display order
	 */
	public function renderHtml()
	{
		$actions = array();

		foreach ($this->_params['actions'] AS $actionId => $action)
		{
			$x = floor($action['display_order'] / 100);
			if(!isset($actions[$x]) && !empty($actions)){
				$actions[$x][] = array(
					'value' => '0',
					'label' => "",
					'selected' => '',
					'disabled' => 'disabled',
					'depth' => 0,
				);
			}
			$actions[$x][$actionId] = array(
				'value' => $action['action_id'],
				'label' => $action['title'],
				'selected' => '',
				'depth' => 0,
			);
		}
		
		//prd($actions);
		$this->_params['renderedActions'] = $actions;
	}
}