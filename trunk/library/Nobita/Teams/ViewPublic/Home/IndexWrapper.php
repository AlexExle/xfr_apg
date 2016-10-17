<?php

class Nobita_Teams_ViewPublic_Home_IndexWrapper extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$this->_renderCategorySidebar();
	}

	protected function _renderCategorySidebar()
	{
		if (!isset($this->_params['categoriesGrouped']))
		{
			return;
		}
		$categoriesGrouped = $this->_params['categoriesGrouped'];

		$rendered = Nobita_Teams_ViewPublic_Helper_Category::renderCategoryTreeFromDisplayArray(
			$this, $categoriesGrouped
		);
		
		$this->_params['rendered'] = $rendered;
	}
}