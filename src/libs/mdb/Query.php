<?php
/**
 * Created by IntelliJ IDEA.
 * User: KowloonZh
 * Date: 17/5/31
 * Time: 上午10:58
 */

namespace libs\mdb;


use frame\base\Exception;
use frame\base\God;
use libs\log\Loger;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;

/**
 * Class Query
 *
 * Usage:
 *
 * 创建一个 mdb 连接和 query 对象
 * $mdb = new \libs\mdb\MDB(['dsn'=>'mongodb://127.0.0.1:27017','username'=>'jiu','password'=>'pass','database'=>'test']);
 * $query = new \libs\mdb\Query(['mdb'=>$mdb]);
 *
 * 向集合 user 中增加一行记录
 * $objectId = $query->from('user')->insert(['username'=>'KowloonZh','age'=>18]);
 *
 * 修改一行记录
 * $num = $query->where(['_id'=>$id])->addOption('multi',false)->update(['age'=>18]);
 *
 * 修改多行记录
 * $num = $query->update(['gender'=>'m']);
 *
 * 修改或者增加一条记录(不存在时)
 * $mixed = $query->where(['username'=>'zjl'])->upsert(['username'=>'zjl','age'=>18,'gender'=>'m','ctime'=>new UTCDatetime(time()*1000)]);
 *
 * 删除记录
 * $num = $query->where(['username'=>'zjl'])->delete();
 *
 *
 * 查询所有集合(采用单个查询条件)  db.user.find({'age':{'$gt':18}});
 * $res = $query->from('user')->where(['age'=>['$gt'=>18]])->queryAll();
 * return: 二维关联数组
 * [
 *      ['_id'=>MongoDB\BSON\ObjectID{oid:"593141c71d3cc23a0366bfc1"},'name'=>'zhangping','age'=>20,'gender'=>'m'],
 *      ['_id'=>MongoDB\BSON\ObjectID{oid:"593141e61d3cc23a55132131"},'name'=>'zengyan','age'=>22,'gender'=>'m'],
 *      ....
 * ]
 *
 * 查询所有集合(只返回某些字段) db.user.find({'age':{$gt:18}},{username:1,age:1,_id:0});
 * $res = $query->from('user')->select('username,age','_id')->where(['age'=>['$gt'=>18]])->queryAll();
 * return: 二维关联数组
 * [
 *      ['name'=>'zhangping','age'=>20],
 *      ['name'=>'zengyan','age'=>22],
 *      ....
 * ]
 *
 * 查询一条记录（采用多个查询条件）db.user.findOne({'age':{$gt:18},username:{$regex:/^zhang/}})
 * $res = $query->select('username,age','_id')
 *              ->from('user')
 *              ->where(['age'=>['$gt'=>18]])
 *              ->andWhere(['username'=>new Regex('^zhang','')])   // just the same sql in mysql:  username like 'zhang%'
 *              ->queryRow();
 * return: 一维关联数组
 * ['name'=>'zhangping','age'=>20]
 *
 *
 * 查询某个字段的集合 db.user.find({'age':{$lt:20}},{username:1,_id:0});
 * $res = $query->select('username')->from('user')->where(['age'=>['$lt'=>20]])->queryColumn();
 * return: 一维索引数组
 * ['zhang','qiu','feng']
 *
 * 查询一个值 db.user.findOne({'age':{$lt:20}},{username:1,_id:0});
 * $res = $query->select('username','_id')->from('user')->queryOne();
 * return: 第一行第一列对应的值
 * 'KowloonZh'
 *
 *
 * 查询 count 值 db.user.findOne({'age':{$lt:20}}}).count();
 * $num = $query->from('user')->where(['age'=>['$lt'=>20]])->count();
 * return:
 * 3
 *
 * 查询 sum 值 db.user.aggregate([{$match:{age:{$gt:18}}},{$group : {_id : 1,sum: { $sum: "$age" }}}]);
 * $res = $query->from('user')->where(['age'=>['$gt'=>18]])->sum('age');
 * return:
 * ["_id"=>1,'sum'=>62]
 *
 * 按gender分组求sum值, 并将结果倒序排列 db.user.aggregate([{$match:{age:{$gt:18}}},{$group : {_id : '$gender',sum: { $sum: "$age" }},{$sort:{sum:-1}}}]);
 * $res = $query->from('user')->where(['age'=>['$gt'=>18]])->sort(['sum'=>-1])->sum('age','gender');
 * return:
 * ["_id"=>'m','sum'=>40]
 * ["_id"=>'f','sum'=>22]
 *
 * 分页 db.user.find().skip(2).limit(2);
 * $res = $query->from('user')->page(2, 2)->queryAll();
 * return: @see queryAll
 *
 *
 * @package libs\mdb
 */
