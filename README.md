# fish-php
简易的 php 后端接口框架

## 快速开始
### 1. 克隆
```
// 执行命令：
git clone git@github.com:KowloonZh/fish-php.git $app_name
```

备注: 其中 `$app_name` 替换成你的应用名称即可

### 2. 初始化项目配置文件
只需要在根目录执行： `./env.init.sh [user]` 这将初始化项目的 web 应用配置 `(web_[user].php)`，和 nginx 配置 `(nginx_conf_[user].php)` 。

修改 `configs/nginx/nginx_conf_[user].php` 中的 `server_name` 以适配您应用的域名

将 nginx 配置软连到 nginx 配置目录下 `cd /usr/local/nginx/conf/include/ && sudo ln -sf app_path/configs/nginx_conf.php your_domain.conf`

此时，访问 `http:your_domain:your_port` 既可以看到 `hello,world`

备注：[user] 替换成对应开发人员的字母，比如：`./env.init.sh local`, 就会在 configs 下创建相关配置文件的软链接。

### 3.  模块
#### 3.1 默认模块
默认的应用模板已经为我们创建好了一个 home 模块，在 `src/app/home` 目录下，一个完整的模块应包含 `controllers,consoles,models,daos` 目录

#### 3.2 新增模块
在应用的根目录下执行模块初始化脚本 `php src/task/index.php home/default/gen --module=module_name` 即可在 src/app 目录下生成一个 module_name 的目录

然后在 configs/web.php 中的新增一行模块如下

```
    'modules'    => [
        'home' => '@app/home',
        //add new module path
        'module_name' => '@app/module_name',
    ],
```
注：module_name 为您的模块名称

### 4. 路由
#### 4.1 路由说明
比如访问： `/home/front/foo/bar`
表示访问的是 `src/app/home/controllers/front/FooController.php` 文件的 `barAction` 方法
其中 home  为模块名，front 为模块下的子目录，foo 为 FooController.php 控制器类的 id，bar 为控制器的 barAction 方法的 actionId
在控制器中使用 `$this->moduleId` 获取模块名，此处为 home，`$this->id` 可获取完整的路由，此处为 `/home/front/foo/bar` ,`$this->actionId` 表示方法名，此处为 bar 

#### 4.2 路由大小写
默认不支持大写的路由，如果想要访问控制器 `/home/controllers/front/AaBbController.php` 的 ccDdAction 方法话，访问的路由应该为：`/home/front/aa-bb/cc-dd`

#### 4.3 默认路由
默认路由为 `/home/default/index` 
在 configs/config.php 中可以修改 defaultRoute 自定义您想要的默认路由

### 5.  配置说明
#### 5.1 配置的分类
配置总体分三类：应用 App 的属性配置，应用的容器配置，自定义配置

#### 5.2 应用的属性配置与使用
在 configs/config.php 中类似 debug,env,basePath 等都属于 App 的成员变量
在应用的任何地方访问 `\Load::$app->$config_name` 既可获取对应的配置
注：`$config_name` 为配置的名称

#### 5.3 应用的容器配置
在 configs/config.php 中 components 数组里的每一个元素都是一个容器对象
一般需要全局访问并要求是单例的对象，或者一个对象在不同的环境的配置不一样时，我们就可以将对象配置在 components 数组中作为一个容器对象来使用
下面是两个比较典型的应用场景：

```
'components' => [
        //数据库对象配置 db表示容器对象的id
        'db'   => [
            'class'    => 'libs\db\DB',//class字段表示类名，必须设置
            'dsn'      => 'mysql:host=localhost;dbname=fish;port=3306',
            'username' => 'root',
            'password' => '',
            'charset'  => 'utf8',
        ],
       //第三方接口
        'third_api' => [
            'class'    => 'third\ThirdApi',
            'domain'   => 'http://127.0.0.1:8080/',
            'hostname' => 'localhost',
        ],
```

使用容器对象:
访问 `\Load::$app->get($id)` 可获取实例化后的容器对象
如果预设类名不会发生变化，可以通过 `libs\db\DB::di($id)` 方法获取到容器对象，这种方式对 IDE 比较友好，

注：$id 为容器对象的 id, 上例中分别为 "db"，"third_api"，通过 di 方法获取时的类名就是配置中 class 对应的值，上例分别为 `libs\db\DB` 和 `third\ThirdApi`

#### 5.4 自定义配置和使用
应用难免需要自己的配置，自定义配置和应用的属性配置和使用是一样一样的

### 6.  自动加载与路径别名
#### 6.1 路径别名的设置和使用
路径别名其实是一个路径的 map, key 是路径的别名，value 是路径的绝对路径值
如：@tmpdir =>/tmp
设置路径别名可以在 configs/web.php 中的 aliases 设置

