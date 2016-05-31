<?php

namespace frame\console;

use frame\base\Exception;
use Load;

/**
 * Description of App
 * 命令行应用
 * @author KowloonZh
 */
class App extends \frame\base\App
{

    /**
     * 当前的console对象
     * @var Console
     */
    public $console;

    /**
     * 默认的console控制器id
     * @var string
     */
    public $defaultConsole = 'base';

    public function coreComponents()
    {
        return [
            'request' => ['class' => Request::className()],
        ];
    }

    public function run()
    {
        return parent::run();
    }

    public function handleRequest(\frame\base\Request $request)
    {
        list ($route, $params) = $request->resolve();
        if ($route === '') {
            $route = $this->defaultRoute;
        }
        if ($this->strictRouteMode && preg_match('/[A-Z]/', $route)) {
            throw new Exception('Route does not support capital letters [A-Z]');
        }
        $parts = $this->createConsole($route);
        if (is_array($parts)) {
            list($console, $actionId) = $parts;
            Load::$app->console = $console;
            return $console->run($actionId, $params);
        } else {
            throw new Exception('Unknown route ' . $route);
        }
    }

    /**
     * 
     * @param string $route
     * @return Console
     * @throws \frame\base\Exception
     */
    public function createConsole($route)
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
            $console = $this->createConsoleById($id);
            if ($console) {
                break;
            }
        }
        if ($console === null) {
            return false;
        }
        //console控制器必须继承自frame\console\Console或其子类
        if (!is_subclass_of($console, Console::className())) {
            throw new Exception('The console must to be the subclass of ' . Console::className());
        }
        $actionId = !empty($path) ? end($path) : '';
        return [$console, $actionId];
    }

    public function createConsoleById($id)
    {
        $consoleId = $id;
        //获取模块名根据id，只支持单层模块
        $module    = $this->getModuleNameById($id);

        if ($module !== false && $id === '') {
            return null;
        }

        if ($module === false) {
            $module = 'app';
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
            //@TODO log
            return null;
        }
        if ($prefix !== '' && !preg_match('/^[a-zA-Z0-9_\/]+$/', $prefix)) {
            //@TODO log
            return null;
        }
        //驼峰命名的控制器可以用短横分隔来请求 admin-hulk/index 相当于 AdminHulkConsole::indexAction()
        $className        = str_replace(' ', '', ucwords(str_replace('-', ' ', $className))) . 'Console';
        //控制器的命名空间
        $consoleNamespace = $module . '/consoles/' . ltrim($prefix . '/', '/') . $className;
        //如果控制器类文件不存在 返回空
        $consoleFile      = Load::getAlias('@' . $consoleNamespace . '.php');
        if (!file_exists($consoleFile)) {
            return null;
        }
        $consoleName = str_replace('/', '\\', $consoleNamespace);
        return static::createObject($consoleName, ['id' => $consoleId, 'moduleId' => $module]);
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