class Query extends God
{

    /**
     * @var MDB
     */
    public $mdb;

    private $_query;

    public function init()
    {
        parent::init();

        if (is_string($this->mdb)) {
            $this->mdb = \Load::$app->get($this->mdb);
        }
    }

    /**
     * eg. select('username,mail','_id')
     *
     * @param string|array $columns // 支持逗号分隔
     * @param string|array $notColumns
     * @return $this
     */
    public function select($columns, $notColumns = [])
    {

        if (is_string($columns)) {

            $columns = preg_split('/\s*[,\s]\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }

        if (is_string($notColumns)) {

            $notColumns = preg_split('/\s*[,\s]\s*/', trim($notColumns), -1, PREG_SPLIT_NO_EMPTY);
        }

        $fields = [];

        if (!empty($columns)) {

            foreach ($columns as $item) {

                $fields[$item] = 1;
            }
        }

        if (!empty($notColumns)) {

            foreach ($notColumns as $item) {

                $fields[$item] = 0;
            }
        }

        // 支持多次select
        if (!isset($this->_query['select'])) {

            $this->_query['select'] = $fields;

        } else {
            $this->_query['select'] = array_merge($this->_query['select'], $fields);
        }


        return $this;
    }

    public function getSelect()
    {
        return $this->_query['select'] ?: [];
    }

    public function from($collection)
    {

        $this->_query['from'] = $collection;
        return $this;
    }

    public function getFrom()
    {
        $collection = $this->_query['from'];

        if (empty($collection)) {
            throw new Exception("Collection must be set");
        }
        return $collection;
    }

    public function getWhere()
    {
        return $this->_query['where'] ?: [];
    }

    /**
     * @param array $condition //@see
     * @return $this|Query
     */
    public function where(array $condition)
    {
        return $this->andWhere($condition);
    }

    public function andWhere(array $condition)
    {
        if (empty($condition)) {
            return $this;
        }

        if (isset($this->_query['where'])) {

            $this->_query['where'] = ['$and' => [$condition, $this->_query['where']]];

        } else {
            $this->_query['where'] = $condition;
        }

        return $this;
    }

    public function orWhere(array $condition)
    {
        if (empty($condition)) {
            return $this;
        }

        if (isset($this->_query['where'])) {

            $this->_query['where'] = ['$or' => [$condition, $this->_query['where']]];

        } else {
            $this->_query['where'] = $condition;
        }

        return $this;
    }


    /**
     * 插入一条文档记录
     * @param array $document
     * @return bool|mixed
     * @throws Exception
     */
    public function insert(array $document)
    {

        if (empty($document)) {
            return false;
        }

        $collection = $this->getFrom();

        $bulkWrite = new BulkWrite();

        $id = $bulkWrite->insert($document);

        $start   = microtime(true);
        $res     = $this->mdb->executeBulkWrite($collection, $bulkWrite);
        $end     = microtime(true);
        $consume = round(($end - $start) * 1000);

        Loger::info(['consume' => $consume, 'action' => 'insert', 'collection' => $collection, 'document' => $document], 'mdb.execute');

        if ($res->getInsertedCount() < 1) {
            return false;
        }
        return $id;
    }

    /**
     * 批量插入文档记录
     * @param array $documents
     * @return bool|mixed
     * @throws Exception
     */
    public function batchInsert(array $documents)
    {

        if (empty($documents)) {
            return false;
        }

        $collection = $this->getFrom();

        $bulkWrite = new BulkWrite();

        $ids = [];
        foreach ($documents as $document) {
            $ids[] = $bulkWrite->insert($document);
        }

        $start   = microtime(true);
        $res     = $this->mdb->executeBulkWrite($collection, $bulkWrite);
        $end     = microtime(true);
        $consume = round(($end - $start) * 1000);

        Loger::info(['consume' => $consume, 'action' => 'batchInsert', 'collection' => $collection, 'documents' => $documents], 'mdb.execute');

        if ($res->getInsertedCount() < 1) {
            return false;
        }
        return $ids;
    }

    /**
     * @see http://php.net/manual/en/mongodb-driver-bulkwrite.update.php
     *
     * @param array $update // @see https://docs.mongodb.com/manual/reference/operator/update/#id1
     * @return int
     * @throws Exception
     */
    public function update(array $update)
    {
        $options = array_merge([
            'multi'  => true,
            'upsert' => false,
        ], $this->getOptions());

        $collection = $this->getFrom();

        $where = $this->getWhere();

        $keys = array_keys($update);

        if (!$options['upsert']) {

            if (!empty($keys) && strncmp('$', $keys[0], 1) !== 0) {
                $update = ['$set' => $update];
            }
        }

        $bulkWrite = new BulkWrite();

        $bulkWrite->update($where, $update, $options);

        $start   = microtime(true);
        $res     = $this->mdb->executeBulkWrite($collection, $bulkWrite);
        $end     = microtime(true);
        $consume = round(($end - $start) * 1000);

        Loger::info(['consume' => $consume, 'action' => 'update', 'query' => $this->_query, 'update' => $update, 'options' => $options], 'mdb.execute');

        if ($res->getUpsertedCount() > 0) {
            return $res->getUpsertedIds()[0];
        }

        return $res->getModifiedCount();
    }

    /**
     * 更新或者插入一条新记录 //@todo 只判断某几个字段是否相同
     * @param array $document
     * @return int
     */
    public function upsert(array $document)
    {

        $this->addOptions([
            'upsert' => true,
            'multi'  => false,
        ]);

        return $this->update($document);
    }

    /**
     * @see http://php.net/manual/en/mongodb-driver-bulkwrite.delete.php
     *
     * @return int
     */
    public function delete()
    {
        $collection = $this->getFrom();
        $where      = $this->getWhere();
        $options    = $this->getOptions();

        $bulkWrite = new BulkWrite();
        $bulkWrite->delete($where, $options);

        $start   = microtime(true);
        $res     = $this->mdb->executeBulkWrite($collection, $bulkWrite);
        $end     = microtime(true);
        $consume = round(($end - $start) * 1000);

        Loger::info(['consume' => $consume, 'action' => 'delete', 'query' => $this->_query, 'mdb.execute']);

        return $res->getDeletedCount();
    }

    public function limit($limit, $skip = null)
    {

        $this->_query['limit'] = intval($limit);
        if ($skip !== null) {
            $this->skip($skip);
        }

        return $this;
    }

    public function getLimit()
    {

        return $this->_query['limit'] ?: -1;
    }

    public function skip($skip)
    {

        $this->_query['skip'] = intval($skip);

        return $this;
    }

    public function getSkip()
    {

        return isset($this->_query['skip']) ? $this->_query['skip'] : -1;
    }

    public function page($page, $pagesize)
    {
        return $this->limit($pagesize, $pagesize * ($page - 1));
    }

    public function sort(array $order)
    {

        $this->_query['sort'] = $order;
        return $this;
    }

    public function getSort()
    {
        return $this->_query['sort'] ?: [];
    }

    public function addOption($key, $value)
    {
        $this->_query['options'][$key] = $value;

        return $this;
    }

    public function addOptions(array $options)
    {
        foreach ($options as $k => $value) {

            $this->addOption($k, $value);
        }
        return $this;
    }

    public function getOptions()
    {
        return $this->_query['options'] ?: [];
    }

    /**
     * 返回多行记录
     * @return array
     */
    public function queryAll()
    {

        $results = $this->query()->toArray();

        return $results;
    }

    /**
     * 返回一列记录
     * @return array
     */
    public function queryColumn()
    {

        $select = $this->getSelect();

        // 只取某一行时,去掉默认添加的_id字段
        if (!isset($select['_id'])) {

            $this->select('', '_id');
        }

        $results = $this->query()->toArray();

        if (empty($results)) {
            return [];
        }

        $column = [];

        foreach ($results as $k => $result) {
            $column[] = reset($result);
        }
        return $column;
    }

    /**
     * 返回一行记录
     * @return array
     */
    public function queryRow()
    {

        $results = $this->query()->toArray();
        if (!empty($results)) {
            return $results[0];
        }
        return [];

    }

    /**
     * 返回一个字段的一个值
     * @return bool|mixed
     */
    public function queryOne()
    {
        $select = $this->getSelect();

        // 只取某一行时,去掉默认添加的_id字段
        if (!isset($select['_id'])) {

            $this->select('', '_id');
        }

        $results = $this->query()->toArray();

        if (!empty($results)) {
            return reset($results[0]);
        }

        return false;
    }

    /**
     * @see http://php.net/manual/en/mongodb-driver-query.construct.php
     *
     * @return \MongoDB\Driver\Cursor
     * @throws Exception
     */
    public function query()
    {
        $collection = $this->getFrom();

        $where = $this->getWhere();

        $options = $this->buildOptions();

        $query = new \MongoDB\Driver\Query($where, $options);

        $start   = microtime(true);
        $cursor  = $this->mdb->executeQuery($collection, $query);
        $end     = microtime(true);
        $consume = round(($end - $start) * 1000);

        Loger::info(['consume' => $consume, 'query' => $this->_query], 'mdb.query');

        return $cursor;
    }

    public function distinct($key)
    {

        $document = [
            'distinct' => $this->getFrom(),
            'key'      => $key,
        ];

        $where = $this->getWhere();

        if ($where) {
            $document['query'] = $where;
        }

        $document = array_merge($document, $this->buildOptions());

        $command = new Command($document);

        $start   = microtime(true);
        $cursor  = $this->mdb->executeCommand($command);
        $end     = microtime(true);
        $consume = round(($end - $start) * 1000);

        Loger::info(['consume' => $consume, 'action' => 'distinct', 'document' => $document], 'mdb.query');

        $res = reset($cursor->toArray());
        return $res['values'];
    }

    public function count()
    {

        $document = [
            'count' => $this->getFrom(),
            'query' => $this->getWhere(),
        ];

        $options = $this->buildOptions();

        $document = array_merge($document, $options);

        $command = new Command($document);

        $start   = microtime(true);
        $res     = reset($this->mdb->executeCommand($command)->toArray());
        $end     = microtime(true);
        $consume = round(($end - $start) * 1000);

        Loger::info(['consume' => $consume, 'action' => 'count', 'document' => $document], 'mdb.query');

        if (!empty($res)) {
            return $res['n'];
        }
        return false;
    }

    /**
     * Mongo文档例子:
     * db.orders.aggregate([
     * { $match: { status: "A" } },
     * { $group: { _id: "$cust_id", total: { $sum: "$amount" } } },
     * { $sort: { total: -1 } }
     * ])
     *
     * @param string $sum // 求和的字段, eg. '$price' | 'price'
     * @param string|int $groupBy // 分组的字段, 默认不分组 eg. '$mid' | 'mid'
     * @return array
     * @throws Exception
     */
    public function sum($sum, $groupBy = 1)
    {

        if (is_string($sum) && !is_numeric($sum) && strncmp($sum, '$', 1) !== 0) {
            $sum = '$' . $sum;
        }

        if (is_string($groupBy) && !is_numeric($groupBy) && strncmp($groupBy, '$', 1) !== 0) {
            $groupBy = '$' . $groupBy;
        }

        $collection = $this->getFrom();

        $pipeline = [];

        $group = ['$group' => ['_id' => $groupBy, 'sum' => ['$sum' => $sum]]];
        $match = $this->getWhere();
        if ($match) {
            $pipeline[] = ['$match' => $match];
        }

        $pipeline[] = $group;

        $sort = $this->getSort();
        if ($sort) {
            $pipeline[] = ['$sort' => $sort];
        }

        $skip = $this->getSkip();
        if ($skip > 0) {
            $pipeline[] = ['$skip' => $skip];
        }

        $limit = $this->getLimit();
        if ($limit > 0) {
            $pipeline[] = ['$limit' => $limit];
        }

        $document = [
            'aggregate' => $collection,
            'pipeline'  => $pipeline,
            'cursor'    => new \stdClass(),
        ];

        $command = new Command($document);

        $start   = microtime(true);
        $res     = $this->mdb->executeCommand($command)->toArray();
        $end     = microtime(true);
        $consume = round(($end - $start) * 1000);

        Loger::info(['consume' => $consume, 'action' => 'sum', 'document' => $document], 'mdb.query');

        return $res;
    }

    /**
     * @see https://docs.mongodb.com/manual/reference/method/db.collection.aggregate/
     *
     * @param array $pipeline
     * @return \MongoDB\Driver\Cursor
     * @throws Exception
     */
    public function aggregate(array $pipeline)
    {
        $document = [
            'aggregate' => $this->getFrom(),
            'pipeline'  => $pipeline,
            'cursor'    => new \stdClass(),
        ];

        $document = array_merge($document, $this->getOptions());

        $command = new Command($document);

        $start   = microtime(true);
        $cursor  = $this->mdb->executeCommand($command);
        $end     = microtime(true);
        $consume = round(($end - $start) * 1000);

        Loger::info(['consume' => $consume, 'action' => 'aggregate', 'document' => $document], 'mdb.query');

        return $cursor;
    }

    protected function buildOptions()
    {

        $options = $this->getOptions();

        $select = $this->getSelect();
        if ($select) {
            $options['projection'] = $select;
        }

        $limit = $this->getLimit();
        if ($limit > 0) {

            $options['limit'] = $limit;
        }

        $skip = $this->getSkip();
        if ($skip > 0) {
            $options['skip'] = $skip;
        }

        $sort = $this->getSort();
        if ($sort) {
            $options['sort'] = $sort;
        }


        return $options;
    }

    /**
     * 查看当前库下所有的集合
     * @param bool $onlyName onlyName 为 true 时只返回集合名称
     * @return array
     */
    public function showCollections($onlyName = true)
    {
        $document = ['listCollections' => 1];
        $command  = new Command($document);

        $start   = microtime(true);
        $cursor  = $this->mdb->executeCommand($command);
        $end     = microtime(true);
        $consume = round(($end - $start) * 1000);

        Loger::info(['consume' => $consume, 'action' => 'show-collections', 'document' => $document], 'mdb.query');

        $collections = $cursor->toArray();

        $ret = [];
        if ($onlyName) {
            foreach ($collections as $collection) {
                $ret[] = $collection['name'];
            }
        } else {
            $ret = $collections;
        }
        return $ret;
    }
}