```
    /**
     * 路径别名 设置路径别名会在命名空间中注册一个根空间 tmpdir，common都是一个命名空间的根空间
     */
    'aliases'    => [
        'tmpdir'    =>'/tmp'
        'common' => '@app/common',
    ],
```

全局的别名可以通过 `\Load::$aliases` 查看
单个别名的值可以通过 `\Load::getAlias('@app')` ,访问时需要带上 @ 符号

注：配置中的 common 的值使用了上面配置的 app 别名，配置文件中的 modules 配置的每个模块默认会生成一个路径别名，配置时防止与 aliases 中配置的名称冲突

#### 6.2 自动加载的机制
在入口文件index.php中require框架 frame/Load.php之后，就拥有的自动加载的功能
当访问 frame\web\App 这个类时，其实是在访问 @frame对应的路径/web/App.php文件

### 7. 控制器
#### 7.1 控制器中获取各种参数
在 Controller 中使用 $this->request() 可获取所有的参数包含（$_GET 和 $_POST），`$this->request($name,$default=null)` 返回具体某个参数的值
`$this->get()` 返回过滤后的 $_GET 参数，`$this->get($name,$default=null)`，返回具体某个参数的值
`$this->post()` 返回过滤后的 $_POST 参数，`$this->post($name,$default=null)`，返回具体某个参数的值
`$this->id` 返回全路由
`$this->moduleId` 返回模块名
`$this->actionId` 返回 action 的名

#### 7.2 控制器参数过滤
在 Controller 中实现 filters 方法即可过滤访问的参数，用 `$this->request()` 方法获取的是过滤之后的值，配置方法如下：

```
    //过滤请求的字段
    public function filters()
    {
        return [
            // word 表示要过滤的参数，多个之间用逗号分隔，trim 是要使用的回调函数
            ['word','trim']
        ];
    }
```

#### 7.3 控制器参数验证
在 Controller 中实现 rules 方法即可验证访问的参数，如：
```

    // 验证请求的字段
    public function rules()
    {
        return [
            // word 是被验证的参数，多个之间用逗号分隔
            // string 是验证器的名称，验证器定义在 libs\utils\Validator.php 文件的静态方法中
            // on 表示需要验证的参加，如果没设置，表示所有的场景都要验证，在 controller 中的场景名就是 actionId
            ['word','string','on'=>'index'],
            // when 是一个有效的 php 回调，表示当...的时候，才执行这个验证，一般是当另一个请求参数符合某个条件就需要执行这个验证
            //allowEmpty 是 json 静态方法的参数
            ['busi_roles', 'json', 'allowEmpty' => false, 'on' => 'add','when'=>[$this,'whenRoleEmpty']],
        ];
    }
```

注:更多的验证器请查看文件 libs\utils\Validator.php 中的静态方法

#### 7.4 控制器返回值
控制器基类中定义了 `$this->_msg` 属性，

```

    protected $_msg = [
        'errno'  => 0,
        'errmsg' => '',
        'data'   => [
        ]
    ];
```

一般需要返回 json 串时，在 action 方法中的最后 `return $this->_msg` 即可
`$this->setData($data)` 方法可以设置 $_msg 的 data 的值

### 8.  数据库操作
#### 8.1 数据库配置
在 configs/config.php 中配置一个 db 容器对象，如下：

```
'components' => [
        // 数据库对象配置
        'db'   => [
            'class'    => 'libs\db\DB',// class字段表示类名，必须字段
            'dsn'      => 'mysql:host=localhost;dbname=fish;port=3306',
            'username' => 'root',
            'password' => '',
            'charset'  => 'utf8',
        ],
```

#### 8.2 Dao 数据访问对象类文件创建
在数据表所在模块的 daos 目录下新建一个 dao 类文件，并继承自 `\common\daos\Dao` 或者其子类
```
namespace user\daos;

/**
 * Description of UserDao
 *
 * @author JIU
 */
class UserDao extends BaseDao
{
    // 静态方法tableName返回Dao对应的表名
    public static function tableName()
    {
        return 'user';
    }


}
```

#### 8.3 使用 Dao 来操作数据库
```
// 增加: 
UserDao::insert(['name'=>'kowloonzh','age'=>3]);

//修改：
UserDao::update(['age'=>18],['id'=>1]);

// 删除：
UserDao::delete(['id'=>1]);

// 查询单行：
UserDao::queryRow(['id'=>1]);

// 查询多行：
UserDao::queryAll(['age'=>18]);

// 查询单列：
UserDao::queryColumn('name','id>:id',[':id'=>3]);

// 查询单行单列：
UserDao::queryOne('count(1)',['age'=>18]);

```
更多更详细的 Dao 操作说明请参考 libs/base/Dao.php的文档注释说明

