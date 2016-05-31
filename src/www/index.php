<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
/**
 * 导入自动加载类
 */
require dirname(__DIR__) . '/frame/Load.php';

/**
 * 获取web应用的配置数组
 */
$config = require dirname(__DIR__) . '/../config/web.php';

try {
    $app = new frame\web\App($config);
    $loginfo = [];  //日志信息
    $loginfo['node'] = posix_uname()['nodename'];   //主机名
    $loginfo['user_ip'] = $_SERVER['REMOTE_ADDR'];  //访问者ip
    //获取request组件将访问的Url和参数记录到日志
    $request = $app->getRequest();

    $loginfo['url'] = $request->getAbsoluteUrl();   //url
    $loginfo['params'] = $request->request();    //参数

    /**
     * 运行应用
     */
    $app->run();

    $loginfo['consume(ms)'] = $app->getConsumeTime();   //耗时

    \libs\log\Loger::info($loginfo, 'biz');
} catch (common\libs\ExceptionBiz $e) {
    header('content-type:text/html;charset=utf-8');
    $result = [
        'errno'  => $e->getCode(),
        'errmsg' => $e->getMessage(),
        'node'   => $loginfo['node'],
        'data'   => $e->getExtInfo(),
    ];
    \libs\log\Loger::error(['loginfo' => $loginfo, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'extinfo' => $e->getExtInfo()], 'biz');
    echo json_encode($result);
} catch (frame\base\Exception $e) {
    header('content-type:text/html;charset=utf-8');
    $result = [
        'errno'  => $e->getCode(),
        'errmsg' => $e->getMessage(),
        'node'   => $loginfo['node'],
        'data'   => [],
    ];
    \libs\log\Loger::error(['loginfo' => $loginfo, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'frame');
    echo json_encode($result);
} catch (\Exception $e) {
    header('content-type:text/html;charset=utf-8');
    //系统级别的错误
    $result['errno'] = \common\libs\Errors::getErrCode(\common\libs\Errors::ERR_SYSTEM);
    $result['errmsg'] = \common\libs\Errors::getErrMsg(\common\libs\Errors::ERR_SYSTEM);
    $result['node'] = $loginfo['node'];
    $result['data'] = [];
    \libs\log\Loger::error(array_merge(['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], $loginfo, $_SERVER));
    echo json_encode($result);
}

//打印并高亮函数 开发环境使用
function p($target, $bool = true)
{
    static $iii = 0;
    if ($iii == 0) {
        header('content-type:text/html;charset=utf-8');
    }
    //来自postman
    if(isset($_SERVER['HTTP_POSTMAN_TOKEN'])){
        if(is_array($target)){
            $json_str = json_encode($target);
            if(json_last_error()==JSON_ERROR_NONE){
                header('content-type:application/json');
                echo $json_str;exit;
            }
        }elseif(is_string($target)){
            json_decode($target,true);
            if(json_last_error()==JSON_ERROR_NONE){
                header('content-type:application/json');
                echo $target;exit;
            }
        }
        var_dump($target);exit;
    }else{
        echo '<pre>';
        $result = highlight_string("<?php\n" . var_export($target, true), true);
        echo preg_replace('/&lt;\\?php<br \\/>/', '', $result, 1);
    }
    $iii++;
    if ($bool) {
        exit;
    }
}
