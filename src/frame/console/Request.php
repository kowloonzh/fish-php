<?php

namespace frame\console;

/**
 * Description of Request
 * 计划任务命令行请求对象
 * @author KowloonZh
 */
class Request extends \frame\base\Request
{

    /**
     * 脚本参数 [k=>v,k=>v]
     * @var array 
     */
    private $_params = [];

    protected function resolvePathInfo()
    {
        if (isset($_SERVER['argv'])) {
            $params = $_SERVER['argv'];

            //设置脚本名
            $this->_scriptUrl = array_shift($params);
            if (empty($params)) {
                return '';
            }
            //设置路由
            $pathInfo = array_shift($params);
            //解析参数
            $this->resolveParams($params);
            return $pathInfo;
        }
        return '';
    }

    //设置参数
    protected function resolveParams($params)
    {
        foreach ($params as $param) {
            //只取--param=value格式的参数
            if (preg_match('/^--(\w+)(=(.*))?$/', $param, $matches)) {
                $name = $matches[1];

                $this->_params[$name] = isset($matches[3]) ? $matches[3] : true;
            }
        }
    }

    //返回参数
    public function request($name = null, $default = null)
    {
        if ($name === null) {
            return $this->_params;
        }
        return isset($this->_params[$name]) ? $this->_params[$name] : $default;
    }

    public function getScriptUrl()
    {
        return $this->_scriptUrl;
    }

}
