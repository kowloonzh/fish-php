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
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\WriteConcern;

class MDB extends God
{

    /**
     * @var Manager
     */
    public $manager;

    public $dsn;

    public $database;

    public $username;

    public $password;

    //public $authDbName = 'admin';

    public $options = [];

    public $driverOptions = [];

    /**
     * 是否重连
     * @var bool
     */
    public $auto_reconnect = false;

    /**
     * 重连执行次数
     * @var int
     */
    public $retry = 3;

    private $_originRetry;

    /**
     * @param string $id
     * @param bool $throwException
     * @return self
     */
    static public function di($id = "mdb", $throwException = true)
    {
        return parent::di($id, $throwException);
    }

    public function open()
    {

        if ($this->manager == null) {
            $this->initConnection();
        }
    }

    public function close()
    {
        $this->manager = null;

    }

    public function init()
    {
        parent::init();
        $this->_originRetry = $this->retry;
    }

    public function resetRetry()
    {
        $this->retry = $this->_originRetry;
    }

    public function initConnection()
    {

        try {

            //if ($this->authDbName) {
            //    //$this->options['authenticationDatabase'] = $this->authDbName;
            //}
            $this->options['username'] = $this->username;
            $this->options['password'] = $this->password;

            $this->manager = new Manager($this->dsn, $this->options, $this->driverOptions);

        } catch (\Exception $e) {

            Loger::error(['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'mdb.init.connection');
            throw new Exception("Mongodb manager init failed");
        }

    }

    /**
     * 返回 mongodb 的 query 对象
     * @param string $collection
     * @return \libs\mdb\Query
     */
    public function createQuery($collection = null)
    {

        $query = new \libs\mdb\Query(['mdb' => $this]);
        if ($collection) {
            $query->from($collection);
        }
        return $query;
    }

    // @todo 读取优先级
    public function getReadPreference()
    {
        return $this->manager->getReadPreference();
        //return new ReadPreference(ReadPreference::RP_PRIMARY);
    }

    /**
     * @see http://php.net/manual/en/class.mongodb-driver-query.php
     *
     * @param $collection
     * @param $query
     * @return \MongoDB\Driver\Cursor
     */
    public function executeQuery($collection, Query $query)
    {
        RETRY:

        try {

            $this->open();

            $cursor = $this->manager->executeQuery($this->getNamespace($collection), $query, $this->getReadPreference());

        } catch (\Exception $e) {

            // 如果设置自动重连，再次执行
            if($this->auto_reconnect && $this->retry > 0){
                $this->retry--;
                $this->close();
                goto RETRY;
            }

            throw $e;
        }

        $this->resetRetry();

        // 设置返回值都为数组, 而不是对象
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        return $cursor;
    }

    /**
     * @param $collection
     * @param BulkWrite $bulk
     * @param WriteConcern|null $writeConcern
     * @return \MongoDB\Driver\WriteResult
     */
    public function executeBulkWrite($collection, BulkWrite $bulk, WriteConcern $writeConcern = null)
    {

        $namespace = $this->getNamespace($collection);

        RETRY:

        try {
            $this->open();

            $res = $this->manager->executeBulkWrite($namespace, $bulk, $writeConcern);

        } catch (\Exception $e) {

            // 如果设置自动重连，再次执行
            if($this->auto_reconnect && $this->retry > 0){
                $this->retry--;
                $this->close();
                goto RETRY;
            }

            throw $e;
        }

        $this->resetRetry();

        return $res;
    }

    /**
     * @param Command $command
     * @return \MongoDB\Driver\Cursor
     */
    public function executeCommand(Command $command)
    {
        RETRY:

        try {
            $this->open();

            $cursor = $this->manager->executeCommand($this->database, $command, $this->getReadPreference());

        } catch (\Exception $e) {

            // 如果设置自动重连，再次执行
            if($this->auto_reconnect && $this->retry > 0){
                $this->retry--;
                $this->close();
                goto RETRY;
            }

            throw $e;
        }

        $this->resetRetry();

        // 设置返回值都为数组, 而不是对象
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

        return $cursor;
    }

    protected function getNamespace($collection)
    {

        $namespace = $this->database . '.' . $collection;
        return $namespace;
    }

    public function selectDb($database)
    {
        $this->database = $database;
        return $this;
    }
}
