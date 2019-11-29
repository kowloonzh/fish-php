<?php

namespace common\consoles;
use libs\log\Loger;

/**
 * Description of Console
 * 业务命令行控制器基类
 * @author JIU
 */
declare(ticks=1);

class Console extends \libs\base\Console
{
    /**
     * 用来判断进程是否已接受到杀死的信号量
     * @var bool
     */
    private $_killed = false;

    protected function beforeAction()
    {
        // 注册一个 SIGUSR2 的信号处理器
        pcntl_signal(SIGUSR2, function ($signo) {
            if ($signo == SIGUSR2) {
                $this->_killed = true;
            }
        });
        return parent::beforeAction();
    }

    /**
     * 常驻进程去执行一个脚本
     * @param callable $func
     * @param int $millSecond // 每个任务的执行间隔毫秒数, 默认是间隔1000ms, 如果传0就是死循环, 如果传-1只执行一次
     */
    public function runLoop(callable $func, $millSecond = 1000)
    {

        while (true) {

            if ($this->_killed) {
                // 睡 n 秒等待新的项目软连完成
                sleep(5);
                break;
            }

            $func();

            Loger::di()->flush(true);

            // 只执行一次
            if ($millSecond == -1) {
                break;
            }

            if ($millSecond > 0) {
                usleep($millSecond * 1000);
            }
        }
    }


    /**
     * 执行命令行的脚本
     * @param $route
     * @param bool $async // 是否异步,默认同步,如果不影响后面的数据导入,可以异步
     * @throws \Exception
     */
    protected function runScript($route, $async = false)
    {
        $dir = \Load::getAlias('@root/src/task');

        $cmd = sprintf("cd %s;/usr/local/bin/php index.php %s >>/dev/null 2>&1 %s", $dir, $route, $async ? '&' : '');

        shell_exec($cmd);

        echo date('Y-m-d H:i:s') . ': ' . $route . ' done!' . PHP_EOL;
    }

}
