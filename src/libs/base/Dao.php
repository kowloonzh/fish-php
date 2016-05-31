<?php

namespace libs\base;

/**
 * Description of Dao
 * 数据库Dao基类
 * 
 * for example
 * ~~~~~~~
 * 有一个user表
 * CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `age` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * 
 * 创建一个UserDao类
 * class UserDao extends FrameDao{
  public static function tableName() {
  return 'user';
  }
  }
 * 
 * 单条语句插入
 * $res = UserDao::insert(['name'=>'zhangsan']);
 * return : 自增id
 * 
 * 多条语句插入：
 * $res = UserDao::batchInsert(array('id','name'),array(
 *      array('16','lisi'),
 *      array('17','wangwu'),
 * ));
 * return: 影响的行数
 * 
 * 修改：
 * $res = UserDao::update(array(
 *      'name'=>'liuliu'
 * ),['id'=>17]);
 * return: 受影响的行数
 * 
 * 删除：
 * $res = UserDao::delete(['id'=>17]);
 * 
 * 查询一行记录
 * $res = UserDao::queryRow(1); //根据主键id查询
 * $res = UserDao::queryRow(['name'=>'KowloonZh']);  //根据条件查询
 * return: 一维关联数组
 * ['id'=>1,'name'=>'zs','age'=>20]
 * 
 * 查询多行记录
 * $res = UserDao::queryAll(['id'=>[1,2,3]]);   //查询id in (1,2,3)
 * $res = UserDao::queryAll(['name'=>'KowloonZh','age'=>4]); //查询年龄4岁，名字为KowloonZh的用户
 * 
 * 复杂的查询请获取Query之后用query对象操作
 * $query = UserDao::find();
 * 
 * ~~~~~~
 * @author KowloonZh
 */
abstract class Dao extends \frame\base\Object
{
    /**
     * 返回的是\libs\db\Query的对象实例
     * @param string $sql
     * @return \libs\db\Query
     */
    static public function find($sql = null)
    {
        return (new \libs\db\Query(['sql' => $sql, 'db' => static::getDb()]))->from(static::tableName() . ' as t');
    }

    /**
     * 执行一条插入语句
     * @param array $columns
     * @param boolean $return_id 是否要返回自增id，默认为true，false则返回影响的行数 
     * @return int 受影响的行数
     */
    static public function insert($columns, $return_id = true)
    {
        $query = static::find();
        $num = $query->insert(static::tableName(), $columns);
        return $return_id ? $query->getLastInsertId() : $num;
    }

    /**
     * 执行多条语句的插入
     * @param array $columns
     * @param array $rows
     * @return int
     */
    static public function batchInsert($columns, $rows)
    {
        return static::find()->batchInsert(static::tableName(), $columns, $rows);
    }

    /**
     * 执行数据更新
     * @param array $columns
     * @param string|array $condition
     * @param array $params
     * @return int
     */
    static public function update($columns, $condition = '', $params = [])
    {
        return static::find()->update(static::tableName(), $columns, $condition, $params);
    }

    /**
     * 执行数据删除
     * @param string|array $condition
     * @param array $params
     * @return int
     */
    static public function delete($condition = '', $params = [])
    {
        return static::find()->delete(static::tableName(), $condition, $params);
    }

    /**
     * 获取一行信息
     * @param int|array $param
     * @param array|string $select 要取出来的字段
     * @return array
     */
    static public function queryRow($param, $select = '*')
    {
        $query = static::find()->select($select);
        if(is_array($param)){
            $query->andWhere($param);
        }else{
            //根据主键查询 [只支持单主键查询]
            $query->andWhere(static::primaryKey() . '=:id', [':id' => $param]);
        }
        return $query->queryRow();
    }

    /**
     * 返回多行记录
     * @param array $param
     * @param array|string $select 要取出来的字段
     * @return array
     */
    static public function queryAll(array $param = [], $select = '*')
    {
        $query = static::find()->select($select)->where($param);
        return $query->queryAll();
    }

    /**
     * 取一列数据
     * @param string $select 要取的字段
     * @param array $param 条件
     * @return array
     */
    static public function queryColumn($select, $param = [])
    {
        $query = static::find()->select($select)->where($param);
        return $query->queryColumn();
    }
    
    /**
     * 取第一行第一列的值
     * @param mixed $select
     * @param array $param
     * @return string
     */
    static public function queryOne($select,$param=[]) 
    {
        $query = static::find()->select($select)->where($param);
        return $query->queryOne();
    }

    /**
     * 返回当前Dao的表名
     */
    abstract static public function tableName();

    /**
     * 返回当前Dao的主键名
     * @return string
     */
    static public function primaryKey()
    {
        return 'id';
    }
    
    /**
     * 返回对应的数据库链接
     * @return \libs\db\DB
     */
    static public function getDb()
    {
        return \libs\db\DB::di('db');
    }
}
