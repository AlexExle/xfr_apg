<?php

class XenForo_Helper_Http
{
	/**
	 * Gets a Zend_Http_Client object, automatically switching to cURL if the
	 * specified URL can't be handled by streams.
	 *
	 * @param string $uri
	 * @param array $options
	 *
	 * @return Zend_Http_Client
	 */
	public static function getClient($uri, array $options = array())
	{
		if (!isset($options['adapter']))
		{
			$options += self::getExtraHttpClientOptions($uri);
		}

		return new Zend_Http_Client($uri, $options);
	}

	/**
	 * Gets a Zend_Http_Client object designed for use on untrusted URLs. This allows an admin
	 * to configure use of an HTTP proxy (such as with Zend_Http_Client_Adapter_Proxy)
	 *
	 * @param string $uri
	 * @param array $options
	 *
	 * @return Zend_Http_Client
	 */
	public static function getUntrustedClient($uri, array $options = array())
	{
		$config = XenForo_Application::getConfig()->untrustedHttpClient->toArray();
		if (!empty($config['adapter']))
		{
			$config += $options;
			return new Zend_Http_Client($uri, $config);
		}
		else
		{
			return self::getClient($uri, $options);
		}
	}

	/**
	 * Gets extra options to pass to an HTTP client to ensure it works in more situations
	 *
	 * @param string $uri
	 *
	 * @return array
	 */
	public static function getExtraHttpClientOptions($uri)
	{
		$parts = parse_url($uri);
		$wrappers = stream_get_wrappers();
		if (!in_array($parts['scheme'], $wrappers))
		{
			// can't be handled by sockets -- fallback to cURL
			if (function_exists('curl_getinfo'))
			{
				return array(
					'adapter' => 'Zend_Http_Client_Adapter_Curl',
					'curloptions' => array(CURLOPT_SSL_VERIFYPEER => false)
				);
				// TODO: consider validating SSL cert
			}
		}

		return array();
	}
}