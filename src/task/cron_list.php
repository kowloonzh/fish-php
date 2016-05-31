<?php

/**
 * 计划任务列表
 */
//每天晚上01:30
if (ifRun('30 01 * * *')) {
    //runScript('index.php home/log/clean');   //清理日志
}


//每分钟执行(放在最下面)
if (ifRun('* * * * *')) {

    //每分钟执行一次,并且只保证一个进程
    //runScriptOnce('index.php salt/task/listen');
}

// ##########################  function  ####################
function ifRun($cron)
{
    $min      = date('i'); //分
    $hour     = date('H'); //时
    $day      = date('d'); //日
    $mon      = date('m'); //月
    $week     = date('w'); //周
    $cron_arr = explode(" ", $cron);
    if (count($cron_arr) != 5) {
        echo "Error: $cron\n";
        return false;
    }
    list($a, $b, $c, $d, $e) = $cron_arr;
    $res_a = parseCron($a, $min);
    $res_b = parseCron($b, $hour);
    $res_c = parseCron($c, $day);
    $res_d = parseCron($d, $mon);
    $res_e = parseCron($e, $week);
    if ($res_e && $res_d && $res_c && $res_a && $res_b)
        return true;
}

function parseCron($a, $min)
{
    if ($a == '*' || $a == '*/1') {
        return true;
    } else if (preg_match('/\//', $a)) {
        list($xing, $runm) = explode("/", $a);
        if (0 == ($min % $runm))
            return true;
    }
    else if (preg_match('/^\d+$/', $a)) {
        if ($a == $min)
            return true;
    }
    else if (preg_match("/,/", $a)) {
        $a_arr = explode(",", $a);
        foreach ($a_arr as $stime) {
            if ($stime == $min)
                return true;
        }
    }
    else if (preg_match('/-/', $a)) {
        list($start, $end) = explode('-', $a);
        if (($min >= $start) && ($min <= $end))
            return true;
    }
    else {
        echo "unknow cron $a\n";
        return false;
    }
}

//执行任务
function runScript($file, $bg = true)
{
    $cmd = getCmd($file, $bg);
    if ($cmd === false) {
        return false;
    }
    pclose(popen($cmd, 'r'));
    echo date('Y-m-d H:i:s') . " " . $cmd . "\n";
}

//增加lockf机制，保证只有一个任务进程在执行
function runScriptOnce($file, $bg = true)
{
    $cmd = getCmd($file, $bg);
    if ($cmd === false) {
        return false;
    }
    $lock = md5($file) . '.lock';
    $cmd  = '/usr/bin/lockf -t 0 ' . $lock . ' ' . $cmd;
    pclose(popen($cmd, 'r'));
    echo date('Y-m-d H:i:s') . " " . $cmd . "\n";
}

//获取执行的命令
function getCmd($file, $bg = true)
{
    $runer   = array(
        'sh'  => '/bin/sh',
        'php' => '/usr/local/bin/php',
        'pl'  => '/usr/bin/perl',
        'py'  => '/usr/bin/python',
    );
    $runFile = explode(' ', $file)[0];
    if (!file_exists($runFile)) {
        echo date('Y-m-d H:i:s') . " " . $runFile . " file not found\n";
        return false;
    }
    $exer = $runer[end(explode('.', $runFile))];
    if (empty($exer)) {
        echo date('Y-m-d H:i:s') . " " . $file . " exer not found\n";
        return false;
    }
    $cmd = $exer . ' ' . $file . ($bg ? ' &' : ''); ///usr/local/bin/php index.php home/hello &
    return $cmd;
}
?>

