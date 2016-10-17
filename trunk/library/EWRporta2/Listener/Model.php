<?php

class EWRporta2_Listener_Model
{
	public static function post($class, array &$extend)
	{
		// XenForo_Model_Post
		$extend[] = 'EWRporta2_Model_Post';
	}
	
	public static function user($class, array &$extend)
	{
		// XenForo_Model_User
		$extend[] = 'EWRporta2_Model_User';
	}
}