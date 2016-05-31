<?php

namespace frame\console;

use frame\base\Exception;
use frame\base\Object;
use Load;
use ReflectionClass;
use ReflectionMethod;

/**
 * Description of Console
 * 命令行控制器基类
 * 
 * 使用方法
 * php path/to/entry_script.php <route> [--param1=value1 --param2 ...]
 * 默认的route为 $defaultRoute/help
 * 
 * @author KowloonZh
 */
class Console extends Object
{

    const FG_BLACK    = 30;
    const FG_RED      = 31;
    const FG_GREEN    = 32;
    const FG_YELLOW   = 33;
    const FG_BLUE     = 34;
    const FG_PURPLE   = 35;
    const FG_CYAN     = 36;
    const FG_GREY     = 37;
    const BG_BLACK    = 40;
    const BG_RED      = 41;
    const BG_GREEN    = 42;
    const BG_YELLOW   = 43;
    const BG_BLUE     = 44;
    const BG_PURPLE   = 45;
    const BG_CYAN     = 46;
    const BG_GREY     = 47;
    const RESET       = 0;
    const NORMAL      = 0;
    const BOLD        = 1;
    const ITALIC      = 3;
    const UNDERLINE   = 4;
    const BLINK       = 5;
    const NEGATIVE    = 7;
    const CONCEALED   = 8;
    const CROSSED_OUT = 9;
    const FRAMED      = 51;
    const ENCIRCLED   = 52;
    const OVERLINED   = 53;

    public $id;
    public $moduleId;
    public $actionId;
    public $defaultAction = 'help';

    public function run($actionId, $params)
    {
        /**
         * 如果没有指定actionid，则使用默认的actionid
         */
        if ($actionId === '') {
            $actionId = $this->defaultAction;
        }
        $this->actionId = $actionId;
        $result         = null;
        //根据actionid生成action的方法名
        $action         = $this->resolveActionMethod($actionId);
        if ($action === null) {
            throw new Exception('Fail to resolve ' . htmlspecialchars($this->id . '/' . $actionId));
        }
        //执行action前钩子
        if ($this->beforeAction()) {
            /**
             * 绑定并验证action方法的参数
             */
            $args   = $this->bindActionParams($action, $params);
            $res    = call_user_func_array(array($this, $action), $args);
            //执行action后钩子
            $result = $this->afterAction($res);
        }
        return $result;
    }

