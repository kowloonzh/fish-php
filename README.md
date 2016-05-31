# fish
简易的后端接口框架
### web路由功能
1. 默认路由 /home/default/index
http://lon.com:8360/

2. 支持目录分级
http://lon.com:8360/home/admin/base/index 
访问的是@root/src/app/home/controllers/admin/BaseController/indexAction()
http://lon.com:8360/home/admin/base  `get($k,$default)` `post($k,$default)` `request()` 

5. 获取当前的控制器id,和actionid
    `$this->id`  `$this->actionId`

6. web应用的返回值
    >如果action有返回值， 字串则直接输出，数组则json化之后输出


### 入口以及web配置详解
1. 入口文件index.php---config.php frame\web\App

2. app的属性,以及属性的使用

3. DI容器中的对象的注入（components数组）,容器中对象的注入`set`和使用`get`,查看`getComponents`当前容器中的对象|对象配置,`di()`方法(业务中使用?)

4. 自定义App类，更灵活的使用配置文件


### 命令行路由|应用
1. 命令行路由使用(默认路由 home/default/help) php index.php

2. 新建一个命令行控制器@root/src/app/home/consoles/TestConsole::indexAction,  php index.php home/test/index

3. 命令行下的参数 --param=value
    `request($k,$default=null)`


### 数据库操作
1. Dao相关操作 libs\base\Dao

2. 数据库连接 libs\db\DB,在配置中注入到容器中

3. 数据库操作类 libs\db\Query

4. 事务操作(嵌套)

### 日志操作
1. 日志示例

2. 日志配置

3. 日志扩展






