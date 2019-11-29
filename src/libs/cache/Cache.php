<?php

namespace libs\cache;

use frame\base\God;

/**
 * Description of Cache
 * 缓存基类
 * @author KowloonZh
 */
abstract class Cache extends God
{
    /**
     * 所有key的前缀
     * @var string 
     */
    public $key_prefix;

    //创建key
    public function buildKey($key)
    {
        if (is_string($key)) {
            $key = (ctype_alnum($key) && strlen($key)) <= 32 ? $key : md5($key);
        } else {
            $key = md5(json_encode($key));
        }
        return $this->key_prefix . $key;
    }

    //根据单个key获取值
    public function get($key)
    {
        $key   = $this->buildKey($key);
        $value = $this->getValue($key);
        return $value;
    }

    //根据多个key获取值
    public function mget($keys)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * 添加值
     * @param string $key
     * @param mixed $value
     * @param int $duration 多少秒后过期，0表示不过期
     * @return boolean
     */
    public function add($key, $value, $duration = 0)
    {
        $key = $this->buildKey($key);
        return $this->addValue($key, $value, $duration);
    }
    
    /**
     * 设置值
     * @param string $key
     * @param mixed $value
     * @param int $duration 多少秒后过期，0表示不过期
     * @return boolean
     */
    public function set($key, $value, $duration = 0)
    {
        $key = $this->buildKey($key);
        return $this->setValue($key, $value, $duration);
    }

    /**
     * 批量设置值
     * @param array $items [{key:value},{key:value}]
     * @param int $duration 多少秒后过期，0表示不过期
     * @return array 返回设置失败的key列表
     */
    public function mset($items, $duration = 0)
    {
        $failed_keys = [];
        foreach ($items as $key => $value) {
            if ($this->set($key, $value,$duration) === false) {
                $failed_keys[] = $key;
            }
        }
        return $failed_keys;
    }

    //删除Key
    public function delete($key)
    {
        $key = $this->buildKey($key);
        return $this->deleteValue($key);
    }

    //清空缓存
    public function flush()
    {
        return $this->flushValues();
    }

    //判断某个key是否有值
    public function exists($key)
    {
        $key   = $this->buildKey($key);
        $value = $this->get($key);
        return $value !== false;
    }

    abstract protected function getValue($key);

    abstract protected function setValue($key, $value, $duration);
    
    abstract protected function addValue($key, $value, $duration);

    abstract protected function deleteValue($key);

    abstract protected function flushValues();
}
