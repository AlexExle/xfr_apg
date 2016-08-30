<?php

class Nobita_Teams_ViewPublic_Team_View extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		XenForo_Application::set('view', $this);

		$newTabs = array();
		$parentTabs = array();

		foreach ($this->_params['customFieldsGrouped'] as $fieldPosition => $fields)
		{
			if ($fieldPosition == 'new_tab')
			{
				$newTabs[$fieldPosition] = $fields;
			}
			elseif ($fieldPosition == 'parent_tab')
			{
				$parentTabs[$fieldPosition] = $fields;
			}
		}


		if (!empty($newTabs) && !empty($parentTabs))
		{
			foreach($parentTabs['parent_tab'] as $parentTab)
			{
				foreach ($newTabs['new_tab'] as &$newTab)
				{
					if ($parentTab['parent_tab_id'] == $newTab['field_id']
						&& $parentTab['hasValue']
					)
					{
						// if child tab of new tab has value
						// so the tab should be display
						$newTab['hasValue'] = true;
					}
				}
			}
		}

		$this->_params['customFieldsGrouped'] = Nobita_Teams_Helper_Field::renderCustomFieldsForView(
			$this, $newTabs, $this->_params['team']
		);

	}
}