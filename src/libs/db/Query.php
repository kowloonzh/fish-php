<?php

namespace libs\db;

use frame\base\Object;
use libs\log\Loger;
use Load;
use PDO;
use PDOStatement;

/**
 * Description of Query
 * 数据库crud操作类
 * for example
 * ~~~~~~~
 * 特别说明，query对象绑定参数值支持:param=$value的方式，不支持"?"绑定方式
 *
 * 表user：
 * CREATE TABLE `user` (
 * `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 * `name` varchar(255) NOT NULL DEFAULT '',
 * `age` tinyint(3) unsigned NOT NULL DEFAULT '0',
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * 获取Db数据库连接对象和query对象
 * $db = new \libs\db\DB(['dsn'=>'mysql:dbname=testdb;host=127.0.0.1','username'=>'xxx','password'=>'xxxx']);   //db连接对象应采用更合理的单例
 * $query = new \libs\db\Query(['db'=>$db]);
 *
 * 单条语句插入：INSERT INTO `user` (`name`, `age`) VALUES ('zhangjiulong', 3)
 * $res = $query->insert('user',array(
 *      'name'=>'zhangjiulong',
 *      'age'=>3
 * ));
 * 返回影响的行数
 * 如果要获取最新插入的自增id，可使用 $query->getLastInsertId() 获取
 *
 * 多条语句插入：INSERT INTO `user`(`name`, `age`) VALUES ('lisi', 13), ('wangwu', 17)
 * $res = $query->batchInsert('user', array('name', 'age'), array(
 *      array('lisi','13'),
 *      array('wangwu',17),
 * ));
 * 返回影响的行数
 *
 *
 * 修改：UPDATE `user` SET `name`='zhangsanfeng' WHERE `id`=33
 * $res = $query->update('user', ['name'=>'zhangsanfeng'], ['id'=>33]);
 * return: 受影响的行数
 * 注：update方法的第三个参数为条件表达式，支持多种用法，详见下文的`条件表达式`具体使用方法
 *
 * 删除：DELETE FROM `user` WHERE `id`=34
 * $res = $query->delete('user',['id'=>34]);
 * 返回受影响的行数
 * 注：delete方法的第二个参数为条件表达式，支持多种用法，详见下文的`条件表达式`具体使用方法
 *
 *
 * 查询所有集合(采用单个查询条件)  SELECT * FROM `user` WHERE age>18
 * $res = $query->select('*')->from('user')->where('age>:age',[':age'=>18])->queryAll();
 * return: 二维关联数组
 * [
 *      ['id'=>1,'name'=>'zs','age'=>20],
 *      ['id'=>2,'name'=>'li','age'=>35],
 *      ....
 * ]
 *
 * 查询一条记录（采用多个查询条件） SELECT `id`, `name`, `age` FROM `user` WHERE (age>=18) AND (`name` LIKE '%zjl%')
 * $res = $query->select('id,name,age')->from('user')->where('age>=:age',[':age'=>18])->andWhere(['like','name','%zjl%'])->queryRow();
 * return: 一维关联数组
 * ['id'=>1,'name'=>'zs','age'=>20]
 *
 * 查询一个值 SELECT count(1) FROM `user`
 * $res = $query->select('count(1)')->from('user')->queryOne();
 * return: count(1)对应的值
 * 10
 *
 * 查询某个字段的集合 SELECT `id` FROM `user` WHERE id>5
 * $res = $query->select('id')->from('user')->where('id>:id',[':id'=>5])->queryColumn();
 * return: 一维索引数组
 * [1,2,3,4,5,10]
 *
 * 内联查询 SELECT `a`.*, `b`.* FROM `user` `a` JOIN `time` `b` ON a.id=b.uid WHERE `a`.`id`=5
 * $res = $query->select('a.*,b.utime')
 * ->from('user as a')
 * ->join('time as b', 'a.id=b.uid')
 * ->where(['a.id'=>5])
 * ->queryAll();
 *
 * 左联查询 SELECT `a`.*, `b`.`name` FROM `user` `a` LEFT JOIN `time` `b` ON a.id=b.uid WHERE (a.id>=5) AND (a.id<=10)
 * $res = $query->select('a.*,b.name')
 * ->from('user as a')
 * ->leftJoin('time as b', 'a.id=b.uid')
 * ->where('a.id>=:id',[':id'=>5])
 * ->andWhere('a.id<=:lid',[':lid'=>10])
 * ->queryAll();
 *
 * 分组使用 SELECT min(id),age FROM `user` GROUP BY `age`
 * $res = $query->select('min(id),age')->from('user')->group('age')->queryAll();
 *
 * 排序分页(页大小5,当前页是2，取id大于10的集合，按id倒叙排列)
 * $page = 2;   $pagesize = 5;
 * 1. 组装where条件
 * $query->select('id,name,age')->from('user')->where('id>:id',[':id'=>10])->order('id desc');
 * 2. 获取总数
 * $total = $query->count();
 * 3. 根据页大小设置偏移量
 * $query->limit($pagesize,$pagesize*($page-1));
 * 4. 执行查询
 * $res = $query->queryAll()
 * (表示取id大于10的集合，按id倒叙排列，取10条，跳过5条)
 *
 * 条件表达式 where andWhere orWhere delete update中使用
 * 总体来说，分为3种使用情况,以where($condition,$params)为例
 * 1.键值对数组
 * where(['id'=>3])                 =======>  where id=3
 * where(['id'=>3,'name'=>'zjl'])   =======>  where id=3 and name='zjl'
 * where(['id'=>[3,4,5],'name'=>'zjl'])  ====> where id in (3,4,5) and name='zjl'
 *
 * 2.字符串参数
 * where('id>:id',[':id'=>3])      ========>  where id>3
 * where('id=:id',[':id'=>3])      ========>  where id=3
 * where('name like :name and id>:id',[':name'=>'%zjl%',':id'=>3])  =======> where id=3 and name like "%zjl%"
 *
 * 3.索引数组（索引数组包含三个值,第一个值是操作符IN|NOT IN|LIKE|AND|OR,第二个值是一个字段或者一个表达式，第三个值为字段对应的值或者另一个表达式）
 * where(['IN','id',[3,4,5]])           =====>  where id in (3,4,5)
 * where(['NOT IN','id',[3,4,5]])       =====>  where id not in (3,4,5)
 * where(['LIKE','name','%zjl%'])       =====>  where name like "%zjl%"
 * where(['AND',['id'=>3],'name=:name'],[':name'=>'zjl'])       ===> where id=3 and name="zjl"
 * where(['OR','id>:id',['NOT IN','id',[7,8]],[':id'=>3])        ===> where id>3 or id not in (7,8)
 *
 * 事务操作($db是libs\db\DB的实例)
 * $trans = $db->beginTransaction();        //$trans为libs\db\Transaction对象实例
 * try{
 *      $db->createQuery()->insert($table,$columns);
 *      $db->createQuery()->insert($table,$columns);
 *      ....
 *      $trans->commit();
 * }catch(\Exception $e){
 *      echo $e->getMessage();
 *      $trans->rollback();
 * }
 *
 * 直接写原生sql
 * $query = $db->createQuery($sql);
 * //直接执行execute，返回影响函数
 * $query->execute();
 * //或者执行query系列操作，返回结果集
 * $query->queryAll();
 *
 *
 * ~~~~~~~~
 * @author zhangjiulong
 */
