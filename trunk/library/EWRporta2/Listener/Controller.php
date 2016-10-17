<?php

class EWRporta2_Listener_Controller
{
	public static function forum($class, array &$extend)
	{
		// XenForo_ControllerPublic_Forum
		$extend[] = 'EWRporta2_ControllerPublic_Forum';
	}
	
	public static function thread($class, array &$extend)
	{
		// XenForo_ControllerPublic_Thread
		$extend[] = 'EWRporta2_ControllerPublic_Thread';
	}
}