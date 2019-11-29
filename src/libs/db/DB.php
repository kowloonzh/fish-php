<?php

namespace libs\db;

use libs\log\Loger;

/**
 * Description of DB
 * 数据库链接
 * @author zhangjiulong
 */
class DB extends \frame\base\God
{

    /**
     * 数据源名称或叫做 DSN，包含了请求连接到数据库的信息。
     * eg:
     * $dsn  =  'mysql:dbname=testdb;host=127.0.0.1' ;
     * @var string
     */
    public $dsn;

    /**
     * DSN字符串中的用户名
     * @var string
     */
    public $username;

    /**
     * DSN字符串中的密码
     * @var string
     */
    public $password;

    /**
     * pdo的属性配置
     * 一个具体驱动的连接选项的键=>值数组。
     * @var array
     */
    public $atttibutes;

    /**
     * 数据库的字符集
     * @var string
     */
    public $charset = 'utf8';

    /**
     * PDO连接对象
     * @var \PDO
     */
    public $pdo;

    /**
     * PDO对象的类名，可扩展为原声PDO的子类对象
     * @var string
     */
    public $pdoClass = 'PDO';

    /**
     * 表前缀或者表后缀
     * @var string
     */
    public $tablePrefix = '';

    /**
     * 驱动名称 mysql|mssql等
     * @var string
     */
    private $_driverName;

    /**
     * 事务对象
     * @var Transaction
     */
    private $_transaction;

    /**
     * 是否自动重连
     * @var boolean
     */
    public $auto_reconnect = false;

    /**
     * 记录执行超时的日志,单位 ms
     * @var int
     */
    public $log_timeout = 1000;

    /**
     *
     * @param string $id 容器中DB对应的类ID标示
     * @param boolean $throwException
     * @return \libs\db\DB
     */
    public static function di($id = 'db', $throwException = true)
    {
        return parent::di($id, $throwException);
    }

    /**
     * 获取数据库链接是否已经建立
     * @return boolean 链接建立成功返回true
     */
    public function getIsActive()
    {
        return $this->pdo !== null;
    }

    public function init()
    {
        parent::init();
        //默认自动打开
        $this->open();
    }

    /**
     * 打开数据库链接
     * @throws \frame\base\Exception
     */
    public function open()
    {
        if ($this->pdo !== null) {
            return;
        }
        if (empty($this->dsn)) {
            throw new \frame\base\Exception('FrameDB::dsn cannot be empty!');
        }
        //@TODO log
        try {
            $this->pdo = $this->createPdoInstance();
            $this->initConnection();
        } catch (\PDOException $e) {
            Loger::error(['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'sql.connect');
            throw new \frame\base\Exception($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * 关闭数据库链接
     */
    public function close()
    {
        if ($this->pdo !== null) {
            //@TODO log
            $this->pdo          = null;
            $this->_transaction = null;
        }
    }

    /**
     * 创建PDO实例
     * @return \PDO
     */
    protected function createPdoInstance()
    {
        $pdoClass = $this->pdoClass;
        return new $pdoClass($this->dsn, $this->username, $this->password, $this->atttibutes);
    }

    /**
     * 初始化数据库连接
     */
    protected function initConnection()
    {
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        if ($this->charset !== null && in_array($this->getDriverName(), ['mysql', 'pgsql', 'mysqli', 'cubrid'])) {
            $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
        }
    }

    /**
     * 获取驱动名
     * @return string
     */
    public function getDriverName()
    {
        if ($this->_driverName === null) {
            $this->_driverName = strtolower(substr($this->dsn, 0, strpos($this->dsn, ':')));
        }
        return $this->_driverName;
    }

    /**
     * 设置驱动名
     * @param string $driverName
     */
    public function setDriverName($driverName)
    {
        $this->_driverName = strtolower($driverName);
    }

    /**
     * 开启事务
     * @return \libs\db\Transaction
     */
    public function beginTransaction()
    {
        $this->open();
        if (($transaction = $this->getTransaction()) === null) {
            $transaction = $this->_transaction = new Transaction(['db' => $this]);
        }
        $transaction->begin();
        return $transaction;
    }

    /**
     * 返回事务
     * @return \libs\db\Transaction
     */
    public function getTransaction()
    {
        return $this->_transaction && $this->_transaction->getIsActive() ? $this->_transaction : null;
    }

    /**
     * 引号标记sql语句
     * @param string $sql
     * @return string
     */
    public function quoteSql($sql)
    {
        return preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/', function ($matches) {
            if (isset($matches[3])) {
                return $this->quoteColumnName($matches[3]);
            } else {
                return str_replace('%', $this->tablePrefix, $this->quoteTableName($matches[2]));
            }
        }, $sql
        );
    }

    /**
     * 引号标记字段名
     * @param string $name
     * @return string
     */
    public function quoteColumnName($name)
    {
        if (strpos($name, '(') !== false || strpos($name, '[[') !== false || strpos($name, '{{') !== false) {
            return $name;
        }
        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $pos)) . '.';
            $name   = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }
        return $prefix . $this->quoteSimpleColumnName($name);
    }

    /**
     * 引号标记表名
     * @param string $name
     * @return string
     */
    public function quoteTableName($name)
    {
        if (strpos($name, '(') !== false || strpos($name, '{{') !== false) {
            return $name;
        }
        if (strpos($name, '.') === false) {
            return $this->quoteSimpleTableName($name);
        }
        $parts = explode('.', $name);
        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimpleTableName($part);
        }
        return implode('.', $parts);
    }

    /**
     * 引号标记表名
     * @param string $name
     * @return string
     */
    public function quoteSimpleTableName($name)
    {
        return strpos($name, '`') !== false ? $name : '`' . $name . '`';
    }

    /**
     * 引号标记字段名
     * @param string $name
     * @return string
     */
    public function quoteSimpleColumnName($name)
    {
        return strpos($name, '`') !== false || $name === '*' ? $name : '`' . $name . '`';
    }

    /**
     * 引号标记值
     * @param string $str
     * @return string
     */
    public function quoteValue($str)
    {
        if (!is_string($str)) {
            return $str;
        }
        $this->open();
        if (($value = $this->pdo->quote($str)) !== false) {
            return $value;
        } else {
            // the driver doesn't support quote (e.g. oci)
            return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
        }
    }

    /**
     * 获取pdo类型，pdo绑定参数时使用
     * @staticvar array $map
     * @param mixed $value
     * @return int
     */
    public function getPdoType($value)
    {
        static $map = array
        (
            'boolean'  => \PDO::PARAM_BOOL,
            'integer'  => \PDO::PARAM_INT,
            'string'   => \PDO::PARAM_STR,
            'resource' => \PDO::PARAM_LOB,
            'NULL'     => \PDO::PARAM_NULL,
        );
        $type = gettype($value);
        return isset($map[$type]) ? $map[$type] : \PDO::PARAM_STR;
    }

    /**
     * 创建Query操作对象
     * @param string|null $sql
     * @param array $params
     * @return \libs\db\Query
     */
    public function createQuery($sql = null, $params = [])
    {
        $query = new Query([
            'db'  => $this,
            'sql' => $sql
        ]);
        return $query->bindValues($params);
    }

}

