<?php

class Brivium_Credits_ViewPublic_Credits_Index extends XenForo_ViewPublic_Base
{
	/**
	 * Renders all actions, and splits them into groups according to
	 * their 100s display order
	 */
	public function renderHtml()
	{
		$actions = array();
		$currencies = array();
		if(!empty($this->_params['actions'])){
			foreach ($this->_params['actions'] AS $actionId => $action)
			{
				$x = floor($action['display_order'] / 100);
				if(!isset($actions[$x]) && !empty($actions)){
					$actions[$x][] = array(
						'value' => '0',
						'label' => '',
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
			$this->_params['renderedActions'] = $actions;
		}
		if(!empty($this->_params['currencies'])){
			foreach ($this->_params['currencies'] AS $currencyId => &$currency)
			{
				if(empty($currency['events'])){
					unset($this->_params['currencies'][$currencyId]);
					continue;
				}
				$currencies = array();
				foreach ($currency['events'] AS $actionId => $event)
				{
					if(!empty($event['title']) && !empty($event['display_order'])){
						$x = floor($event['display_order'] / 100);
						if(!isset($currencies[$x]) && !empty($currencies)){
							$currencies[$x] = array();
						}
						if(!empty($event['active'])){
							$currencies[$x][$actionId] = array(
								'value' => $event['action_id'],
								'label' => $event['title'],
								'selected' => '',
								'depth' => 0,
								'active' => $event['active'],
							);
						}
					}
				}

				$currency['renderedCurrencies'] = $currencies;
			}
		}
	}
}