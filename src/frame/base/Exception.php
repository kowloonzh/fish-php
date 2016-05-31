<?php

namespace frame\base;

/**
 * Description of Exception
 * Frame异常类
 * @author KowloonZh
 */
class Exception extends \Exception
{
    public function __construct($message = '', $code = 500, $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
