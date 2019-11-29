<?php

namespace frame\base;

/**
 * Description of Request
 * 请求对象基类
 * @author KowloonZh
 */
abstract class Request extends God
{

    /**
     * PATHINFO
     * @var string
     */
    private $_pathInfo;

    /**
     * 入口脚本的url|脚本名
     * @var string
     */
    protected $_scriptUrl;

    /**
     * 解析返回路由和参数数组
     * @return array
     */
    public function resolve()
    {
        return [$this->getPathInfo(), $this->request()];
    }

    /**
     * 返回pathInfo
     * @return string
     */
    public function getPathInfo()
    {
        if ($this->_pathInfo === null) {
            $this->_pathInfo = $this->resolvePathInfo();
        }
        return $this->_pathInfo;
    }

    abstract public function request($name = null, $default = null);

    abstract public function getScriptUrl();

    abstract protected function resolvePathInfo();
}
