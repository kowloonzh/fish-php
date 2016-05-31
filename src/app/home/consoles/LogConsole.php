<?php

namespace home\consoles;

/**
 * Description of LogConsole
 * 日志脚本
 * @author JIU
 */
class LogConsole extends \common\consoles\Console
{

    public $today;
    public $delete_day;

    public function cleanAction($keep = 7)
    {
        if(\Load::$app->isDev() && $keep==7){
            $keep = 1;
        }
        //只留今天的日期，其他日期放到achive目录下，achive目录中的文件保留7天
        $this->today = date('Ymd');
        $this->delete_day = date('Ymd', strtotime('-' . $keep . ' day'));
        $log_path = \Load::getAlias('@root/logs');
        if (!is_dir($log_path)) {
            return $log_path . ' is not a dir';
        }
        $this->doClean($log_path);
        return 'done!';
    }

    protected function doClean($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $filename) {
            $file = $dir . '/' . $filename;
            if ($filename == 'archive') {
                $this->dealArchive($file);
            } elseif (is_file($file)) {
                $this->dealFile($file);
            } else {
                $this->doClean($file);
            }
        }
    }

    //处理文件
    protected function dealFile($file)
    {
        $basename = basename($file);
        if (!preg_match('/' . $this->today . '/', $basename)) {
            //移动到achive目录
            $archive = dirname($file) . '/archive';
            if (!is_dir($archive)) {
                @mkdir($archive, 0777, true);
            }
            @rename($file, $archive . '/' . $basename);
        }
    }

    //处理archive目录
    protected function dealArchive($archive)
    {
        //获取archive目录下所有的文件
        $files = array_diff(scandir($archive), ['.', '..']);
        foreach ($files as $filename) {
            //将7天以前的文件删除
            if (preg_match('/' . $this->delete_day . '/', $filename)) {
                @unlink($archive . '/' . $filename);
            }
        }
    }

}