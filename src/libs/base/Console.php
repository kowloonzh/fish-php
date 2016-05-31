<?php

namespace libs\base;

/**
 * Description of Console
 * 业务Console控制器基类
 * @author KowloonZh
 */
class Console extends \frame\console\Console
{
    //如果有--sleep=1的参数 则先sleep再执行
    protected function beforeAction()
    {
        $sleep = $this->request('sleep');
        if($sleep>=1){
            sleep($sleep);
        }
        return parent::beforeAction();
    }
}
