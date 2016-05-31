<?php

namespace frame\web;

use frame\base\Exception;
use Load;

/**
 * Description of App
 * web应用类
 * @author KowloonZh
 */
class App extends \frame\base\App
{

    /**
     * 当前的控制器对象
     * @var Controller 
     */
    public $controller;

    /**
     * 返回应用的核心组件
     * @return array
     */
    public function coreComponents()
    {
        return [
            'request'  => ['class' => Request::className()],
            'response' => ['class' => Response::className()],
        ];
    }

    /**
     * 运行web应用，返回response对象
     * @return Response
     */
    public function run()
    {
        $response = parent::run();
        /**
         * 输出返回结果
         */
        $response->send();
        return $response;
    }

    /**
     * 处理request请求
     * @param object $request Request
     * @return Response
     * @throws Exception
     */
    protected function handleRequest(\frame\base\Request $request)
    {
        /**
         * Request::resolve()方法返回路由和request参数
         */
        list($route, $params) = $request->resolve();
        if ($route === '') {
            $route = $this->defaultRoute;
        }
        if ($this->strictRouteMode && preg_match('/[A-Z]/', $route)) {
            throw new Exception('Route does not support capital letters [A-Z]');
        }
        /**
         * 根据路由创建控制器对象和action名字,解析路由失败则抛出异常
         */
        $parts = $this->createController($route);
        if (is_array($parts)) {
            /**
             * 获取生成的controller对象和actionId
             */
            list($controller, $actionId) = $parts;
            /**
             * 将controller对象赋值应用的controller属性，在应用的任何地方，都可以调用FrameApp::$app->controller获取当前控制器对象
             */
            $this->controller = $controller;

            /**
             * 根据actionId，访问控制器对应的action方法获取结果集
             */
            $result = $controller->run($actionId, $params);
            /**
             * 将结果集转化为Response对象返回
             */
            if ($result instanceof Response) {
                return $result;
            } else {
                $response       = $this->getResponse();
                $response->data = $result;
                return $response;
            }
        } else {
            throw new Exception('Unknown route ' . htmlspecialchars($route));
        }
    }

    /**
     * 返回web响应类
     * @return Response
     */
    public function getResponse()
    {
        return Load::$app->get('response');
    }

    /**
     * 根据路由创建控制器实例,成功则返回控制器实例和actionId组成的数组
     * @param string $route
     * @return boolean|array
     */
    public function createController($route)
    {
        if ($route === '') {
            throw new Exception('The defaultRoute can not be empty');
        }
        //禁止url中出现2个/
        if (strpos($route, '//') !== false) {
            return false;
        }
        $path = explode('/', trim($route, '/'));
        $id   = '';   //控制器的id
        foreach ($path as $val) {
            $id = ltrim($id . '/' . $val, '/');
            array_shift($path);
            if (count($path) >= 2) {    //表示是个目录
                continue;
            }
            //根据控制器id创建控制器实例 创建成功则跳出循环
            $controller = $this->createControllerById($id);
            if ($controller) {
                break;
            }
        }
        if ($controller === null) {
            return false;
        }
        //控制器必须继承自frame\web\Controller或其子类
        if (!is_subclass_of($controller, Controller::className())) {
            throw new Exception('The controller must to be the subclass of ' . Controller::className());
        }
        $actionId = !empty($path) ? end($path) : '';
        return [$controller, $actionId];
    }

    /**
     * 根据路由里的控制器id生成控制器对象
     * @param string $id
     * @return Controller
     */
    public function createControllerById($id)
    {
        $controllerId = $id;
        //获取模块名根据id，只支持单层模块
        $module       = $this->getModuleNameById($id);

        if ($module !== false && $id === '') {
            return null;
        }

        if ($module === false) {
            $module = 'app';
			$id = $controllerId;
        }
        $pos = strrpos($id, '/');
        if ($pos === false) {
            $prefix    = '';
            $className = $id;
        } else {
            $prefix    = substr($id, 0, $pos);
            $className = substr($id, $pos + 1);
        }
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9\\-_]*$/', $className)) {
            return null;
        }
        if ($prefix !== '' && !preg_match('/^[a-zA-Z0-9_\/]+$/', $prefix)) {
            return null;
        }
        //驼峰命名的控制器可以用短横分隔来请求 admin-hulk/index 相当于 AdminHulkController::indexAction()
        $className           = str_replace(' ', '', ucwords(str_replace('-', ' ', $className))) . 'Controller';
        //控制器的命名空间
        $controllerNamespace = $module . '/controllers/' . ltrim($prefix . '/', '/') . $className;
        //如果控制器类文件不存在 返回空
        $controllerFile      = Load::getAlias('@' . $controllerNamespace . '.php');
        if (!file_exists($controllerFile)) {
            return null;
        }

        $controllerName = str_replace('/', '\\', $controllerNamespace);
        return static::createObject($controllerName, ['id' => $controllerId, 'moduleId' => $module]);
    }

    //根据路由id获取模块名称
    protected function getModuleNameById(&$id)
    {
        $pos = strpos($id, '/');
        if ($pos === false) {
            $module = $id;
            $id     = '';
        } else {
            $module = substr($id, 0, $pos);
            $id     = substr($id, $pos + 1);
        }
        if (isset($this->modules[$module])) {
            return $module;
        } else {
            return false;
        }
    }

}