    /**
     * 根据actionid解析action的方法名
     * @param string $actionId
     * @return string
     */
    protected function resolveActionMethod($actionId)
    {
        if (preg_match('/^[a-zA-Z0-9\\_-]+$/', $actionId) && strpos($actionId, '--') === false && trim($actionId, '-') === $actionId) {
            $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $actionId)))) . 'Action';
            $method     = new ReflectionMethod($this, $methodName);
            if ($method->isPublic() && $method->getName() === $methodName) {
                return $methodName;
            }
        }
        return null;
    }

    /**
     * 从GET参数中绑定action方法的参数，并根据参数的类型进行简单验证
     * @param string $action 方法名
     * @param array $params 脚本参数
     * @return array 成功则返回action方法的参数列表
     * @throws Exception
     */
    public function bindActionParams($action, $params)
    {
        $method  = new ReflectionMethod($this, $action);
        $args    = $missing = array();
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                //参数中数组用逗号隔开来标示
                if ($param->isArray()) {
                    $args[] = preg_split('/\s*,\s*/', $params[$name]);
                } elseif (!is_array($params[$name])) {
                    $args[] = $params[$name];
                } else {
                    throw new Exception('Wrong param ' . $name);
                }
                unset($params[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }
        if (!empty($missing)) {
            throw new Exception('Missing param ' . implode(', ', $missing));
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
     * @param mixed $result
     * @return mixed
     */
    protected function afterAction($result)
    {
        return $result;
    }

    /**
     * 帮助Action，用来显示当前的console可操作的action
     * @return string
     */
    public function helpAction()
    {
        $route = Load::$app->getRequest()->getPathInfo();
        if (empty($route)) {
            $this->showConsolesHelp();
        } else {
            $this->showActionsHelp();
        }
    }

    //显示console级别的提示信息
    protected function showConsolesHelp()
    {
        $help       = 'Usage: php ' . $this->getScriptName() . ' ' . 'console[/help]';
        $consoles   = $this->findConsoles();
        $help       = static::ansiFormat($help . "\n", [static::BOLD, static::FG_CYAN]);
        $consolestr = "Consoles:\n";
        if (empty($consoles)) {
            $consolestr .='Do not have console yet.';
        } else {
            foreach ($consoles as $console) {
                $consolestr .= '      ' . $console . "\n";
            }
        }
        echo $help . $consolestr;
    }

    //显示action级别的提示信息
    protected function showActionsHelp()
    {
        $help    = 'Usage: php ' . $this->getScriptName() . ' ' . $this->id;
        $options = $this->getOptionHelp();
        if (empty($options)) {
            echo static::ansiFormat($help . "\n", [static::BOLD, static::FG_GREEN]);
            return;
        }
        if (count($options) == 1) {
            echo static::ansiFormat($help . '/' . $options[0] . "\n", [static::BOLD, static::FG_GREEN]);
            return;
        }
        $help.= "/<action>\n";
        $help      = static::ansiFormat($help, [static::BOLD, static::FG_GREEN]);
        $actionstr = "Actions:\n";
        foreach ($options as $option) {
            $actionstr .= '      ' . $option . "\n";
        }
        echo $help . $actionstr;
    }

    /**
     * 返回当console的action提示项
     * @return string
     */
    public function getOptionHelp()
    {
        $options = [];
        $class   = new \ReflectionClass(get_class($this));
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if (strlen($name) > 6 && substr($name, -6) == 'Action') {
                $name = substr($name, 0, -6);
                $help = self::upper2lower($name);
                foreach ($method->getParameters() as $param) {
                    $optional     = $param->isDefaultValueAvailable();
                    $defaultValue = $optional ? $param->getDefaultValue() : null;
                    if (is_array($defaultValue)) {
                        $defaultValue = str_replace(array("\r\n", "\n", "\r"), '', print_r($defaultValue, true));
                    }
                    $name = $param->getName();
                    if ($optional) {
                        $help .= " [--$name=$defaultValue]";
                    } else {
                        $help .= " [--$name=value]";
                    }
                }
                $options[] = $help;
            }
        }
        return $options;
    }

    /**
     * 获取当前执行的脚本名 eg: php index.php help 返回 index.php
     * @return string
     */
    public function getScriptName()
    {
        return Load::$app->getRequest()->getScriptUrl();
    }

    /**
     * 获取可以访问的console控制器
     * @return array
     */
    public function findConsoles()
    {
        $modules = array_merge(Load::$app->modules, ['app'=>'@app']);
        foreach ($modules as $module => $modulePath) {
            $consolePath = Load::getAlias($modulePath.'/consoles');
            if(!is_dir($consolePath)){
                continue;
            }
            $files = scandir($consolePath);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $file_path = $consolePath .'/' . $file;
                if (substr_compare($file_path, 'Console.php', -11, 11, true) === 0 && is_file($file_path)) {
                    $consoles[] = ($module==='app'?'':$module.'/') . self::upper2lower(substr($file, 0, -11));
                }
            }
        }
        return $consoles;
    }

    /**
     * 根据给定的ANSI风格对$string进行格式化(着色)
     * @param mixed $string
     * @param array $color 可以同时给定前景色，背景色，字体样式
     * eg:
     * 如果想给hello,world加上红色的前景，灰白色的背景，并且加粗
     * \frame\console\Console::ansiFormat('hello,world',[\frame\console\::FG_RED,\frame\console\::BG_GREY,\frame\console\::BOLD])
     * @return string
     */
    static public function ansiFormat($string, $color = [])
    {
        $code   = implode(';', $color);
        $string = (is_scalar($string) && !is_bool($string)) ? $string : var_export($string, true);
        $res    = "\033[0m" . ($code !== '' ? "\033[" . $code . "m" : '') . $string . "\033[0m";
        return $res;
    }

    //返回脚本参数
    public function request($name = null, $default = null)
    {
        return Load::$app->getRequest()->request($name, $default);
    }

    //按大写字母分割字串
    static public function upper2lower($string)
    {
        $arr = preg_split('/(?=[A-Z])/', lcfirst($string));
        return implode('-', array_map('strtolower', $arr));
    }

}
