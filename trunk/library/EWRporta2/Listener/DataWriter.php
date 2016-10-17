<?php

class EWRporta2_Listener_DataWriter
{
    public static function thread($class, array &$extend)
	{
		// XenForo_DataWriter_Discussion_Thread
		$extend[] = 'EWRporta2_DataWriter_Discussion_Thread';
	}
	
    public static function user($class, array &$extend)
	{
		// XenForo_DataWriter_User
		$extend[] = 'EWRporta2_DataWriter_User';
	}
}