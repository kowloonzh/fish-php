<?php

namespace third;

use common\libs\Errors;
use common\libs\ExceptionBiz;
use frame\base\God;
use libs\log\Loger;
use libs\utils\Curl;

/**
 * Description of BaseThird
 * 第三方调用
 * @author JIU
 */
class BaseThird extends God
{

    public $domain;
    public $hostname;
    public $proxy;
    public $app_key;
    public $secret_key;
    public $timeout = 30;
    public $connect_timeout = 3;
    public $log_info_flag = true;
    public $err_throw_flag = true;
    private $_curl;

    /**
     * @param array $config
     * @return self
     */
    public static function ins($config = array())
    {
        return parent::ins($config);
    }

    /**
     *  返回curl对象
     * @return Curl
     */
    public function getCurl()
    {
        if ($this->_curl === null) {
            $this->createCurl();
        }
        return $this->_curl;
    }

    /**
     * 创建curl对象
     * @return Curl
     */
    public function createCurl()
    {
        $this->_curl = new Curl();
        if (!empty($this->hostname)) {
            $this->_curl->setHeader('Host', $this->hostname);
        }
        $this->_curl->setTimeout($this->timeout);
        $this->_curl->setConnectTimeout($this->connect_timeout);
        $this->_curl->complete([$this, 'afterCurl']);
        return $this->_curl;
    }

    public function afterCurl()
    {
        $this->getCurl()->rawResponseHeaders = '';
    }

    /**
     * 执行请求并记录日志
     * @param string $api
     * @param array $params
     * @param string $type
     * @return mixed|string
     * @throws ExceptionBiz
     */
    protected function call($api, $params = [], $type)
    {
        if ($this->domain) {
            $url = rtrim($this->domain, '/') . '/' . ltrim($api, '/');
        } else {
            $url = $api;
        }

        $curl = $this->getCurl();

        if (!empty($this->proxy) && empty($this->hostname)) {
            $url_parse    = parse_url($url);
            $default_port = strtolower($url_parse['scheme']) == 'https' ? 443 : 80;
            $port         = $url_parse['port'];
            $proxy        = $this->proxy . ':' . ($port ? $port : $default_port);
            $curl->setOpt(CURLOPT_PROXY, $proxy);
        }

        $begin_time = microtime(true);

        if ($type == 'delete') {
            $response = $curl->delete($url, [], $params);
        } else {
            $response = $curl->{$type}($url, $params);
        }

        $end_time = microtime(true);
        $consume  = round(($end_time - $begin_time) * 1000);

        if (is_string($response)) {
            $json = json_decode($response, true);
        } else {
            $json = json_decode(json_encode($response), true);
        }

        $result = json_last_error() == JSON_ERROR_NONE ? $json : $response;
        $identi = strtolower(str_replace('\\', '.', get_class($this)));

        $logInfo = [
            'identi'          => $identi,
            'url'             => $url,
            'hostname'        => $this->hostname,
            'proxy'           => $this->proxy,
            'params'          => $params,
            'consume(ms)'     => strval($consume),
            'result'          => $result,
            //            'options'         => $this->getCurl()->getOptions(),
            'requestHeader'   => $curl->requestHeaders,
            'responseHeaders' => $curl->responseHeaders,
            'errorCode'       => $curl->errorCode,
            'errorMessage'    => $curl->errorMessage,
        ];

        // 成功
        if (!$curl->error) {
            if ($this->log_info_flag) {
                Loger::info($logInfo, $identi);
            }
        } else {
            Loger::error($logInfo, $identi);

            // 出现 http error 或者 curl error 时是否抛出异常
            if ($this->err_throw_flag) {
                throw new ExceptionBiz(Errors::ERR_SYSTEM, ['source' => $identi, 'res' => $result]);
            }
        }
        return $result;
    }

    /**
     * 解析返回值,只能处理固定返回值的结果,类似hulk的项目接口
     * @param array $ret // [errno:xx,errmsg:xxx,data:xxxx]
     * @return mixed
     * @throws ExceptionBiz
     */
    protected function resolveResult($ret)
    {
        if (!isset($ret['errno'])) {
            throw new ExceptionBiz(Errors::ERR_SYSTEM);
        } elseif ($ret['errno'] != 0) {
            throw new ExceptionBiz($ret['errno'] . '=>' . $ret['errmsg'], $ret['data']);
        }
        return $ret['data'];
    }

    /**
     * 执行get请求
     * @param string $api
     * @param array $params
     * @return mixed
     */
    public function get($api, $params = [])
    {
        if (is_array($api)) {
            $params = $api;
            $api    = '';
        }
        return $this->call($api, $params, 'get');
    }

    /**
     * 执行post请求
     * @param string $api
     * @param array $params
     * @return mixed
     */
    public function post($api, $params = [])
    {
        $this->getCurl()->setHeader('Expect', '');
        if (is_array($api)) {
            $params = $api;
            $api    = '';
        }
        $ret = $this->call($api, $params, 'post');
        $this->getCurl()->unsetHeader('Expect');
        return $ret;
    }

    /**
     * 执行delete请求
     * @param string $api
     * @param array $queryParams // 放在url上的参数
     * @param array $params // body里面的参数
     * @return mixed
     */
    public function delete($api, $queryParams = [], $params = [])
    {
        if (is_array($api)) {
            $params      = $queryParams;
            $queryParams = $api;
            $api         = '';
        }

        $api = $this->buildQueryUrl($api, $queryParams);
        return $this->call($api, $params, 'delete');
    }

    /**
     * 执行put请求
     * @param string $api
     * @param array $params
     * @return mixed
     */
    public function put($api, $params = [])
    {
        if (is_array($api)) {
            $params = $api;
            $api    = '';
        }
        return $this->call($api, $params, 'put');
    }

    /**
     * 执行patch请求
     * @param string $api
     * @param array $params
     * @return mixed
     */
    public function patch($api, $params = [])
    {
        if (is_array($api)) {
            $params = $api;
            $api    = '';
        }
        return $this->call($api, $params, 'patch');
    }

    public function getHostInfo()
    {
        $host_info = rtrim($this->domain, '/');
        if (!empty($this->hostname)) {
            $matches   = parse_url($host_info);
            $host_info = str_replace($matches['host'], $this->hostname, $host_info);
        }
        return $host_info;
    }


    /**
     * 生成带query参数的url, eg. buildQueryUrl('http://360.cn',['user'=>'zjl'])   =>  http://360.cn?user=zjl
     * @param string $url
     * @param array $params
     * @return string
     */
    protected function buildQueryUrl($url, array $params = [])
    {

        if (empty($params)) {
            return $url;
        }

        if (strpos($url, '?')) {
            $url .= '&' . http_build_query($params);
        } else {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

}
