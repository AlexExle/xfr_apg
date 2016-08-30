<?php

/**
 * IP.Board authentication method.
 *
 * @package XenForo_Authentication
 */
class XenForo_Authentication_IPBoard extends XenForo_Authentication_Abstract
{
	/**
	* Password info for this authentication object
	*
	* @var array
	*/
	protected $_data = array();

	protected function _createHash($password, $salt)
	{
		return md5(md5($salt) . md5($password));
	}

	/**
	* Initialize data for the authentication object.
	*
	* @param string   Binary data from the database
	*/
	public function setData($data)
	{
		$this->_data = unserialize($data);
	}

	/**
	* Generate new authentication data
	* @see XenForo_Authentication_Abstract::generate()
	*/
	public function generate($password)
	{
		throw new XenForo_Exception('Cannot generate authentication for this type.');
	}

	/**
	* Authenticate against the given password
	* @see XenForo_Authentication_Abstract::authenticate()
	*/
	public function authenticate($userId, $password)
	{
		if (!is_string($password) || $password === '' || empty($this->_data))
		{
			return false;
		}

		$passwordCleaned = strtr($password, array(
			'&' => '&amp;',
			'\\' => '&#092;',
			'!' => '&#33;',
			'$' => '&#036;',
			'"' => '&quot;',
			'<' => '&lt;',
			'>' => '&gt;',
			'\'' => '&#39;',
		));
		$userHash = $this->_createHash($passwordCleaned, $this->_data['salt']);
		if ($userHash === $this->_data['hash'])
		{
			return true;
		}

		$userHash = $this->_createHash($password, $this->_data['salt']);
		return ($userHash === $this->_data['hash']);
	}
}