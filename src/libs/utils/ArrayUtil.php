<?php

namespace libs\utils;

/**
 * Description of ArrayUtil
 * 数组帮助类
 * @author KowloonZh
 */
class ArrayUtil
{

    /**
     * 用空格或者逗号或者换行将字串分割成数组
     * 使用：ArrayUtil::string2array($str)
     * 案例 $str = 'zhangsan KowloonZh';
     *      $str = 'zhangsan ,KowloonZh';
     *      $str = "zhangsan \n,KowloonZh";
     * 返回 array('zhangsan','KowloonZh')
     * @param string $str
     * @return array
     */
    public static function string2array($str)
    {/* {{{ */
        if (is_array($str)) {
            return $str;
        }
        return preg_split('/\s*[,\s]\s*/', trim($str), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * 把字符串拆成键值对数组
     *
     * @param string $string 
     * @param string $kv_sep  key=value (=)
     * @param string $pair_sep  k1=v1|k2=v2 (|)
     * @return array
     */
    public static function str2kv($string, $kv_sep = '=', $pair_sep = '|')
    {
        if (is_array($string))
            return $string;
        $ret_array = array();
        $pairs     = explode($pair_sep, $string);
        foreach ($pairs as $pair) {
            $kv = explode($kv_sep, $pair);
            if (count($kv) == 2) {
                $ret_array[$kv[0]] = $kv[1];
            }
        }
        return $ret_array;
    }

    /**
     * 从数组或者对象中获取某个键/属性对应的值
     * 如果没有找到，返回默认值default
     *
     * 使用例子,
     * ~~~
     * // working with array
     * $username = ArrayUtil::getValue($_POST, 'username');
     * // working with object
     * $username = ArrayUtil::getValue($model, 'username');
     *
     * @param array|object $array 数组或者对象
     * @param string $key 数组的键名或者对象的属性名
     * @param mixed $default 默认返回值
     * @return mixed
     */
    public static function getValue($array, $key, $default = null)
    {
        if (is_array($array)) {
            return isset($array[$key]) || array_key_exists($key, $array) ? $array[$key] : $default;
        } else {
            return $array->$key;
        }
    }

    /**
     * 使用特殊的键重新索引多维数组
     * 键名可以使数组的键名或者对象的属性名
     *
     * For example,
     *
     * ~~~
     * $array = array(
     *     array('id' => '123', 'data' => 'abc'),
     *     array('id' => '345', 'data' => 'def'),
     * );
     * $result = ArrayUtil::index($array, 'id');
     * // the result is:
     * // array(
     * //     '123' => array('id' => '123', 'data' => 'abc'),
     * //     '345' => array('id' => '345', 'data' => 'def'),
     * // )
     *
     * ~~~
     *
     * @param array $array 需要被索引的多维数组
     * @param string $key 数组的键名或者对象的属性名
     * @return array the indexed array
     */
    public static function index($array, $key)
    {
        $result = array();
        foreach ($array as $element) {
            $value          = self::getValue($element, $key);
            $result[$value] = $element;
        }
        return $result;
    }

    /**
     * 将多维数组按某一个键名分组
     * ~~~~~
     * for example：
     * ~~~~~~
     *  $array = array(
     *     array('id' => '123', 'name' => 'aaa', 'group' => 'x'),
     *     array('id' => '124', 'name' => 'bbb', 'group' => 'x'),
     *     array('id' => '345', 'name' => 'ccc', 'group' => 'y'),
     * );
     * $result = ArrayUtil::group($array,'group');
     * the return is :
     * array(
     *  'x'=>array(
     *      array('id' => '123', 'name' => 'aaa', 'group' => 'x'),
     *      array('id' => '124', 'name' => 'bbb', 'group' => 'x'),
     *  ),
     *  'y'=>array(
     *      array('id' => '345', 'name' => 'ccc', 'group' => 'y'),
     *      )
     * );
     * @param array $array 要被分组的数组
     * @param string $key   用来分组的键名
     * @return array
     */
    static public function group($array, $key)
    {
        $result = array();
        if (!empty($array)) {
            foreach ($array as $element) {
                $value            = self::getValue($element, $key);
                $result[$value][] = $element;
            }
        }
        return $result;
    }

    /**
     * 返回二维数组中某个键对应的所有值的集合
     *
     * For example,
     *
     * ~~~
     * $array = array(
     *     array('id' => '123', 'data' => 'abc','name'=>'KowloonZh'),
     *     array('id' => '345', 'data' => 'def','name'=>'zjl'),
     * );
     * $result = ArrayUtil::getColumn($array, 'id');
     * // the result is: array( '123', '345')
     *
     * $result = ArrayUtil::getColumn($array, ['id','name']);
     * // the result is: [{'id'=>123,'name'=>'zhangjiulng'},{'id'=>'345','name'=>'zjl'}]
     * ~~~
     *
     * @param array $array
     * @param string|array $name
     * @param boolean $keepKeys 是否需要保持原二维数组的索引关系
     * will be re-indexed with integers.
     * @return array the list of column values
     */
    public static function getColumn($array, $name, $keepKeys = false)
    {
        $result = array();
        if (empty($array)) {
            return $result;
        }
        if ($keepKeys) {
            foreach ($array as $k => $element) {
                if (is_array($name)) {
                    foreach ($name as $val) {
                        $res[$val] = self::getValue($element, $val);
                    }
                    $result[$k] = $res;
                } else {
                    $result[$k] = self::getValue($element, $name);
                }
            }
        } else {
            foreach ($array as $element) {
                if (is_array($name)) {
                    foreach ($name as $val) {
                        $res[$val] = self::getValue($element, $val);
                    }
                    $result[] = $res;
                } else {
                    $result[] = self::getValue($element, $name);
                }
            }
        }

        return $result;
    }

    /**
     * 从多维数组中删除某个|多个键的值
     * 
     * For example,
     * 
     * $array = [
     *  ['id'=>3,'name'=>'zjl','age'=>30],
     *  ['id'=>4,'name'=>'zjl','age'=>30],
     *  ['id'=>5,'name'=>'zjl','age'=>30],
     * ];
     * 
     * $result = ArrayUtil::unsetColumn($array, 'name');
     * return 
     * $array = [
     *  ['id'=>3,'age'=>30],['id'=>4,'age'=>30],['id'=>5,'age'=>30],
     * ];
     * 
     * $result = ArrayUtil::unsetColumn($array, ['name','age']);
     * return 
     * $array = [
     *  ['id'=>3],['id'=>4],['id'=>5],
     * ];
     * 
     * @param type $array
     * @param type $name
     * @return type
     */
    static public function unsetColumn($array, $name)
    {
        if (empty($array) || empty($name)) {
            return $array;
        }
        foreach ($array as $k => $element) {
            if (is_array($name)) {
                foreach ($name as $val) {
                    unset($element[$val]);
                    $array[$k] = $element;
                }
            } else {
                unset($element[$name]);
                $array[$k] = $element;
            }
        }

        return $array;
    }

    /**
     * 从多维数组中取某个键的值作为新数组的键，某个键的值做为新数组的值，形成一个键值对数组
     * 也可以选择是否需要分组
     *
     * For example,
     *
     * ~~~
     * $array = array(
     *     array('id' => '123', 'name' => 'aaa', 'class' => 'x'),
     *     array('id' => '124', 'name' => 'bbb', 'class' => 'x'),
     *     array('id' => '345', 'name' => 'ccc', 'class' => 'y'),
     * );
     *
     * $result = ArrayUtil::map($array, 'id', 'name');
     * // the result is:
     * // array(
     * //     '123' => 'aaa',
     * //     '124' => 'bbb',
     * //     '345' => 'ccc',
     * // )
     *
     * $result = ArrayUtil::map($array, 'id', 'name', 'class');
     * // the result is:
     * // array(
     * //     'x' => array(
     * //         '123' => 'aaa',
     * //         '124' => 'bbb',
     * //     ),
     * //     'y' => array(
     * //         '345' => 'ccc',
     * //     ),
     * // )
     * ~~~
     *
     * @param array $array
     * @param string$from
     * @param string $to
     * @param string $group
     * @return array 返回键值对数组
     */
    public static function map($array, $from, $to, $group = null)
    {
        $result = array();
        if (empty($array)) {
            return $result;
        }
        foreach ($array as $element) {
            $key   = self::getValue($element, $from);
            $value = self::getValue($element, $to);
            if ($group !== null) {
                $result[self::getValue($element, $group)][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * 判断数组是否为关联数组
     * $arr = array(); | true
     * $arr = array(2,3,4,5); | false
     * $arr = array(2,'a'=>'b',3,'d'); |false
     * $arr = array('0'=>'a','1'=>'b','a'=>'c'); |false
     * $arr = array('a'=>'c','b'=>'c'); |true
     * $arr = array('a'=>'c','b'=>'c','d'=>array(1,2)); |true
     * @param array $arr
     * @return boolean
     */
    public static function is_assoc($arr)
    {
        return (is_array($arr) && (!count($arr) || count(array_filter(array_keys($arr), 'is_string')) == count($arr)));
    }

    /**
     * 对二维数组的多个字段进行自然排序
     * for example ~~~
     * $rpms = array(
      array('id'=>'81623','name'=>'addops-nginx','version'=>'1.2.9','release'=>'9.el6'),
      array('id'=>'81989','name'=>'addops-nginx','version'=>'1.2.2','release'=>'5.el5'),
      array('id'=>'81691','name'=>'afdops-nginx','version'=>'1.2.10','release'=>'7.el6'),
      array('id'=>'81781','name'=>'addops-nginx','version'=>'1.2.9','release'=>'4.el6'),
      array('id'=>'81566','name'=>'addops-nginx','version'=>'1.2.9','release'=>'3.el6'),
      array('id'=>'81582','name'=>'addops-nginx','version'=>'1.2.9','release'=>'10.el6'),
      );
     * use: ArrayUtil::mNatSort($rpms,array('version'=>'desc','release'=>'desc'));
     * result:
     * $rpms = array(
      array('id'=>'81691','name'=>'afdops-nginx','version'=>'1.2.10','release'=>'7.el6'),
      array('id'=>'81582','name'=>'addops-nginx','version'=>'1.2.9','release'=>'10.el6'),
      array('id'=>'81623','name'=>'addops-nginx','version'=>'1.2.9','release'=>'9.el6'),
      array('id'=>'81781','name'=>'addops-nginx','version'=>'1.2.9','release'=>'4.el6'),
      array('id'=>'81566','name'=>'addops-nginx','version'=>'1.2.9','release'=>'3.el6'),
      array('id'=>'81989','name'=>'addops-nginx','version'=>'1.2.2','release'=>'5.el5'),
      );
     * ~~~~
     * @param array $array
     * @param array $sortRules 由字段名和排序方向组成的数组,如['version'=>'desc','release'=>'desc'],表示将$array先按version排序再按release排序
     * @return type
     */
    public static function mNatSort($array, $sortRules = array())
    {
        if (empty($array))
            return $array;
        $bakRules = $sortRules;
        if (!empty($sortRules)) {
            list($key, $order) = each($sortRules);
            unset($bakRules[$key]);
            $array  = self::natSort($array, $key, $order);
            $gArray = self::group($array, $key);
            if (list($key, $order) = each($sortRules)) {
                foreach ($gArray as $k => $arr) {
                    $gArray[$k] = self::mNatSort($arr, $bakRules);
                }
            }
            //重组
            foreach ($gArray as $g => $arr) {
                foreach ($arr as $val) {
                    $newArr[] = $val;
                }
            }
            return $newArr;
        }
        return $array;
    }

    /**
     * 对二维数组的单个字段进行自然排序
     * for example ~~~~ 将$rpms按release倒序排列
     * $rpms = array(
      array('id'=>'81623','name'=>'addops-nginx','version'=>'1.2.9','release'=>'9.el6'),
      array('id'=>'81989','name'=>'addops-nginx','version'=>'1.2.9','release'=>'5.el5'),
      array('id'=>'81691','name'=>'afdops-nginx','version'=>'1.2.10','release'=>'7.el6'),
      array('id'=>'81781','name'=>'addops-nginx','version'=>'1.2.9','release'=>'4.el6'),
      array('id'=>'81566','name'=>'addops-nginx','version'=>'1.2.9','release'=>'3.el6'),
      array('id'=>'81582','name'=>'addops-nginx','version'=>'1.2.2','release'=>'10.el6'),
      );
     * use 
     * ArrayUtil::natSort($rpms,'release','desc');
     * result:
     * $rpms = array(
      array('id'=>'81582','name'=>'addops-nginx','version'=>'1.2.2','release'=>'10.el6'),
      array('id'=>'81623','name'=>'addops-nginx','version'=>'1.2.9','release'=>'9.el6'),
      array('id'=>'81691','name'=>'afdops-nginx','version'=>'1.2.10','release'=>'7.el6'),
      array('id'=>'81989','name'=>'addops-nginx','version'=>'1.2.9','release'=>'5.el5'),
      array('id'=>'81781','name'=>'addops-nginx','version'=>'1.2.9','release'=>'4.el6'),
      array('id'=>'81566','name'=>'addops-nginx','version'=>'1.2.9','release'=>'3.el6'),
      );
     * ~~~~~~  
     * @param array $array
     * @param string $key
     * @param string $order 排序方向 desc|asc
     * @return array
     */
    public static function natSort($array, $key, $order)
    {
        if (empty($array) || !is_array($array)) {
            return $array;
        }
        $keys = self::getColumn($array, $key, true);
        uasort($keys, 'strnatcmp');
        if (strtolower($order) == 'desc') {
            $keys = array_reverse($keys, true);
        }
        $isAssoc = self::is_assoc($array);
        foreach ($keys as $k => $val) {
            if ($isAssoc) {
                $newArr[$k] = $array[$k];
            } else {
                $newArr[] = $array[$k];
            }
        }
        return $newArr;
    }

    /**
     * 递归的合并两个数组,如果两个数组有相同的元素，后面的会覆盖签名的值
     * @param array $a
     * @param array $b
     * @return array
     */
    public static function merge($a, $b)
    {
        $args = func_get_args();
        $res  = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_integer($k)) {
                    if (isset($res[$k])) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = self::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    /**
     * 将二维数组按一维中的某个键的指定值进行排序
     * @param array $array 待排序的二维数组
     * @param string $key 根据排序的键
     * @param array $values 指定的顺序
     * @return array
     */
    static public function sortByValues($array, $key, array $values)
    {
        if (empty($array)) {
            return $array;
        }
        foreach ($values as $val) {
            $list[$val] = [];
        }
        $other = [];
        foreach ($array as $arr) {
            if (in_array($arr[$key], $values)) {
                $list[$arr[$key]][] = $arr;
            } else {
                $other[] = $arr;
            }
        }
        $res = [];
        foreach ($list as $v) {
            $res = array_merge($res, $v);
        }
        return array_merge($res, $other);
    }
}
