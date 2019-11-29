<?php
/**
 * Created by IntelliJ IDEA.
 * User: KowloonZh
 * Date: 17/6/5
 * Time: 上午11:51
 */

namespace libs\mdb;


use frame\base\God;

/**
 * mongodb的数据访问对象
 *
 * Class MDao
 * @package libs\mdb
 */
abstract class MDao extends God
{

    /**
     * @param array $config
     * @return self
     */
    public static function ins($config = array())
    {
        return parent::ins($config);
    }

    /**
     * 返回集合名
     * @param string $splitKey
     * @return mixed
     */
    abstract protected function _collectionName($splitKey = '');

    /**
     * @param string $splitKey // 分表关键字
     * @param string|null $mdb
     * @return Query
     */
    static public function find($splitKey = '', $mdb = null)
    {
        if ($mdb === null) {
            $mdb = static::getMdb();
        }

        $query = new Query(['mdb' => $mdb]);
        $query->from(static::collectionName($splitKey));
        return $query;
    }

    /**
     * 返回mongo的连接
     * @return \libs\mdb\MDB
     * @throws \frame\base\Exception
     */
    static public function getMdb()
    {
        return \Load::$app->get('mdb');
    }

    static public function collectionName($splitKey = '')
    {
        return static::ins()->_collectionName($splitKey);
    }

    // 返回数据库名称
    public static function databaseName()
    {
        return static::getMdb()->database;
    }

    /**
     * 插入一行记录
     * @param array $document
     * @param string $splitKey
     * @return bool|mixed
     */
    public static function insert(array $document, $splitKey = '')
    {
        return static::find($splitKey)->insert($document);
    }

    /**
     * 修改记录
     * @param array $update
     * @param array $condition
     * @param string $splitKey
     * @return int
     */
    public static function update(array $update, array $condition = [], $splitKey = '')
    {
        return static::find($splitKey)->where($condition)->update($update);
    }

    /**
     * 添加或者修改一行记录
     * @param array $document
     * @param array $condition
     * @param string $splitKey
     * @return int
     */
    public static function upsert(array $document, array $condition, $splitKey = '')
    {
        return static::find($splitKey)->where($condition)->upsert($document);
    }

    /**
     * 删除记录
     * @param array $condition
     * @param string $splitKey
     * @return int
     */
    public static function delete(array $condition, $splitKey = '')
    {
        return static::find($splitKey)->where($condition)->delete();
    }
}
