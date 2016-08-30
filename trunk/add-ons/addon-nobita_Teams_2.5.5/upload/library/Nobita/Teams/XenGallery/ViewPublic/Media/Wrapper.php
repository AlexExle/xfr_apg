<?php

class Nobita_Teams_XenGallery_ViewPublic_Media_Wrapper extends XFCP_Nobita_Teams_XenGallery_ViewPublic_Media_Wrapper
{
	public function renderHtml()
	{
		if ($this->_params['collapsible'] == 'basic')
		{
			foreach($this->_params['categoriesGrouped'] as $group => &$categories)
			{
				foreach($categories as $categoryId => &$category)
				{
					if(!$this->_isVisible($category['category_id']))
					{
						unset($categories[$categoryId]);
					}
				}
			}
		}
		else
		{
			foreach($this->_params['categories'] as $categoryId => &$category)
			{
				if(!$this->_isVisible($category['category_id']))
				{
					unset($this->_params['categories'][$categoryId]);
				}
			}
		}

		return parent::renderHtml();
	}

	protected function _isVisible($categoryId)
	{
		return Nobita_Teams_XenGallery_Media::isVisibleCategory($categoryId);
	}
}
