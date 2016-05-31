<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

/**
 * 导入自动加载类
 */
require dirname(__DIR__).'/frame/Load.php';

/**
 * 获取console应用的配置数组
 */
$config = libs\utils\ArrayUtil::merge(require(dirname(__DIR__) . '/../config/web.php'), [
    /**
     * 日志目录 默认为basePath/logs
     */
    'logPath' => '@root/logs/task',
]);

/**
 * 创建console应用
 */

try {
    $app = new frame\console\App($config);
    ob_start();
    /**
     * 运行应用
     */
    $result = $app->run();
    /**
     * 处理返回结果
     */
    if(is_array($result)){
        $result = print_r($result,true);
    }
    $res = ob_get_clean().$result;
    /**
     * 记录日志
     */
    $loginfo = [
        'access_time'=>date('Y-m-d H:i:s',$app->startTime),
        'consume(ms)' => $app->getConsumeTime(),               //耗时
        'res'=> $res
    ];
    \libs\log\Loger::info($loginfo, str_replace('/', '.', $app->console->id.'/'.$app->console->actionId));
    echo $res;
    exit(0);
} catch (\Exception $e) {
    //记录异常错误日志
    \libs\log\Loger::error(['message'=>$e->getMessage(),'trace'=>$e->getTraceAsString()]);
    echo frame\console\Console::ansiFormat($e->getMessage(), [frame\console\Console::FG_RED]);
    exit(1);
}
?>

