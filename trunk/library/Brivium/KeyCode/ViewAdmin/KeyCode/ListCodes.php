<?php

class Brivium_KeyCode_ViewAdmin_KeyCode_ListCodes extends XenForo_ViewAdmin_Base
{
	public function renderJson()
	{
		if (!empty($this->_params['filterView']))
		{
			$this->_templateName = 'BRKC_code_list_items';
		}

		return null;
	}
}