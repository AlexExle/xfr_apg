<?php

class Brivium_Credits_ViewAdmin_Credits_ListActions extends XenForo_ViewAdmin_Base
{
	/**
	 * Renders all options, and splits them into groups according to
	 * their 100s display order
	 */
	public function renderHtml()
	{
		$actions = array();
		if($this->_params['actions']){
			foreach ($this->_params['actions'] AS $actionId => $action)
			{
				$x = floor($action['display_order'] / 100);
				$actions[$x][$actionId] = $action;
			}
		}

		$this->_params['renderedActions'] = $actions;
	}
}