class Query extends Object
{

    /**
     * 绑定参数前缀，in查询等中使用
     */
    const PARAM_PREFIX = ':ZJL9';

    /**
     *
     * @var DB
     */
    public $db;

    /**
     *
     * @var PDOStatement
     */
    public $pdoStatement;

    /**
     * PDO的取值模式，默认为取关联数组
     * @var int
     */
    public $fetchMode = \PDO::FETCH_ASSOC;

    /**
     * 对预处理语句绑定的参数
     * @var array
     */
    public $params = [];

    /**
     * 设置debug，当true时，会打印出sql
     */
    private $debug = false;
    /**
     * 实际绑定的参数，在bindValues()中使用
     * @var array
     */
    private $_pendingParams = [];

    /**
     * sql语句
     * @var string
     */
    private $_sql;

    /**
     * SQL语句的组装数组
     * @var array
     */
    private $_query;

    public function __construct($config = array())
    {
        //因sql依赖db,先处理db
        if (isset($config['db'])) {
            $this->db = $config['db'];
            unset($config['db']);
        }
        if ($this->db == null) {
            $this->db = Load::$app->get('db');
        }
        parent::__construct($config);
    }

    /**
     * 返回当前的sql语句（未绑定参数）
     * @return string
     */
    public function getSql()
    {
        if ($this->_sql == '' && !empty($this->_query)) {
            $this->setSql($this->buildQuery($this->_query));
        }
        return $this->_sql;
    }

    /**
     * 实际执行的sql语句(参数绑定后的)
     * @return string
     */
    public function getRawSql()
    {
        if (empty($this->params)) {
            return $this->getSql();
        } else {
            $params = [];
            foreach ($this->params as $name => $value) {
                if (is_string($value)) {
                    $params[$name] = $this->db->quoteValue($value);
                } elseif ($value === null) {
                    $params[$name] = 'NULL';
                } else {
                    $params[$name] = $value;
                }
            }
            return strtr($this->getSql(), $params);
        }
    }

