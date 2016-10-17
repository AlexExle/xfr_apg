<?php

class EWRporta2_Widget_RandomQuote extends XenForo_Model
{
	public function getCachedData($widget, $options)
	{
		return $options['randomquote_quote'][array_rand($options['randomquote_quote'])];
	}
}