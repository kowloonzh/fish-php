<?php

namespace frame\base;

use Closure;

/**
 * Description of DI
 * Frame的DI容器
 * @author KowloonZh
 */
class DI extends God
{

    /**
     * 存放容器中的实例对象列表，key为对象的唯一id标识，value为对象的实例
     * @var array
     */
    private $_components = [];

    /**
     * 存入容器中对象的定义配置列表，key为对象的唯一id标识,value为对象的配置
     * @var array
     */
    private $_definitions = [];

    /**
     * 从容器中取值，返回的是一个实例化后的对象
     * @param string $id 容器中对象的唯一标识
     * @param boolean $throwException 如果设置为true,在容器中没有找到时抛出异常，如果设置为false，没有找到时返回null
     * @return object
     * @throws Exception
     */
    public function get($id, $throwException = true)
    {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }
        if (isset($this->_definitions[$id])) {
            $definition = $this->_definitions[$id];
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_components[$id] = $definition;
            } else {
                return $this->_components[$id] = static::createObject($definition);
            }
        }
        if ($throwException) {
            throw new Exception('Unknow object ID: ' . $id);
        } else {
            return null;
        }
    }

    /**
     * 向容器中注入一个对象配置
     * @param string $id 容器中对象的唯一标识
     * @param mixed $definition 对象的配置，可以是类名|匿名函数|对象|包含class元素的数组
     * @throws Exception
     */
    public function set($id, $definition)
    {
        if ($definition === null) {
            unset($this->_components[$id], $this->_definitions[$id]);
        }
        unset($this->_components[$id]);
        if (is_object($definition) || is_callable($definition, true)) {
            $this->_definitions[$id] = $definition;
        } elseif (is_array($definition)) {
            if (isset($definition['class'])) {
                $this->_definitions[$id] = $definition;
            } else {
                throw new Exception('the configuration for the "' . $id . '" object must contain "class" element');
            }
        } else {
            throw new Exception('the configuration type for the "' . $id . '" object error: ' . gettype($definition));
        }
    }

    /**
     * 根据对象标识判断对象是否在容器中
     * @param string $id
     * @param boolean $checkInstance 设置为true时，只检查已经实例化后的容器列表
     * @return boolean
     */
    public function has($id, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_components[$id]) : isset($this->_definitions[$id]);
    }

    /**
     * 清除容器中的某个对象
     * @param string $id
     */
    public function clear($id)
    {
        unset($this->_components[$id], $this->_definitions[$id]);
    }

    /**
     * 覆写父类魔术方法，当访问对象的某个属性时，先去容器中取值
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($this->has($name)) {
            return $this->get($name);
        }
        return parent::__get($name);
    }

    /**
     * 覆写父类魔术方法，当设置对象属性时，先设置容器对象
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if ($this->has($name)) {
            $this->set($name, $value);
        }
        parent::__set($name, $value);
    }

    /**
     * 当调用getter|setter方法时，去容器中取值
     * @param string $name
     * @param array $arguments
     * @return type
     */
    public function __call($name, $arguments)
    {
        $act = substr($name, 0, 3);
        if ($act == 'get') {
            $key = lcfirst(substr($name, 3));
            return $this->get($key, isset($arguments[0]) ? $arguments[0] : true);
        } elseif ($act == 'set') {
            $key = lcfirst(substr($name, 3));
            return $this->set($key, isset($arguments[0]) ? $arguments[0] : null);
        }
    }

    /**
     * 返回容器中的对象列表，或者定义(对象配置)列表
     * @param boolean $returnDefinition 如果设置为true，则返回对象配置列表
     * @return array
     */
    public function getComponents($returnDefinition = true)
    {
        return $returnDefinition ? $this->_definitions : $this->_components;
    }

    /**
     * 将应用配置中的components数组注入到容器中
     * @param array $components
     */
    public function setComponents($components)
    {
        foreach ($components as $id => $component) {
            $this->set($id, $component);
        }
    }

}
