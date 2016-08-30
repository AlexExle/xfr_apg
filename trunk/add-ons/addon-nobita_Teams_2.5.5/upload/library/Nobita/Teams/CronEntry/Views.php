<?php

class Nobita_Teams_CronEntry_Views
{
	public static function updateViews()
	{
		Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team')->updateTeamViews();
	}
}