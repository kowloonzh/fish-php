<?php

namespace frame\web;

/**
 * Description of Response
 * Frame的响应类
 * @author KowloonZh
 */
class Response extends \frame\base\Object
{
    /**
     * 发送前的内容,在send()中会转换为content的值
     * @var mixed 
     */
    public $data;
    /**
     * 实际发送的内容
     * @var string
     */
    public $content;
    
    /**
     * 头信息
     * @var string 
     */
    private $_headers = [];
    /**
     * 是否已发送
     * @var boolean 
     */
    public $isSent = false;

    /**
     * 发送相应的结果
     * @return string
     */
    public function send() {
        if ($this->isSent) {
            return;
        }
        $this->prepare();
        $this->sendHeaders();
        $this->sendContent();
        $this->isSent = true;
    }

    /**
     * 发送前的预处理
     * @return null
     * @throws Exception
     */
    public function prepare() {
        if ($this->data === null) {
            return;
        }
        if (is_array($this->data)) {
            $this->addHeaders('Content-Type', 'application/json;charset=UTF-8');
            $this->content = json_encode($this->data);
        } elseif (is_scalar($this->data)) {
            $this->addHeaders('Content-Type', 'text/html;charset=UTF-8');
            $this->content = $this->data;
        }elseif(is_object($this->data) && method_exists($this->data, '__toString')){
            $this->addHeaders('Content-Type', 'text/html;charset=UTF-8');
            $this->content = (string)$this->data;
        } else {
            throw new \frame\base\Exception('Unknown response type: ' . gettype($this->data));
        }
    }

    /**
     * 发送头信息
     * @return type
     */
    public function sendHeaders() {
        if (headers_sent()) {
            return;
        }
        $headers = $this->getHeaders();
        if (!empty($headers)) {
            foreach ($headers as $name => $value) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                header("$name: $value");
            }
        }
    }

    /**
     * 发送内容
     */
    public function sendContent() {
        echo $this->content;
    }

    /**
     * 添加头信息
     * @param type $name
     * @param type $value
     */
    public function addHeaders($name, $value) {
        $this->_headers[$name] = $value;
    }

    /**
     * 返回头信息
     * @return type
     */
    public function getHeaders() {
        return $this->_headers;
    }

    /**
     * 清空对象
     * @return \frame\web\Response
     */
    public function clear() {
        $this->_headers = [];
        $this->data = null;
        $this->content = null;
        $this->isSent = false;
        return $this;
    }
}
