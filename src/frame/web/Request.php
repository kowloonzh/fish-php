<?php

namespace frame\web;

use frame\base\Exception;

/**
 * Description of Request
 * Frame的请求类
 * @author KowloonZh
 */
class Request extends \frame\base\Request {

    /**
     * 当前Url
     * @var string 
     */
    private $_url;

    /**
     * 入口文件的路径
     * @var string 
     */
    private $_scriptFile;

    /**
     * 基本url，不含入口文件
     * @var string
     */
    private $_baseUrl;

    /**
     * 含域名的url，eg:http://www.zjl.cn
     * @var string 
     */
    private $_hostInfo;

    //获取$_REQUEST
    public function request($name = null, $default = null)
    {
        if ($name === null) {
            return array_merge($this->get(), $this->post());
        }
        $post_value = $this->post($name);
        return $post_value !== NULL ? $post_value : $this->get($name, $default);
    }

    //获取$_GET
    public function get($name = null, $default = null)
    {
        if ($name === null) {
            return $_GET;
        }
        return isset($_GET[$name]) ? $_GET[$name] : $default;
    }

    //获取$_POST
    public function post($name = null, $default = null)
    {
        if ($name === null) {
            return $_POST;
        }
        return isset($_POST[$name]) ? $_POST[$name] : $default;
    }

    /**
     * 解析pathInfo
     * @return string
     * @throws Exception
     */
    protected function resolvePathInfo()
    {
        $pathInfo = $this->getUrl();
        if (($pos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }
        $pathInfo = urldecode($pathInfo);
        $scriptUrl = $this->getScriptUrl();
        $baseUrl = $this->getBaseUrl();
        if (strpos($pathInfo, $scriptUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($scriptUrl));
        } elseif ($baseUrl === '' || strpos($pathInfo, $baseUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
        } elseif (isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0) {
            $pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
        } else {
            throw new Exception('Fail to resolve pathinfo');
        }
        if ($pathInfo[0] === '/') {
            $pathInfo = substr($pathInfo, 1);
        }
        return (string) $pathInfo;
    }

    /**
     * 返回当前Url
     * @return type
     */
    public function getUrl()
    {
        if ($this->_url === null) {
            $this->_url = $this->resolveRequestUri();
        }
        return $this->_url;
    }

    /**
     * 解析请求的uri,各个web服务器不尽相同
     * @return string
     * @throws Exception
     */
    public function resolveRequestUri()
    {
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {  //IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if ($requestUri !== '' && $requestUri[0] !== '/') {
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            throw new Exception('Fail to resolve uri');
        }
        return $requestUri;
    }

    /**
     * 返回入口url
     * @return string
     * @throws Exception
     */
    public function getScriptUrl()
    {
        if ($this->_scriptUrl === null) {
            $scriptFile = $this->getScriptFile();
            $scriptName = basename($scriptFile);
            if (basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                $this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->_scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $scriptFile));
            } else {
                throw new Exception('Fail to resolve entry_script');
            }
        }
        return $this->_scriptUrl;
    }

    /**
     * 返回基础URL
     * @return string
     */
    public function getBaseUrl()
    {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }
        return $this->_baseUrl;
    }

    /**
     * 返回入口文件路径
     * @return string
     */
    public function getScriptFile()
    {
        return isset($this->_scriptFile) ? $this->_scriptFile : $_SERVER['SCRIPT_FILENAME'];
    }

    /**
     * 设置入口文件路径
     * @param string $value
     */
    public function setScriptFile($value)
    {
        $this->_scriptFile = $value;
    }

    /**
     * 返回是否是https
     * @return boolean if the request is sent via secure channel (https)
     */
    public function getIsSecureConnection()
    {
        return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
    }

    /**
     * 返回hostname的url，eg：http://www.zjl.cn
     * @return string
     */
    public function getHostInfo()
    {
        if ($this->_hostInfo === null) {
            $secure = $this->getIsSecureConnection();
            $http = $secure ? 'https' : 'http';
            if (isset($_SERVER['HTTP_HOST'])) {
                $http_host = explode(':', $_SERVER['HTTP_HOST'])[0];
                $this->_hostInfo = $http . '://' . $http_host;
            } else {
                $this->_hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
            }
            $port = $this->getPort();
            if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
                $this->_hostInfo .= ':' . $port;
            }
        }
        return $this->_hostInfo;
    }

    //返回网站端口
    public function getPort()
    {
        $secure = $this->getIsSecureConnection();
        if ($secure) {
            return isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 443;
        } else {
            return isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 80;
        }
    }

    //返回当前请求的绝对路径
    public function getAbsoluteUrl()
    {
        return $this->getHostInfo() . $this->getUrl();
    }

}
