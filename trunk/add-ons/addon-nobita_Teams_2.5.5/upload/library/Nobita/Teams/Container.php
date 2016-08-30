<?php

class Nobita_Teams_Container implements ArrayAccess
{
    /**
     * @var Nobita_Teams_Container
     */
    private static $_instance;

    /**
     * @var array
     */
    private $_data = array();

    public function __construct()
    {
    }

    public function offsetExists($name)
    {
        return isset($this->_data[$name]);
    }

    public function offsetGet($name)
    {
        if($this->offsetExists($name))
        {
            return $this->_data[$name];
        }
    }

    public function offsetSet($name, $value)
    {
        $this->_data[$name] = $value;

        return $this;
    }

    public function offsetUnset($name)
    {
        if($this->offsetExists($name))
        {
            unset($this->_data[$name]);
        }
    }

    public function has($name)
    {
        return $this->offsetExists($name);
    }

    public function get($name)
    {
        return $this->offsetGet($name);
    }

    public function set($name, $value)
    {
        return $this->offsetSet($name, $value);
    }

    public static function getModel($class)
    {
        $dataKey = 'model:'.$class;
        if(!self::getInstance()->has($dataKey))
        {
            self::getInstance()->set($dataKey, XenForo_Model::create($class));
        }

        return self::getInstance()->get($dataKey);
    }

    public static function getInstance()
    {
        if(self::$_instance === null)
        {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __unset($name)
    {
        return $this->offsetUnset($name);
    }
}
