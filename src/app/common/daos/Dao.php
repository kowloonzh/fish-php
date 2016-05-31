<?php

namespace common\daos;

use common\libs\Errors;
use common\libs\ExceptionBiz;

/**
 * Description of Dao
 *
 * @author JIU
 */
abstract class Dao extends \libs\base\Dao
{

    const ENABLED = 1; //可用
    const DELETED = 0; //已删除

    /**
     * 缓存的记录
     * @var array 
     */

    protected static $_rows = [];

    /**
     * 从缓存中取一行记录
     * @param int|array $param
     * @return array
     * @throws \common\libs\ExceptionBiz
     */
    static public function queryRowByCache($param)
    {
        $table = static::tableName();
        $key = md5(json_encode($param));
        if (!isset(self::$_rows[$table][$key])) {
            $row = static::queryRow($param);
            if (empty($row)) {
                throw new ExceptionBiz(Errors::ERR_DATA_NOT_EXIST, ['param' => $param, 'table' => $table]);
            } else {
                self::$_rows[$table][$key] = $row;
            }
        }
        return self::$_rows[$table][$key];
    }

    /**
     * 执行逻辑删除
     * @param mixed $condition 条件
     * @param array $params 绑定的参数
     * @param string $column 标记删除字段的名称 默认为enabled
     * @return int 影响的行数
     */
    static public function logicDelete($condition = '', $params = [], $column = 'enabled')
    {
        return static::find()->update(static::tableName(), [$column => static::DELETED], $condition, $params);
    }

    /**
     * 只取enabled=1的一行记录
     * @param int|array $param
     * @param string|array $select
     * @return array
     */
    static public function queryEnabledRow($param, $select = '*')
    {
        if (!is_array($param)) {
            $pk = $param;
            $param = [];
            $param[static::primaryKey()] = $pk;
        }
        $param['enabled'] = self::ENABLED;
        return static::queryRow($param, $select);
    }

    /**
     * 只取enabled=1的多行记录
     * @param array $param
     * @param string $select
     * @return array
     */
    static public function queryEnabledAll(array $param = [], $select = '*')
    {
        $param['enabled'] = self::ENABLED;
        return static::queryAll($param, $select);
    }

    /**
     * 只取enabled=1的一列记录
     * @param string|array $select
     * @param array $param
     * @return type
     */
    static public function queryEnabledColumn($select, array $param = [])
    {
        $param['enabled'] = self::ENABLED;
        return static::queryColumn($select, $param);
    }

    /**
     * 只取enabled=1的某行的某个字段的值
     * @param string $select
     * @param array $param
     * @return string|boolean
     */
    static public function queryEnabledOne($select, $param = [])
    {
        if (!is_array($param)) {
            $pk = $param;
            $param = [];

            $param[static::primaryKey()] = $pk;
        }
        $param['enabled'] = self::ENABLED;
        return static::queryOne($select, $param);
    }

    /**
     * 将某个字段在原值上增加
     * @param array|string $condition 条件
     * @param string $column 字段
     * @param int $step 增加的值
     * @return int
     */
    static public function incr($condition, $column, $step = 1)
    {
        $express = "{$column} + {$step}";
        return static::update([$column => new \libs\db\DbExpression($express)], $condition);
    }

    /**
     * 将某个字段在原值上减少
     * @param array|string $condition 条件
     * @param string $column 字段
     * @param int $step 减少的值
     * @return int
     */
    static public function decr($condition, $column, $step = 1)
    {
        $express = "{$column} - {$step}";
        return static::update([$column => new \libs\db\DbExpression($express)], $condition);
    }

}
