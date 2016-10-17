<?php

if (! function_exists('dd'))
{
	function dd()
	{
		array_map(function($var) {
			Zend_Debug::dump($var);
		}, func_get_args());

		// Stop
		die();
	}
}

if (! function_exists('group_hex2rgb'))
{
	function group_hex2rgb($hex)
	{
		$parts = array_map(function($value)
        {
            preg_match('/[0-9]+/', $value, $matched);

            return reset($matched);
        }, explode(',', $hex));

        if (count($parts) == 3)
        {
            return $parts;
        }

		$hex = str_replace("#", "", $hex);

        if(strlen($hex) == 3)
        {
        	$r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        }
        else
        {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }

        $rgb = array($r, $g, $b);

        return $rgb;
	}
}
