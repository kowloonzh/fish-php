<?php

namespace common\consoles;

/**
 * Description of Console
 * 业务命令行控制器基类
 * @author JIU
 */
class Console extends \libs\base\Console
{
    //优雅的杀死某个死循环进程,配合tools/reload_scripts.sh使用
    public function gracefulKill()
    {
        $route    = $this->id . '/' . $this->actionId;
        $filename = str_replace('/', '.', $route);
        $path     = \Load::getAlias('@root/src/task/.signal.' . $filename);
        //如果文件没有则创建并写入
        if (!file_exists($path)) {
            file_put_contents($path, '');
            chmod($path, 0777);
            return false;
        }
        //判断是否有更新,有更新则清空
        $content = file_get_contents($path);
        if ($content != '') {
            file_put_contents($path, '');
            return true;
        }
        return false;
    }
}