#### 8.3.2 使用 Dao 来操作数据库复杂的查询语句
```
// select * from user where id = 1;
UserDao::find()->where(['id'=>1])->queryRow();

// select * from user where id > 1 and age = 18 limit 10 offset 5 order by id desc;
UserDao::find()->where('id>:id',[':id'=>1])->andWhere(['age'=>18])->limit(10,5)->order('id desc')->queryAll();

// select id from user where name like 'zhang%';
UserDao::find()->select('id')->where(['like','name','zhang%'])->queryAll();

// select * from user where name like 'zhang%' or age = 18;
UserDao::find()->where(['or',['like','name','zhang%'],['age'=>18]])->queryAll();

// select t.,r.label as role_label from user as t join role as r on r.uid=t.id where t.id>3 and r.role = 1;
UserDao::find()
      ->select('t.*,r.label as role_label')
      ->join(RoleDao::tableNameAlias('r'),'r.uid=t.id')
      ->where('t.id>:id',[':id'=>3])
      ->andWhere(['r.role'=>1])
      ->queryAll();
```

#### 8.4 直接使用sql来操作数据库
```
// 执行，返回影响行数
libs\db\Db::di()->createQuery($sql)->execute(); 

// 查询单行
libs\db\Db::di()->createQuery($sql)->queryRow(); 

// 查询返回单列
libs\db\Db::di()->createQuery($sql)->queryColumn(); 

// 查询返回多行
libs\db\Db::di()->createQuery($sql)->queryAll(); 

// 查询返回单行单列
libs\db\Db::di()->createQuery($sql)->queryOne(); 

```

#### 8.5 事务的使用

```
$db = libs\db\Db::di();
$trans = $db->beginTransaction();        // $trans为libs\db\Transaction 对象实例
try{
     $db->createQuery()->insert($table,$columns);
     $db->createQuery()->insert($table,$columns);
      ....
      $trans->commit();
 }catch(\Exception $e){
      echo $e->getMessage();
      $trans->rollback($e);
 }
```
注：事务可以嵌套使用

### 9. 命令行应用
#### 9.1 命令行路由
命令行应用的路由与web应用的路由相似

例如：在项目根目录下访问 `php src/task/index.php /home/default/index` 

home 表示 home 模块，对应 src/app/home 目录
default 表示命令行控制器，对应 consoles 目录下 DefaultConsole.php
index 表示 action 方法，对应 indexAction 方法
所以，上面的路由访问的是 `src/app/home/consoles/DefaultConsole::indexAction` 方法

#### 9.2 命令行参数
命令行参数只支持 `--param=value` 的形式
例如：
`php src/task/index.php /home/default/gen --module=admin`
在命令行控制器的 action 中可以在参数中获取

`public function genAction($module){}`

也可以通过 `$this->request($module);` 来获取参数

#### 9.3 命令行控制器
命令行控制器与 web 应用的 Controller 控制器相似，只是命令行控制器都放在 consoles 目录，继承自 frame\console\Console 类或者其子类

#### 9.4 计划任务
在 src/task/cron_list.php 中可设定计划任务列表
例如：
```
//每天晚上01:30

if (ifRun('30 01 * * *')) {
    runScript('index.php home/log/clean');   // 清理日志
}
```
cron_list.php 中的计划任务时间设置与 Linux 一样 分时日月周
设置好后在 `crontab -e` 中添加一行如下：
```
* * * * * cd ${project}/src/task;/usr/local/bin/php cron_list.php >> cron_list.log 2>&1
```
此时计划任务已经生效

#### 9.5 秒级计划任务
采用 runScript() 保障只有一个进程,如下:
```
if (ifRun('* * * * *')) {
    // 每分钟执行一次,并且只保证一个进程
    runScript('index.php home/task/listen');
}
```
在 TaskConsole 的 listenAction 中设置 while(true) 死循环，并每次循环 sleep(1)，保障此方法每秒运行一次，代码示例如下：

```
class TaskConsole extends BaseConsole
{

    // 监听任务 注意：此命令需要用root权限调用 sudo /usr/local/php/bin/php home/task/listen
    public function listenAction()
    {
        // 第二个参数表示每个调用之间的时间间隔，单位：毫秒，0 表示不间隔，-1 表示只执行一次
        $this->runLoop(function(){
           // 执行您的逻辑
        }, 0);
    }
}
```

#### 9.6 同时运行多个任务进程
类似 kafka 消费队列的场景，使用如下：
```
if (ifRun('* * * * *')) {
    // 第2个参数，表示一共起几个进程数
    runScript('index.php home/kafka/consumer',4);
}
```

