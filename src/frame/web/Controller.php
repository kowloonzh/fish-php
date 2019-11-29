<?php

namespace frame\web;

use frame\base\Exception;
use frame\base\God;
use Load;
use ReflectionMethod;

/**
 * Description of Controller
 * Frame控制器基类
 * @author KowloonZh
 */
class Controller extends God
{

    /**
     * 当前的控制器名称
     * @var string 
     */
    public $id;

    /**
     * 当前的模块名称
     * @var string 
     */
    public $moduleId;

    /**
     * 默认的Action名称
     * @var string 
     */
    public $defaultAction = 'index';

    /**
     * 当前的action唯一标示
     * @var string
     */
    public $actionId;

    /**
     * 当前action的名称
     * @var string 
     */
    private $_actionName;

    /**
     * 请求的参数$_GET+$_POST,过滤时会被修改
     * @var array 
     */
    private $_request = [];

    final public function __construct($config = array())
    {
        return parent::__construct($config);
    }

    /**
     * 运行控制器
     * @param string $actionId action的名称
     * @param array $params get+post参数
     * @return mixed  
     * @throws Exception
     */
    public function run($actionId, $params = [])
    {
        //检查是否有正确的覆写init方法
        $this->checkIsInit();
        /**
         * 如果没有指定actionid，则使用默认的actionid
         */
        if ($actionId === '') {
            $actionId = $this->defaultAction;
        }
        $this->actionId = $actionId;

        //设置$_request
        $this->setRequest($params);

        $result = null;
        //根据actionid生成action的方法名
        $action = $this->resolveActionMethod($actionId);
        if ($action === null) {
            throw new Exception('Fail to resolve ' . htmlspecialchars($this->id . '/' . $actionId));
        }
        //执行action前钩子
        if ($this->beforeAction()) {
            /**
             * 绑定并验证action方法的参数
             */
            $args   = $this->bindActionParams($action);
            $res    = call_user_func_array(array($this, $action), $args);
            //执行action后钩子
            $result = $this->afterAction($res);
        }
        return $result;
    }

    /**
     * 设置$_request的某个值
     * @param string $key
     * @param mixed $value
     */
    public function setRequestValue($key, $value)
    {
        if(isset($this->_request[$key])){
            $this->_request[$key] = $value;
        }
    }

    /**
     * 设置$_request的值
     * @param array $params
     */
    public function setRequest($params)
    {
        $controllerAction = $this->id . '/' . $this->actionId;
        if (isset($params[$controllerAction])) {
            unset($params[$controllerAction]);
        }
        $this->_request = $params;
        return $this;
    }

    /**
     * 根据actionid解析action的方法名
     * @param string $actionId
     * @return string
     */
    protected function resolveActionMethod($actionId)
    {
        if (preg_match('/^[a-zA-Z0-9\\_-]+$/', $actionId) && strpos($actionId, '--') === false && trim($actionId, '-') === $actionId) {
            $actionName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $actionId))));
            $methodName = $actionName . 'Action';
            $method     = new ReflectionMethod($this, $methodName);
            if ($method->isPublic() && $method->getName() === $methodName) {
                $this->setActionName($actionName);
                return $methodName;
            }
        }
        return null;
    }

    /**
     * 从GET参数中绑定action方法的参数，并根据参数的类型进行简单验证
     * @param string $action 方法名
     * @param array $params GET参数
     * @return array 成功则返回action方法的参数列表
     * @throws Exception
     */
    public function bindActionParams($action)
    {
        $params  = $this->request();
        $method  = new ReflectionMethod($this, $action);
        $args    = $missing = [];
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                if ($param->isArray()) {
                    $args[] = is_array($params[$name]) ? $params[$name] : [$params[$name]];
                } else {
                    $args[] = $params[$name];
                }
                unset($params[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }
        if (!empty($missing)) {
            throw new Exception('miss param ' . implode(', ', $missing));
        }
        return $args;
    }

    /**
     * action方法的前钩子，子类覆盖时需return true或者parent::beforeAction，切记
     * @return boolean
     */
    protected function beforeAction()
    {
        return true;
    }

    /**
     * action方法的后钩子
     */
    protected function afterAction($result)
    {
        return $result;
    }

    /**
     * name=null时，返回$_GET+$_POST
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function request($name = null, $default = null)
    {
        $request = $this->_request;
        if ($name == null) {
            return $request;
        } else {
            return isset($request[$name]) ? $request[$name] : $default;
        }
    }

    /**
     * 返回$_POST
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function post($name = null, $default = null)
    {
        $request = $this->_request;
        $post    = Load::$app->getRequest()->post();
        if ($name == null) {
            foreach ($post as $key => $value) {
                if (isset($request[$key])) {
                    $post[$key] = $request[$key];
                }
            }
            return $post;
        } else {
            return isset($post[$name]) ? $request[$name] : $default;
        }
    }

    /**
     * 返回$_GET
     * @param string $name
     * @param mixed $default
     */
    public function get($name = null, $default = null)
    {
        $request = $this->_request;
        $get     = Load::$app->getRequest()->get();
        if ($name == null) {
            foreach ($get as $key => $value) {
                if (isset($request[$key])) {
                    $get[$key] = $request[$key];
                }
            }
            return $get;
        } else {
            return isset($get[$name]) ? $request[$name] : $default;
        }
    }

    /**
     * 返回当前控制器的名称
     * @return string
     */
    public function getControllerName()
    {
        $pos = strrpos($this->id, '/');
        if ($pos === false) {
            $className = $this->id;
        } else {
            $className = substr($this->id, $pos + 1);
        }
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $className)));
    }

    /**
     * 返回当前action的名称
     * @return string
     */
    public function getActionName()
    {
        if ($this->_actionName !== null) {
            return $this->_actionName;
        }
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $this->actionId))));
    }

    /**
     * 设置当前action的名称
     * @param string $name
     */
    public function setActionName($name)
    {
        $this->_actionName = $name;
    }

}
