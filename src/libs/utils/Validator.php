<?php

namespace libs\utils;

/**
 * Description of Validator
 * 验证静态类
 * @author JIU
 */
class Validator
{

    /**
     * 验证失败时的错误信息
     * @var array 
     */
    static public $messages = [
        'required' => '不能为空',
        'number'   => '不是一个数值或者数值的大小不匹配',
        'string'   => '不是一个字符串或者字串长度不匹配',
        'arr'      => '不是一个数组或者数组里面值的个数不匹配',
        'in'       => '不在指定的选值范围中',
        'ip'       => '不是IP',
        'match'    => '不合法',
        'custom'   => '验证失败',
        'json'     => '不是一个有效的Json串'
    ];

    /**
     * 非空验证 
     * @param mixed $value
     * @param boolean $trim
     * @return boolean 如果为空 返回false 不为空 返回true
     */
    static public function required($value, $trim = true)
    {
        return !static::isEmpty($value, $trim);
    }

    /**
     * 数值验证(验证类型和大小)
     * @param integer $value
     * @param integer $min 验证最小值
     * @param integer $max 验证最大值
     * @param boolean $allowEmpty 是否可以为空 可以为空则跳过验证
     * @param boolean $float  是否可以为浮点数，默认是不可以
     * @return boolean
     */
    static public function number($value, $allowEmpty = true, $float = false, $min = null, $max = null)
    {
        //如果可以为空 并且为空 返回true
        if ($allowEmpty && static::isEmpty($value)) {
            return true;
        }
        //如果不是数值，返回false
        if (!is_numeric($value)) {
            return false;
        }
        //如果不可以为浮点数
        if ($float === false) {
            if (strpos($value, '.') !== false) {
                return false;
            }
        }
        //如果小于设置的最小值 返回false
        if ($min !== null && $value < $min) {
            return false;
        }
        //如果大于设置的最大值 返回false
        if ($max !== null && $value > $max) {
            return false;
        }
        return true;
    }

    /**
     * 字串验证（验证类型和长度）
     * @param string $value
     * @param boolean $allowEmpty
     * @param int $min
     * @param int $max
     * @param boolean $encode 是否按UTF-8编码来算计算长度，true则一个中文字符算一个长度
     * @return boolean
     */
    static public function string($value, $allowEmpty = true, $min = null, $max = null, $encode = true)
    {
        //如果可以为空 并且为空 返回true
        if ($allowEmpty && static::isEmpty($value)) {
            return true;
        }
        //验证是否为字串
        if (gettype($value) !== 'string') {
            return false;
        }
        $len = $encode ? mb_strlen($value, 'UTF-8') : strlen($value);
        //字串长度小于给出的长度
        if ($min !== null && $len < $min) {
            return false;
        }
        //字串长度大于给出的长度
        if ($max !== null && $len > $max) {
            return false;
        }
        return true;
    }

    /**
     * 验证数组（类型和count个数）
     * @param array $value
     * @param boolean $allowEmpty 是否可以为空
     * @param int $min 
     * @param int $max
     * @return boolean
     */
    static public function arr($value, $allowEmpty = true, $min = null, $max = null, $json = false)
    {
        //如果是json串，则先转成数组
        if ($json) {
            $value = json_decode($value, true);
        }

        //如果可以为空 并且为空 返回true
        if (static::isEmpty($value)) {
            return $allowEmpty;
        }

        //如果不是数组 返回false
        if (!is_array($value)) {
            return false;
        }
        $count = count($value);
        //如果数组个数太小 返回false
        if ($min !== null && $count < $min) {
            return false;
        }
        //如果数组个数太大 返回false
        if ($max !== null && $count > $max) {
            return false;
        }
        return true;
    }

    /**
     * 验证参数是否为json串
     * @param string $value
     * @param type $allowEmpty
     * @return boolean
     */
    static public function json($value, $allowEmpty = true)
    {
        //如果可以为空 并且为空 返回true
        if ($allowEmpty && static::isEmpty($value)) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        $arr = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if (!$allowEmpty && static::isEmpty($arr)) {
            return false;
        }

        return true;
    }

    /**
     * 范围检查（检测value是否在range中）
     * @param int|string $value 待检查的值
     * @param array $range 值的范围，数组类型
     * @param type $allowEmpty 是否可以为空
     * @param boolean $not 反向验证，如果设置为true,相当于not in
     * @param boolean $strict 是否采用严格模式的in_array
     * @return boolean
     */
    static public function in($value, array $range, $allowEmpty = true, $not = false, $strict = false)
    {
        //如果可以为空 并且为空 返回true
        if ($allowEmpty && static::isEmpty($value)) {
            return true;
        }

        $result = in_array($value, $range, $strict);

        if ($not) {
            return !$result;
        }
        return $result;
    }

    /**
     * 正则匹配验证
     * @param mixed $value 待验证的值，不能为数组
     * @param string $pattern 正则表达式，如 '/\w{9}/'
     * @param boolean $allowEmpty 是否可以为空
     * @param boolean $not 反向验证
     * @return boolean
     */
    static public function match($value, $pattern, $allowEmpty = true, $not = false)
    {
        //如果可以为空 并且为空 返回true
        if ($allowEmpty && static::isEmpty($value)) {
            return true;
        }

        if (is_array($value) ||
                (!$not && !preg_match($pattern, $value)) ||
                ($not && preg_match($pattern, $value))) {
            return false;
        }
        return true;
    }

    /**
     * 验证ip
     * @param string|array $value 如果为数组，则需要数组的每个值都能匹配ip 才返回true
     * @param boolean $allowEmpty
     * @param boolean $not 设置true表示 必须全部不是ip，有一个ip则返回false
     * @return boolean
     */
    static public function ip($value, $allowEmpty = true, $not = false)
    {
        //如果可以为空 并且为空 返回true
        if ($allowEmpty && static::isEmpty($value)) {
            return true;
        }

        $pattern = "/^((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))$/";
        if (is_array($value)) {
            foreach ($value as $v) {
                $res = static::match($v, $pattern, false, $not);
                if ($res == false) {
                    return false;
                }
            }
            return true;
        } else {
            return static::match($value, $pattern, false, $not);
        }
    }

    /**
     * 自定义验证器
     * @param mixed $value 待验证的值
     * @param callback $callback 有效的php回调
     * @param array $params 辅助参数
     * @return boolean
     * @throws ExceptionFrame
     */
    static public function custom($value, $callback, $params = [])
    {
        if (!is_callable($callback)) {
            throw new ExceptionFrame('the callback function is not callable');
        }
//        $param_arr = ['value' => $value, 'params' => $params];
        $param_arr = [$value];
        if (!empty($params)) {
            foreach ($params as $val) {
                $param_arr[] = $val;
            }
        }
        $res = call_user_func_array($callback, $param_arr);
        return $res !== false;
    }

    /**
     * 返回$value的值是否为空 null [] '' 为空，0不为空
     * @param mixed $value
     * @param boolean $trim
     * @return boolean
     */
    static public function isEmpty($value, $trim = true)
    {
        return $value === null || $value === [] || $value === '' || $trim && is_scalar($value) && trim($value) === '';
    }

}
