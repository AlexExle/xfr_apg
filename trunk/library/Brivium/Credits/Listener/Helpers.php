<?php
class Brivium_Credits_Listener_Helpers extends XenForo_Template_Helper_Core
{
	public static function helperCurrencyFormat($amount,$negate=false,$currencyId = null,$rich=true)
	{
		return XenForo_Application::get('brcCurrencies')->currencyFormat($amount,$negate,$currencyId,$rich);
	}
	
	
	public static function helperCurrencyIconUrl($currency, $canonical = false)
	{
		if (!is_array($currency))
		{
			$currency = array();
		}
		$url = self::getCurrencyImageUrl($currency);
		if($url){
			if ($canonical)
			{
				$url = XenForo_Link::convertUriToAbsoluteUri($url, true);
			}

			return htmlspecialchars($url);
		}else{
			return $url;
		}
	}

	public static function getCurrencyImageUrl(array $currency)
	{
		if (!empty($currency['currency_id']) && !empty($currency['image_type']))
		{
			return self::_getCustomCurrencyImageUrl($currency);
		}

		return '';
	}
	
	protected static function _getCustomCurrencyImageUrl(array $currency)
	{
		return XenForo_Application::$externalDataUrl . "/brcimages/currency/$currency[currency_id]$currency[image_type]?$currency[currency_id]";
	}
	
}
?>