    /**
     * 设置sql语句
     * @param string $sql
     * @return Query
     */
    public function setSql($sql)
    {
        if ($sql !== $this->_sql) {
            $this->cancel();
            $this->_sql = $this->db->quoteSql($sql);
        }
        return $this;
    }

    /**
     * 执行sql 增删改操作执行此方法
     * @return int 返回影响的行数
     * @throws \frame\base\Exception
     */
    public function execute()
    {
        $sql = $this->getSql();
        if ($sql == '') {
            return 0;
        }
        $this->prepare();

        $rawSql = $this->getRawSql();

        try {
            //@TODO 此处可记录SQL日志 log($this->getRawSql())
            Loger::info($rawSql, 'sql.execute');
            $begin_time = microtime(true);
            @$this->pdoStatement->execute();
            $spend   = microtime(true) - $begin_time;
            $consume = round($spend * 1000);
            Loger::info('The above sql consume ' . $consume . ' ms', 'sql.execute');

            // 记录超时的日志
            if ($consume >= $this->db->log_timeout) {
                Loger::info(['consume(ms)' => $consume, 'sql' => $rawSql], 'sql.timeout');
            }

            $n = $this->pdoStatement->rowCount();
            return $n;
        } catch (\PDOException $e) {
            //如果自动可以自动重连
            if (in_array($e->errorInfo[1], array(2013, 2006)) && $this->db->auto_reconnect) {
                $this->db->close();
                $this->cancel();
                $this->bindValues();
                return $this->execute();
            } else {

                // 记录错误的日志
                Loger::error($rawSql, 'sql');

                throw new \frame\base\Exception('Error to execute sql, ' . $e->getMessage(), (int)$e->getCode() + 1000);
            }
        }
    }

    /**
     * SQL语句的预处理
     * @return null
     * @throws \frame\base\Exception
     */
    public function prepare()
    {
        try {
            if ($this->pdoStatement) {
                $this->bindPendingParams();
                return;
            }
            $sql = $this->getSql();

            /**
             * 连接数据库
             */
            $this->db->open();
            /**
             * 预处理
             */
            $this->pdoStatement = $this->db->pdo->prepare($sql);
            /**
             * 绑定参数
             */
            $this->bindPendingParams();
        } catch (\Exception $e) {
            throw new \frame\base\Exception('Fail to prepare SQL: ' . $sql . ',' . $e->getMessage(), (int)$e->getCode() + 1000);
        }
    }

    /**
     * 取消sql预处理和参数绑定
     */
    public function cancel()
    {
        $this->pdoStatement = null;
    }

    /**
     * 重置Query对象
     * @param boolean $keepFrom 是否保留query的From值
     * @return Query
     */
    public function reset($keepFrom = false)
    {
        $from                 = $this->getFrom();
        $this->_sql           = null;
        $this->_pendingParams = [];
        $this->_query         = null;
        $this->params         = [];
        $this->cancel();
        if ($keepFrom) {
            $this->setFrom($from);
        }
        return $this;
    }

    /**
     * 绑定值
     * @param array $values
     * @return Query
     */
    public function bindValues($values = [])
    {
        if (!empty($values)) {
            $this->params = array_merge($this->params, $values);
        }

        foreach ($this->params as $name => $value) {
            if (is_array($value)) {
                $this->_pendingParams[$name] = $value;
            } else {
                $type = $this->db->getPdoType($value);

                $this->_pendingParams[$name] = [$value, $type];
            }
        }
        return $this;
    }

    /**
     * 绑定单个值
     * @param string $name
     * @param mixed $value
     * @param int $dataType
     * @return Query
     */
    public function bindValue($name, $value, $dataType = null)
    {
        if ($dataType === null) {
            $dataType = $this->db->getPdoType($value);
        }
        $this->_pendingParams[$name] = [$value, $dataType];
        $this->params[$name]         = $value;
        return $this;
    }

    /**
     * 执行PDOstatement的参数绑定
     */
    public function bindPendingParams()
    {
        foreach ($this->_pendingParams as $name => $value) {
            $this->pdoStatement->bindParam($name, $value[0], $value[1]);
        }
        $this->_pendingParams = [];
    }

