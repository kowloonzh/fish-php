<?php

namespace common\libs;

/**
 * Description of Errors
 * 错误说明类
 * @author JIU
 */
class Errors
{

    /**
     * =========== 公共错误常量 =============
     */
    const ERR_NONE           = 0; //没有错误
    const ERR_SEP            = '=>';
    const ERR_PARAM          = '1=>参数错误';
    const ERR_UNKNOWN        = '2=>未知错误';
    const ERR_SIGN           = '3=>签名认证失败';
    const ERR_SYSTEM         = '4=>系统异常';
    const ERR_OPERATE        = '5=>操作失败';
    

    /**
     * 返回错误码
     * @param string $err
     * @return int
     */
    public static function getErrCode($err)
    {
        return explode(self::ERR_SEP, $err)[0];
    }

    /**
     * 返回错误信息
     * @param string $err
     * @return string
     */
    public static function getErrMsg($err)
    {
        return explode(self::ERR_SEP, $err)[1];
    }

}
