<?php

/**
 * Description of Load
 * 框架引入类
 * @author KowloonZh
 */
class Load
{

    /**
     * 应用的单例
     * @var \frame\base\App|\frame\web\App|frame\console\App
     */
    public static $app; //当前的应用实例

    /**
     * 应用中的路径别名
     * @var array 
     */
    public static $aliases = [];

    /**
     * 设置路径别名
     * @param string $alias
     * @param string $path 别名对应的路径，可以是含别名的路径
     * @throws \Exception 当$alias中包含/时 抛出异常
     */
    static public function setAlias($alias, $path)
    {
        //如果别名不带@ 默认加上
        if (strncmp($alias, '@', 1)) {
            $alias = '@' . $alias;
        }
        $pos = strpos($alias, '/');
        if ($pos !== false) {
            throw new Exception('The alias name can not contain "/"');
        }
        if ($path !== null) {
            //设置路径
            $realpath = rtrim($path, '\\/');
            $path = strncmp($path, '@', 1) ? $realpath : static::getAlias($realpath);

            static::$aliases[$alias] = $path;
        } else {
            //销毁别名
            unset(static::$aliases[$alias]);
        }
    }

    /**
     * 根据路径别名获取路径(已去掉路径尾部的/)
     * @param string $alias
     * @param boolean $throwException
     * @return boolean|string
     * @throws Exception
     */
    static public function getAlias($alias,$throwException=true)
    {
        //如果不带@直接返回
        if (strncmp($alias, '@', 1)) {
            return rtrim($alias, '\\/');
        }
        $pos  = strpos($alias, '/');
        //获取别名
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
        if (isset(static::$aliases[$root])) {
            //如果存在别名 则返回全路径
            $path = $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
            return rtrim($path, '\\/');
        }
        if($throwException){
            throw new Exception("Path of Alias {$alias} can not find.");
        }
        return false;
    }

    /**
     * 根据class的路径自动加载对应的类文件
     * @param string $className
     * @return 
     */
    static public function autoload($className)
    {
        if (strpos($className, '\\') !== false) {
            $classFile = static::getAlias('@' . str_replace('\\', '/', $className), false).'.php';
            if($classFile===false || !is_file($classFile)){
                return;
            }
            include($classFile);
        }
        return;
    }
}

spl_autoload_register(['Load', 'autoload'], true, true);
Load::setAlias('@frame', __DIR__);
Load::setAlias('@libs', dirname(__DIR__).'/libs');