    /**
     * 生成sql语句通过$query数组
     * @param array $query
     * @return string
     * @throws \frame\base\Exception
     */
    public function buildQuery($query)
    {
        $sql = $query['use_master'] ? '/*master*/ ' : '';
        $sql .= !empty($query['distinct']) ? 'SELECT DISTINCT' : 'SELECT';
        $sql .= ' ' . (!empty($query['select']) ? $query['select'] : '*');
        if (!empty($query['from'])) {
            $sql .= " FROM " . $query['from'];
        } else {
            throw new \frame\base\Exception('The DB query must contain the "from" portion');
        }
        if (!empty($query['join'])) {
            $sql .= " " . (is_array($query['join']) ? implode(" ", $query['join']) : $query['join']);
        }
        if (!empty($query['where'])) {
            $sql .= " WHERE " . $query['where'];
        }
        if (!empty($query['group'])) {
            $sql .= " GROUP BY " . $query['group'];
        }
        if (!empty($query['having'])) {
            $sql .= " HAVING " . $query['having'];
        }
        if (!empty($query['union'])) {
            $sql .= " UNION ( " . (is_array($query['union']) ? implode(" ) UNION ( ", $query['union']) : $query['union']) . ')';
        }
        if (!empty($query['order'])) {
            $sql .= " ORDER BY " . $query['order'];
        }
        $limit  = isset($query['limit']) ? (int)$query['limit'] : -1;
        $offset = isset($query['limit']) ? (int)$query['offset'] : -1;
        if ($limit > 0 || $offset > 0) {
            $sql = $this->applyLimit($sql, $limit, $offset);
        }
        return $sql;
    }

    /**
     * 处理sql的偏移量和limit语句
     * @param string $sql
     * @param int $limit
     * @param int $offset
     * @return string
     */
    public function applyLimit($sql, $limit, $offset)
    {
        if ($limit >= 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }
        if ($offset >= 0) {
            $sql .= ' OFFSET ' . (int)$offset;
        }
        return $sql;
    }

    /**
     * 插入单条数据
     * @param string $table 表名
     * @param array $columns [字段=>值]
     * @return int 影响的行数
     */
    public function insert($table, $columns)
    {
        $names        = [];
        $placeholders = [];
        foreach ($columns as $name => $value) {
            $names[] = $this->db->quoteColumnName($name);
            if ($value instanceof DbExpression) {
                $placeholders[] = $value->expression;
                $this->addParams($value->params);
            } else {
                $placeholders[] = ':' . $name;
                $this->addParams([":$name" => $value]);
            }
        }
        $sql = 'INSERT INTO ' . $this->db->quoteTableName($table)
            . ' (' . implode(', ', $names) . ') VALUES ('
            . implode(', ', $placeholders) . ')';

        return $this->setSql($sql)->bindValues()->execute();
    }

    /**
     * 返回最新插入的一个自增id
     * @return string
     */
    public function getLastInsertId()
    {
        return $this->db->pdo->lastInsertId();
    }

    /**
     * 批量插入数据
     * @param string $table // 表名
     * @param array $columns // 字段列表[column1,column2...]
     * @param array $rows // 多行值 [[column1=>value1,column2=>value2],[....]]
     * @return int // 影响的行数
     * @throws \frame\base\Exception
     */
    public function batchInsert($table, $columns, $rows)
    {
        $values = [];
        if (empty($rows)) {
            throw new \frame\base\Exception('BatchInsert Error: The VALUES can not be empty');
        }
        foreach ($rows as $row) {
            $vs = [];
            foreach ($row as $i => $value) {
                if ($value === null) {
                    $value = 'NULL';
                } elseif ($value === false) {
                    $value = 0;
                } else {
                    $phName = self::PARAM_PREFIX . count($this->params);
                    $this->addParams(["$phName" => $value]);
                    $value = "$phName";
                }
                $vs[$i] = $value;
            }
            //表示为关联数组
            if (!isset($vs[0])) {
                $rvs = [];
                foreach ($columns as $name) {
                    $rvs[] = $vs[$name];
                }
                $vs = $rvs;
            }
            $values[] = '(' . implode(', ', $vs) . ')';
        }

        foreach ($columns as $i => $name) {
            $columns[$i] = $this->db->quoteColumnName($name);
        }

        $sql = 'INSERT INTO ' . $this->db->quoteTableName($table) .
            '(' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values);

