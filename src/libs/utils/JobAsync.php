<?php
/**
 * Created by IntelliJ IDEA.
 * User: KowloonZh
 * Date: 17/1/11
 * Time: 下午7:56
 */

namespace libs\utils;


use Closure;
use frame\base\Exception;
use frame\base\God;

/**
 * 多进程任务处理类
 * Class JobAsync
 * @package common\libs
 * eg.
 * $job = new JobAsync();
 * for($i=0;$i<100;$i+=){
 *      $job->addTask($i,function($task){
 *
 *          sleep(1);
 *          return 'add'.$task;
 *      });
 * }
 * $result = $job->await();
 *
 * 测试及结论
 * fork 一个子进程的耗时约 35 ms (执行了1个0耗时的任务)
 * 1000 个任务, 单个 1ms , 工作进程 30个, 耗时 10323 ms
 * 1000 个任务, 单个 35ms , 工作进程 30个, 耗时 10887 ms
 * 1000 个任务, 单个 100ms, 工作进程 30个, 耗时 11991 ms
 * 1000 个任务, 单个 200ms, 工作进程 30个, 耗时 14499 ms
 * 1000 个任务, 单个 1s, 工作进程 30个, 耗时 40695 ms
 * 10000 个任务,单个 1ms, 工作进程 30个, 耗时 148789 ms
 * 10000 个任务,单个 100ms, 工作进程 30个, 耗时 161332 ms
 *
 * 建议只有当单个任务执行时间超过30ms时,再考虑使用此类
 */
class JobAsync extends God
{

    /**
     * 开启的子进程数
     * @var int
     */
    public $workNum = 20;

    /**
     * 任务的队列
     * @var array
     */
    protected $queues = [];

    /**
     * 任务的返回结果
     * @var array
     */
    protected $results = [];

    /**
     * 任务处理回调函数
     * @var Closure
     */
    protected $handler = null;

    /**
     * 未完成的任务id列表
     * @var array
     */
    protected $unDoneTaskIds = [];

    /**
     * 执行中的子进程 [pid=>taskId]
     * @var array
     */
    protected $pids = [];

    public function init()
    {
        if (PHP_SAPI != 'cli') {
            throw new Exception('异步任务只支持在cli模式下运行');
        }

        if (PHP_OS == 'Windows') {
            throw new Exception('异步任务只支持在类linux系统下运行');
        }

        parent::init();
    }

    /**
     * 添加一个任务
     * @param $task
     * @param Closure $handler // 任务的回调处理函数
     * @return string
     */
    public function addTask($task, Closure $handler)
    {

        $taskId = $this->genTaskId();

        $this->queues[$taskId] = $task;

        // 只设置一次
        if (empty($this->handler)) {
            $this->handler = $handler;
        }

        $this->unDoneTaskIds[] = $taskId;
        return $taskId;
    }

    /**
     * 一次添加多个任务
     * @param array $tasks
     * @param Closure $handler
     * @return array
     */
    public function addTasks(array $tasks, Closure $handler)
    {

        $taskIds = [];
        foreach ($tasks as $task) {
            $taskIds[] = $this->addTask($task, $handler);
        }

        return $taskIds;
    }

    public function getTasks()
    {
        return $this->queues;
    }

    /**
     * 异步运行所有的任务,并返回任务执行结果
     * @return array
     */
    public function await()
    {
        // 还有未完成的任务id
        while (count($this->pids) > 0 || count($this->unDoneTaskIds) > 0) {

            // 检测子进程执行状态
            $this->checkSubProcess();

            // 控制工作的子进程数
            if (count($this->pids) >= $this->workNum) {
                continue;
            }

            $unDoCount = count($this->unDoneTaskIds);
            if ($unDoCount) {
                // 取未完成数与进程工作数的最小值, 每次fork出多个子进程
                $num     = $this->workNum > $unDoCount ? $unDoCount : $this->workNum;
                $workNum = $num - count($this->pids);

                for ($i = 0; $i < $workNum; $i++) {
                    $taskId = array_shift($this->unDoneTaskIds);
                    $this->forkProcess($taskId);
                }
            }
        }

        return $this->results;

    }

    // 检测子进程任务是否完成
    protected function checkSubProcess()
    {
        foreach ($this->pids as $pid => $taskId) {

            $status = null;
            $subPid = pcntl_waitpid($pid, $status, WNOHANG);

            // 如果子进程执行完成
            if ($subPid == -1 || $subPid > 0) {

                $res = $this->readRes($taskId);

                $this->results[] = $res;

                unset($this->pids[$pid]);
            }
        }
    }

    // fork子进程并执行任务
    protected function forkProcess($taskId)
    {
        // 执行一个任务
        $task = $this->queues[$taskId];
        $pid  = pcntl_fork();
        switch ($pid) {
            case -1:
                // fail @todo log
                die('fork process failed');
                break;
            case 0:
                // children
                $res = call_user_func($this->handler, $task);

                // 有返回值
                if ($res !== null) {
                    $this->writeRes($taskId, $res);
                }

                exit(0);
                break;
            default:
                // main
                $this->pids[$pid] = $taskId;
                break;
        }
    }

    private function writeRes($key, $res)
    {
        file_put_contents($this->getFilename($key), json_encode($res));
    }

    private function readRes($key)
    {
        $res      = null;
        $filename = $this->getFilename($key);
        if (file_exists($filename)) {

            $data = file_get_contents($filename);

            @unlink($filename);

            $res = json_decode($data, true);

        }
        return $res;
    }

    private function getFilename($key)
    {
        return '/tmp/.job_async_jiu_' . $key;
    }

    public function genTaskId()
    {
        return md5(microtime(true) . uniqid('jiu') . rand(1000, 9000));
    }
}
