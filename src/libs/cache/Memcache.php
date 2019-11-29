<?php

namespace libs\cache;

/**
 * Description of Memcache
 * Memcache缓存类
 * @author KowloonZh
 */
class Memcache extends Cache
{
     /**
     * memcache实例对象
     * @var \Memcached|\Memcache 
     */
    private $_cache;

    /**
     * memcache的服务器配置，参数见 libs\cache\MemecacheServer
     * @var array 
     */
    private $_servers = [];

    /**
     * 是否使用Memcached扩展
     * @var boolean 
     */
    public $use_memcached = false;

    /**
     * 所有通过相同的 persistent_id 值创建的实例共享同一个连接,仅use_memcached为true时有效
     * @var string 
     */
    public $persistent_id;

    /**
     * 用户名,仅use_memcached为true时有效
     */
    public $username;

    /**
     * 密码,仅use_memcached为true时有效
     */
    public $password;

    /**
     * Memcached的选项,仅use_memcached为true时有效
     * @var array 
     */
    public $options;

    public function init()
    {
        parent::init();
        $this->addServers($this->getMemcache(), $this->getServers());
    }
    
    /**
     * @param string $id
     * @param boolean $throwException
     * @return \libs\cache\Memcache
     */
    public static function di($id='cache', $throwException = true)
    {
        return parent::di($id, $throwException);
    }

    protected function addServers($cache, $servers)
    {
        if (empty($servers)) {
            $servers = [new MemcacheServer(['host' => '127.0.0.1'])];
        } else {
            foreach ($servers as $server) {
                if (!$server->host) {
                    throw new \frame\base\Exception('the host of the server is required');
                }
            }
        }
        foreach ($servers as $server) {
            if ($this->use_memcached) {
                $cache->addServer($server->host, $server->port, $server->weight);
            } else {
                $cache->addserver(
                        $server->host, $server->port, $server->persistent, $server->weight, $server->timeout, $server->retry_interval, $server->status, $server->failure_callback
                );
            }
        }
    }

    /**
     * 
     * @return \Memcached|\Memcache
     * @throws \frame\base\Exception
     */
    public function getMemcache()
    {
        if ($this->_cache === null) {
            $extension = $this->use_memcached ? 'memcached' : 'memcache';
            if (!extension_loaded($extension)) {
                throw new \frame\base\Exception('Memcache required php extension ' . $extension);
            }
            if ($this->use_memcached) {
                $this->_cache = $this->persistent_id !== null ? new \Memcached($this->persistent_id) : new \Memcached;
                if ($this->username !== null || $this->password !== null) {
                    $this->_cache->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
                    $this->_cache->setSaslAuthData($this->username, $this->password);
                    if (!empty($this->options)) {
                        $this->_cache->setOptions($this->options);
                    }
                }
            } else {
                $this->_cache = new \Memcache();
            }
        }
        return $this->_cache;
    }

    public function getServers()
    {
        return $this->_servers;
    }

    public function setServers($servers)
    {
        foreach ($servers as $config) {
            $this->_servers[] = new MemcacheServer($config);
        }
    }

    protected function deleteValue($key)
    {
        return $this->_cache->delete($key, 0);
    }

    protected function flushValues()
    {
        return $this->_cache->flush();
    }

    protected function getValue($key)
    {
        return $this->_cache->get($key);
    }

    protected function setValue($key, $value, $duration)
    {
        $expire = $duration > 0 ? $duration + time() : $duration;
        if ($this->use_memcached) {
            return $this->_cache->set($key, $value, $expire);
        } else {
            return $this->_cache->set($key, $value, 0, $expire);
        }
    }

    protected function addValue($key, $value, $duration)
    {
        $expire = $duration > 0 ? $duration + time() : $duration;
        if ($this->use_memcached) {
            return $this->_cache->add($key, $value, $expire);
        } else {
            return $this->_cache->add($key, $value, 0, $expire);
        }
    }
}

class MemcacheServer extends \frame\base\God {

    /**
     * memcache服务器的主机名或者ip地址
     */
    public $host;

    /**
     * memcache服务器的端口
     */
    public $port = '11211';

    /**
     * 用来控制此服务器被选中的权重
     */
    public $weight = 1;

    /**
     * 控制是否使用持久化连接,仅memcache扩展中使用
     * @var boolean 
     */
    public $persistent = true;

    /**
     * 连接持续（超时）时间（单位秒）,仅memcache扩展中使用
     */
    public $timeout = 1;

    /**
     * 服务器连接失败时重试的间隔时间,-1表示不重试,仅memcache扩展中使用
     */
    public $retry_interval = 15;

    /**
     * 控制此服务器是否可以被标记为在线状态,该参数默认 TRUE ，表明允许进行故障转移,仅memcache扩展中使用
     */
    public $status = true;

    /**
     * 允许用户指定一个运行时发生错误后的回调函数。回调函数会在故障转移之前运行。回调函数会接受到两个参数，分别是失败主机的 主机名和端口号,仅memcache扩展中使用
     */
    public $failure_callback;

}