        return $this->setSql($sql)->bindValues()->execute();
    }

    /**
     * 修改表记录
     * @param string $table 表名
     * @param array $columns [字段=>值]
     * @param string|array $condition 条件表达式
     * @param array $params 参数
     * @return int 影响的行数
     */
    public function update($table, $columns, $condition = '', $params = [])
    {
        $lines = array();
        foreach ($columns as $name => $value) {
            if ($value instanceof DbExpression) {
                $lines[] = $this->db->quoteColumnName($name) . '=' . $value->expression;
                $this->addParams($value->params);
            } else {
                $lines[] = $this->db->quoteColumnName($name) . '=:' . $name;
                $this->addParams([":$name" => $value]);
            }
        }
        $sql = 'UPDATE ' . $this->db->quoteTableName($table) . ' SET ' . implode(', ', $lines);
        if (($where = $this->buildCondition($condition)) != '') {
            $sql .= ' WHERE ' . $where;
        }
        return $this->setSql($sql)->bindValues($params)->execute();
    }

    /**
     * 删除表记录
     * @param string $table 表名
     * @param array|string $condition 条件表达式
     * @param array $params 待绑定的参数
     * @return int 影响的行数
     */
    public function delete($table, $condition = '', $params = [])
    {
        $sql = 'DELETE FROM ' . $this->db->quoteTableName($table);
        if (($where = $this->buildCondition($condition)) != '') {
            $sql .= ' WHERE ' . $where;
        }
        return $this->setSql($sql)->bindValues($params)->execute();
    }

    /**
     * 相当于sql中的select语句
     * @param string|array $columns 字段列表
     * @param string $option
     * @return Query
     */
    public function select($columns = '*', $option = '')
    {
        if (is_string($columns) && strpos($columns, '(') !== false) {
            $this->_query['select'] = $columns;
        } else {
            if (!is_array($columns))
                $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);

            foreach ($columns as $i => $column) {
                if (is_object($column))
                    $columns[$i] = (string)$column;
                elseif (strpos($column, '(') === false) {
                    if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/', $column, $matches))
                        $columns[$i] = $this->db->quoteColumnName($matches[1]) . ' AS ' . $this->db->quoteColumnName($matches[2]);
                    else
                        $columns[$i] = $this->db->quoteColumnName($column);
                }
            }
            $this->_query['select'] = implode(', ', $columns);
        }
        if ($option != '')
            $this->_query['select'] = $option . ' ' . $this->_query['select'];
        return $this;
    }

    /**
     * 获取select语句
     * @return string
     */
    public function getSelect()
    {
        return isset($this->_query['select']) ? $this->_query['select'] : '';
    }

    /**
     * 设置select语句
     * @param string|array $value
     */
    public function setSelect($value)
    {
        $this->select($value);
    }

    /**
     * 设置select distinct的情况
     * @param string $columns
     * @return Query
     */
    public function selectDistinct($columns = '*')
    {
        $this->_query['distinct'] = true;
        return $this->select($columns);
    }

    /**
     * 设置使用主库
     * @return $this
     */
    public function setUseMaster()
    {
        $this->_query['use_master'] = true;
        return $this;
    }

    /**
     * 返回是否有distinct
     * @return string
     */
    public function getDistinct()
    {
        return isset($this->_query['distinct']) ? $this->_query['distinct'] : false;
    }

    /**
     * 设置是否有disctict
     * @param boolean $value
     */
    public function setDistinct($value)
    {
        $this->_query['distinct'] = $value;
    }

    /**
     * 相当于sql中的from语句
     * @param string|array $tables 表名
     * @return Query
     */
    public function from($tables)
    {
        if (is_string($tables) && strpos($tables, '(') !== false)
            $this->_query['from'] = $tables;
        else {
            if (!is_array($tables))
                $tables = preg_split('/\s*,\s*/', trim($tables), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($tables as $i => $table) {
                if (strpos($table, '(') === false) {
                    if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/', $table, $matches))  // with alias
                        $tables[$i] = $this->db->quoteTableName($matches[1]) . ' ' . $this->db->quoteTableName($matches[2]);
                    else
                        $tables[$i] = $this->db->quoteTableName($table);
                }
            }
            $this->_query['from'] = implode(', ', $tables);
        }
        return $this;
    }

    public function getFrom()
    {
        return isset($this->_query['from']) ? $this->_query['from'] : '';
    }

    public function setFrom($value)
    {
        $this->from($value);
    }

    /**
     * 添加待绑定的参数 只支持:pl=>val的绑定方式
     * @param array $params
     * @return $this
     * @throws \frame\base\Exception
     */
    public function addParams(array $params)
    {
        if (!empty($params)) {

            if (isset($params[0]) || isset($params[1])) {
                throw new \frame\base\Exception('Not support the placeholder "?"');
            }

            if (empty($this->params)) {
                $this->params = $params;
            } else {
                foreach ($params as $name => $value) {
                    $this->params[$name] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * 设置where条件，相当于sql中的where
     * @param string|array $conditions
     * @param array $params
     * @return Query
     */
    public function where($conditions, $params = [])
    {
        return $this->andWhere($conditions, $params);
    }

    /**
     * 设置where条件，相当于sql中的and where
     * @param string|array $conditions
     * @param array $params
     * @return Query
     */
    public function andWhere($conditions, $params = [])
    {
        if (isset($this->_query['where']))
            $this->_query['where'] = $this->buildCondition(array('AND', $this->_query['where'], $conditions));
        else
            $this->_query['where'] = $this->buildCondition($conditions);

        $this->addParams($params);
        return $this;
    }

    /**
     * 设置where条件，相当于sql中的or where
     * @param string|array $conditions
     * @param array $params
     * @return Query
     */
    public function orWhere($conditions, $params = [])
    {
        if (isset($this->_query['where']))
            $this->_query['where'] = $this->buildCondition(array('OR', $this->_query['where'], $conditions));
        else
            $this->_query['where'] = $this->buildCondition($conditions);

        $this->addParams($params);
        return $this;
    }

    public function getWhere()
    {
        return isset($this->_query['where']) ? $this->_query['where'] : '';
    }

    public function setWhere($value)
    {
        $this->where($value);
    }

    /**
     * 内联 join table
     * @param string $table 表名
     * @param string $conditions 连接条件
     * @param array $params
     * @return Query
     */
    public function join($table, $conditions, $params = array())
    {
        return $this->joinInternal('join', $table, $conditions, $params);
    }

    public function getJoin()
    {
        return isset($this->_query['join']) ? $this->_query['join'] : '';
    }

    public function setJoin($value)
    {
        $this->_query['join'] = $value;
    }

    /**
     * 左连接 left join table
     * @param string $table 表名
     * @param string $conditions 连接条件
     * @param array $params
     * @return Query
     */
    public function leftJoin($table, $conditions, $params = array())
    {
        return $this->joinInternal('left join', $table, $conditions, $params);
    }

    /**
     * 右连接 right join table
     * @param string $table 表名
     * @param string $conditions 连接条件
     * @param array $params
     * @return Query
     */
    public function rightJoin($table, $conditions, $params = array())
    {
        return $this->joinInternal('right join', $table, $conditions, $params);
    }

    /**
     * 分组 group by column
     * @param array|string $columns
     * @return Query
     */
    public function group($columns)
    {
        if (is_string($columns) && strpos($columns, '(') !== false)
            $this->_query['group'] = $columns;
        else {
            if (!is_array($columns))
                $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($columns as $i => $column) {
                if (is_object($column))
                    $columns[$i] = (string)$column;
                elseif (strpos($column, '(') === false)
                    $columns[$i] = $this->db->quoteColumnName($column);
            }
            $this->_query['group'] = implode(', ', $columns);
        }
        return $this;
    }

    public function getGroup()
    {
        return isset($this->_query['group']) ? $this->_query['group'] : '';
    }

    public function setGroup($value)
    {
        $this->group($value);
    }

    /**
     * having语句
     * @param string|array $conditions
     * @param array $params
     * @return Query
     */
    public function having($conditions, $params = [])
    {
        $this->_query['having'] = $this->buildCondition($conditions);
        $this->addParams($params);
        return $this;
    }

    public function getHaving()
    {
        return isset($this->_query['having']) ? $this->_query['having'] : '';
    }

    public function setHaving($value)
    {
        $this->having($value);
    }

    /**
     * order by 语句
     * @param string|array $columns
     * @return Query
     */
    public function order($columns)
    {
        if (is_string($columns) && strpos($columns, '(') !== false)
            $this->_query['order'] = $columns;
        else {
            if (!is_array($columns))
                $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($columns as $i => $column) {
                if (is_object($column))
                    $columns[$i] = (string)$column;
                elseif (strpos($column, '(') === false) {
                    if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches))
                        $columns[$i] = $this->db->quoteColumnName($matches[1]) . ' ' . strtoupper($matches[2]);
                    else
                        $columns[$i] = $this->db->quoteColumnName($column);
                }
            }
            $this->_query['order'] = implode(', ', $columns);
        }
        return $this;
    }

    public function getOrder()
    {
        return isset($this->_query['order']) ? $this->_query['order'] : '';
    }

    public function setOrder($value)
    {
        $this->order($value);
    }

    /**
     * 设置分页
     * @param int $page // 页码
     * @param int $pagesize // 页大小
     * @return $this
     */
    public function page($page = 1, $pagesize = 50)
    {
        if (empty($page)) {
            $page = 1;
        }
        if (empty($pagesize)) {
            $pagesize = 50;
        }
        $this->limit($pagesize, $pagesize * ($page - 1));
        return $this;
    }

    /**
     * limit语句
     * @param int $limit 限制的条件
     * @param int $offset 偏移量
     * @return Query
     */
    public function limit($limit, $offset = null)
    {
        $this->_query['limit'] = (int)$limit;
        if ($offset !== null)
            $this->offset($offset);
        return $this;
    }

    public function getLimit()
    {
        return isset($this->_query['limit']) ? $this->_query['limit'] : -1;
    }

    public function setLimit($value)
    {
        $this->limit($value);
    }

    /**
     * offset语句
     * @param int $offset 偏移量
     * @return Query
     */
    public function offset($offset)
    {
        $this->_query['offset'] = (int)$offset;
        return $this;
    }

    public function getOffset()
    {
        return isset($this->_query['offset']) ? $this->_query['offset'] : -1;
    }

    public function setOffset($value)
    {
        $this->offset($value);
    }

    /**
     * union 语句
     * @param string $sql
     * @return Query
     */
    public function union($sql)
    {
        if (isset($this->_query['union']) && is_string($this->_query['union']))
            $this->_query['union'] = array($this->_query['union']);

        $this->_query['union'][] = $sql;

        return $this;
    }

    public function getUnion()
    {
        return isset($this->_query['union']) ? $this->_query['union'] : '';
    }

    public function setUnion($value)
    {
        $this->_query['union'] = $value;
    }

    /**
     * 组装sql的条件表达式
     * @param $conditions
     * @return string
     * @throws \frame\base\Exception
     */
    protected function buildCondition($conditions)
    {
        if ($conditions instanceof DbExpression) {
            $this->addParams($conditions->params);
            return $conditions->expression;
        } elseif (!is_array($conditions)) {
            return $conditions;
        } elseif ($conditions === array()) {
            return '';
        }
        if (isset($conditions[0])) {
            return $this->processConditions($conditions);
        } else {
            return $this->buildHashCondition($conditions);
        }
    }

    protected function buildHashCondition($conditions)
    {
        $parts = [];
        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $parts[] = $this->processConditions(['IN', $column, $value]);
            } else {
                if (strpos($column, '(') === false) {
                    $column = $this->db->quoteColumnName($column);
                }
                if ($value === null) {
                    $parts[] = "$column IS NULL";
                } else {
                    $phName  = static::PARAM_PREFIX . count($this->params);
                    $parts[] = "$column=$phName";
                    $this->addParams([$phName => $value]);
                }
            }
        }
        return count($parts) === 1 ? $parts[0] : '(' . implode(') AND (', $parts) . ')';
    }

    /**
     * 组装条件
     * @param string|array $conditions
     * @return string
     * @throws \frame\base\Exception
     */
    protected function processConditions($conditions)
    {
        if (!is_array($conditions)) {
            return $conditions;
        } elseif ($conditions === array()) {
            return '';
        }
        $n        = count($conditions);
        $operator = strtoupper($conditions[0]);
        if ($operator === 'OR' || $operator === 'AND') {
            $parts = array();
            for ($i = 1; $i < $n; ++$i) {
                $condition = $this->buildCondition($conditions[$i]);
                if ($condition !== '') {
                    $parts[] = '(' . $condition . ')';
                }
            }
            return $parts === array() ? '' : implode(' ' . $operator . ' ', $parts);
        }

        if (!isset($conditions[1], $conditions[2])) {
            return '';
        }
        $column = $conditions[1];
        if (strpos($column, '(') === false) {
            $column = $this->db->quoteColumnName($column);
        }
        $values = $conditions[2];
        if (!is_array($values)) {
            $values = array($values);
        }
        if (in_array($operator, array('IN', 'NOT IN'))) {
            if ($values === array()) {
                return $operator === 'IN' ? '0=1' : '';
            }
            foreach ($values as $i => $value) {
                $phName     = static::PARAM_PREFIX . count($this->params);
                $values[$i] = $phName;
                $this->addParams([$phName => $value]);
            }
            return $column . ' ' . $operator . ' (' . implode(', ', $values) . ')';
        }

        if (in_array($operator, array('LIKE', 'NOT LIKE', 'OR LIKE', 'OR NOT LIKE'))) {
            if ($values === array()) {
                return $operator === 'LIKE' || $operator === 'OR LIKE' ? '0=1' : '';
            }
            if ($operator === 'LIKE' || $operator === 'NOT LIKE') {
                $andor = ' AND ';
            } else {
                $andor    = ' OR ';
                $operator = $operator === 'OR LIKE' ? 'LIKE' : 'NOT LIKE';
            }
            $expressions = [];
            foreach ($values as $value) {
                $phName        = static::PARAM_PREFIX . count($this->params);
                $expressions[] = $column . ' ' . $operator . ' ' . $phName;
                $this->addParams([$phName => $value]);
            }
            return implode($andor, $expressions);
        }
        throw new \frame\base\Exception('unknow operator: ' . $operator);
    }

    /**
     * 组装连接语句
     * @param string $type 连接类型
     * @param string $table 表名
     * @param string|array $conditions 连接条件
     * @param array $params 参数绑定
     * @return Query
     */
    private function joinInternal($type, $table, $conditions = '', $params = array())
    {
        if (strpos($table, '(') === false) {
            if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/', $table, $matches))  // with alias
                $table = $this->db->quoteTableName($matches[1]) . ' ' . $this->db->quoteTableName($matches[2]);
            else
                $table = $this->db->quoteTableName($table);
        }

        $conditions = $this->buildCondition($conditions);
        if ($conditions != '')
            $conditions = ' ON ' . $conditions;

        if (isset($this->_query['join']) && is_string($this->_query['join']))
            $this->_query['join'] = array($this->_query['join']);

        $this->_query['join'][] = strtoupper($type) . ' ' . $table . $conditions;

        $this->addParams($params);
        return $this;
    }

    /**
     * 设置debug时，在查询前会打印出sql，并中断
     */
    public function debug()
    {
        $this->debug = true;
        return $this;
    }

    /**
     * 返回PDOstatement对象
     * @return PDOStatement
     */
    public function query()
    {
        $stat = $this->queryInternal('', 0);
        $stat->setFetchMode($this->fetchMode);
        return $stat;
    }

    /**
     * 返回多行数据
     * @return array
     */
    public function queryAll()
    {
        return $this->queryInternal('fetchAll');
    }

    /**
     * 返回一列数据
     * @return array
     */
    public function queryColumn()
    {
        return $this->queryInternal('fetchAll', PDO::FETCH_COLUMN);
    }

    /**
     * 返回一行数据
     * @return array
     */
    public function queryRow()
    {
        $res = $this->queryInternal('fetch');
        if ($res == false) {
            return [];
        }
        return $res;
    }

    /**
     * 返回第一行第一列
     * @return string
     */
    public function queryOne()
    {
        $result = $this->queryInternal('fetchColumn', 0);
        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        } else {
            return $result;
        }
    }

    /**
     * 执行查询
     * @param string $method 查询类型
     * @param int $fetchMode 返回的数据结构
     * @return mixed
     * @throws \frame\base\Exception
     */
    private function queryInternal($method, $fetchMode = null)
    {
        /**
         * 绑定参数
         */
        $this->bindValues();
        /**
         * 预处理
         */
        $this->prepare();

        $rawSql = $this->getRawSql();

        try {
            if ($this->debug === true) {
                echo $rawSql;
                die;
            }
            //@TODO 此处记录查询语句 log($this->getRawSql())
            Loger::info($rawSql, 'sql.query');
            $begin_time = microtime(true);
            @$this->pdoStatement->execute();
            $spend   = microtime(true) - $begin_time;
            $consume = round($spend * 1000);
            Loger::info('The above sql consume ' . $consume . ' ms', 'sql.query');

            // 记录超时日志
            if ($consume >= $this->db->log_timeout) {
                Loger::info(['consume(ms)' => $consume, 'sql' => $rawSql], 'sql.timeout');
            }

            if ($method == '') {
                return $this->pdoStatement;
            } else {
                if ($fetchMode === null) {
                    $fetchMode = $this->fetchMode;
                }
                $result = call_user_func_array([$this->pdoStatement, $method], (array)$fetchMode);
                $this->pdoStatement->closeCursor();
                return $result;
            }
        } catch (\PDOException $e) {
            //如果自动可以自动重连
            if (in_array($e->errorInfo[1], array(2013, 2006)) && $this->db->auto_reconnect) {
                $this->db->close();
                $this->cancel();
                return $this->queryInternal($method, $fetchMode);
            } else {
                // 记录错误日志
                Loger::error($rawSql, 'sql');

                throw new \frame\base\Exception('Query fail:' . $e->getMessage(), (int)$e->getCode() + 1000);
            }
        }
    }

    /**
     * 根据query对象获取总条数
     * @return int
     */
    public function count()
    {
        $cloneQuery = clone $this;
        $cloneQuery->limit(-1, -1);
        $group  = $cloneQuery->getGroup();
        $having = $cloneQuery->getHaving();
        if (!empty($group) || !empty($having)) {
            $cloneQuery->order('');
            $sql = $cloneQuery->getSql();
            $sql = "SELECT COUNT(*) FROM ({$sql}) sq";
            $cloneQuery->setSql($sql);
            return $cloneQuery->queryOne();
        } else {
            if ($cloneQuery->getDistinct() == true) {
                $cloneQuery->select("COUNT(DISTINCT {$cloneQuery->getSelect()})");
            } else {
                $cloneQuery->select("COUNT(*)");
            }
            $cloneQuery->order('');
            $cloneQuery->group('');
            $cloneQuery->having('');
            return $cloneQuery->queryOne();
        }
    }
}
