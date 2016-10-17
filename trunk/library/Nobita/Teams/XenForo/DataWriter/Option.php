<?php

class Nobita_Teams_XenForo_DataWriter_Option extends XFCP_Nobita_Teams_XenForo_DataWriter_Option
{
	protected function _preSave()
	{
		if (!$this->isInsert()
			&& $this->get('option_id') == 'Teams_defaultRules'
			&& isset(Nobita_Teams_Listener::$defaultRulesData['rules'])
		)
		{
			$rules = Nobita_Teams_Listener::$defaultRulesData['rules'];

			$this->set('option_value', $rules);
			Nobita_Teams_Listener::$defaultRulesData = null;
		}

		return parent::_preSave();
	}
}