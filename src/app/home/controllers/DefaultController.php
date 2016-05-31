<?php

namespace home\controllers;

/**
 * Description of DefaultController
 *
 * @author KowloonZh
 */
class DefaultController extends \libs\base\Controller
{
    
    public function indexAction()
    {
//        p(\Load::$app->modules);
//        p(\Load::$aliases);
//        \libs\log\Loger::info('just a test', 'email.test');
        //application/json;charset=UTF-8
//        return $this->_msg;
//        throw new \common\libs\ExceptionBiz(\common\libs\Errors::ERR_OPERATE);
        return 'hello,world';
//        return $this->_msg;
    }
    
    public function curlAction($url='')
    {
//        p(\Load::$app->getRequest()->getHostInfo());
        $result = \third\BaseThird::ins()->get(\Load::$app->getRequest()->getHostInfo());
        p($result);
        $curl = new \libs\utils\Curl();
        $response = $curl->get(\Load::$app->getRequest()->getHostInfo());
        p($curl);
    }
}
