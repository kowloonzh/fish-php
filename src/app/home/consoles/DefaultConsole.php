<?php

namespace home\consoles;
use common\consoles\Console;

/**
 * Description of DefaultConsole
 *
 * @author zhangjiulong
 */
class DefaultConsole extends Console
{
    
    public function testAction()
    {
        // 常驻进程
        //$this->runLoop(function(){
        //    echo 'hello';
        //});


    }
    
    //生成模块目录
    public function genAction($module)
    {
        $modulePath = \Load::getAlias('@app/'.$module);
        if(!is_dir($modulePath)){
            //生成controllers，consoles,models,daos目录
            foreach (['controllers','consoles','models','daos'] as $dir) {
                mkdir($modulePath.'/'.$dir,0755,true);
            }
        }
        return 'done!';
    }
}
