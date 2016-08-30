<?php

class Nobita_Teams_AddOnChecker implements ArrayAccess
{
	/**
	 * @var Nobita_Teams_AddOnChecker
	 */
	private static $_instance;

	/**
	 * List active addons
	 *
	 * @var array
	 */
	protected $_addOns = array();

	/**
	 * @var XenForo_Model_AddOn
	 */
	private $_addOnModel;

	public function __construct(array $addOns = null)
	{
		$this->_addOns = $addOns ?: XenForo_Application::get('addOns');
	}

	public function offsetExists($name)
	{
		return isset($this->_addOns[$name]);
	}

	public function offsetGet($name)
	{
		if($this->offsetExists($name))
		{
			return $this->_addOns[$name];
		}
	}

	public function offsetSet($name, $value)
	{
		$this->_addOns[$name] = $value;

		return $this;
	}

	public function offsetUnset($name)
	{
		if($this->offsetExists($name))
		{
			unset($this->_addons[$name]);
		}
	}

	public function existAndActive($name)
	{
		$exists = $this->getAddOnModel()->getAddOnById($name);
		if(!$exists)
		{
			return false;
		}

		$this->offsetSet($name, $exists['version_id']);
		return !empty($exists['active']);
	}

	public function exists($name)
	{
		if($this->offsetExists($name))
		{
			return true;
		}

		$exists = $this->getAddOnModel()->getAddOnById($name);
		if(!$exists)
		{
			return false;
		}

		$this->offsetSet($name, $exists['version_id']);
		return true;
	}

	public function isActive($name)
	{
		return $this->offsetExists($name);
	}

	public function isSonnbXenGalleryExistsAndActive()
	{
		$provider = Nobita_Teams_Option::get('photoProvider');
		if($this->isActive('sonnb_xengallery'))
		{
			return $provider == 'sonnb_xengallery';
		}

		return false;
	}

	public function isXenMediaExistsAndActive()
	{
		$xenMediaCategoryId = Nobita_Teams_Option::get('XenMediaCategoryId');
		$photoProvider = Nobita_Teams_Option::get('photoProvider');

		if($this->offsetGet('XenGallery'))
		{
			return !empty($xenMediaCategoryId) && $photoProvider == 'XenGallery';
		}

		return false;
	}

	public static function getInstance()
	{
		if(!self::$_instance)
		{
			$class = XenForo_Application::resolveDynamicClass(__CLASS__);
			self::$_instance = new $class();
		}

		return self::$_instance;
	}

	public function getAddOnModel()
	{
		if($this->_addOnModel === null)
		{
			$this->_addOnModel = Nobita_Teams_Container::getModel('XenForo_Model_AddOn');
		}

		return $this->_addOnModel;
	}
}
