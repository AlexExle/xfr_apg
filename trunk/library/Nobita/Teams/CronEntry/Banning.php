<?php

class Nobita_Teams_CronEntry_Banning
{
	public static function runHourly()
	{
		Nobita_Teams_Container::getModel('Nobita_Teams_Model_Banning')->deleteBanningExpired();
	}
}