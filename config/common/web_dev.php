<?php

/**
 * Web应用的配置文件
 */
return libs\utils\ArrayUtil::merge(require('web_base.php'), [
    /**
     * 设置应用的运行环境，取值范围 dev|test|beta|prod
     */
    'env'        => 'dev',
    /**
     * ================= 自定义配置区==================
     */
    /**
     * components中配置的对象或者对象的配置文件会被注入到容器中
     */
    'components' => [
        //数据库对象配置
        'db' => [
            'class'    => 'libs\db\DB',
            'dsn'      => 'mysql:host=localhost;dbname=fish;port=3306',
            'username' => 'root',
            'password' => '',
            'charset'  => 'utf8',
        ],
    ],
]);
?>
