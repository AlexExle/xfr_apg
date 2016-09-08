<?php

class Brivium_Credits_ViewAdmin_User_List extends XenForo_ViewAdmin_Base
{
	public function renderJson()
	{
		if (!empty($this->_params['filterView']))
		{
			$this->_templateName = 'BRC_user_list_items';
		}
		return null;
	}
}