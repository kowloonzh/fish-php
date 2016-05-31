<?php

/**
 * Web应用的配置文件
 */
return [
    /**
     * 是否开启debug
     */
    'debug'      => true,
    /**
     * 设置应用的运行环境，取值范围 dev|test|beta|prod
     */
    'env'        => 'dev',
    /**
     * 设置应用的根目录 默认会生成@root的路径别名 在配置的其他地方可以使用@root
     */
    'basePath'   => dirname(__DIR__) . '/../',
    /**
     * 路径别名 @root对应的是basePath的路径,设置路径别名会在命名空间中注册一个根空间
     */
    'aliases'    => [
        'common' => '@app/common',
    ],
    /**
     * 模块列表,只有设置的模块才能在路由中访问
     * [moduleName=>modulePath]
     */
    'modules'    => [
        'home'   => '@app/home',
    ],
    /**
     * 日志目录 默认为basePath/logs
     */
//    'logPath'=>  '@root/logs',
    /**
     * 默认路由 当前url中没有pathinfo时采用，默认为home/DefaultController
     */
//    'defaultRoute'=>'home/default',
    /**
     * 时区设置，默认取php.ini中的时区，如果php.ini中没有设置，则取PRC时区
     */
//    'timeZone'=>'PRC',
    /**
     * ================= 自定义配置区==================
     */
    /**
     * components中配置的对象或者对象的配置文件会被注入到容器中
     */
    'components' => [
        //日志对象配置
        'log'  => [
            'class'   => 'libs\log\Loger',
            'targets' => [
                'file'  => ['class' => 'libs\log\FileTarget'],
            ],
        ],
        //邮件发送组件
        'mail' => [
            'class' => 'common\libs\MailSender'
        ],
    ],
];
?>
