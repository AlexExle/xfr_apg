<?php

class Nobita_Teams_ViewPublic_Team_Search extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$results = array();
		foreach ($this->_params['teams'] AS $team)
		{
			$results[$team['title']] = array(
				'avatar' => XenForo_Template_Helper_Core::callHelper('grouplogo', array($team)),
				'username' => htmlspecialchars($team['title']),
				'team_id' => $team['team_id'],
			);
		}

		return array(
			'results' => $results
		);
	}
}