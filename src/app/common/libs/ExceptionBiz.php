<?php

namespace common\libs;

/**
 * Description of ExceptionBiz
 * 业务异常类
 * @author JIU
 */
class ExceptionBiz extends \Exception {

    private $_ext_info;

    public function __construct($err, $ext_info = array()) {
        $this->_ext_info = $ext_info;
        $arr = explode('=>', $err);
        $msg = strtr($arr[1],$ext_info);
        parent::__construct($msg, $arr[0]);
    }

    public function getExtInfo() {
        return $this->_ext_info;
    }

}