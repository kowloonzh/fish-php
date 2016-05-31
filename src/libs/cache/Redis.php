<?php

namespace libs\cache;

/**
 * Description of Redis
 * See https://github.com/yiisoft/yii2-redis/blob/master/Connection.php
 * @author KowloonZh
 */
class Redis extends \frame\base\Object
{
    /**
     * @var string 主机名
     */
    public $hostname = 'localhost';

    /**
     * @var int 端口
     */
    public $port = 6379;

    /**
     * @var string 密码
     */
    public $password;

    /**
     * @var int redis的数据库，从0开始
     */
    public $database = 0;

    /**
     *  @var string unix socket 所在绝对路径 (e.g. `/var/run/redis/redis.sock`) 
     */
    public $unix_socket;

    /**
     * @var int 连接redis的超时时间，默认为 ini_get("default_socket_timeout")
     */
    public $connect_timeout;

    /**
     * @var int 读写数据的超时时间 单位s
     */
    public $data_timeout;

    /**
     * @var array 系统redis命令列表 http://redis.io/commands
     */
    public $redisCommands = [
        'BRPOP', // key [key ...] timeout Remove and get the last element in a list, or block until one is available
        'BRPOPLPUSH', // source destination timeout Pop a value from a list, push it to another list and return it; or block until one is available
        'CLIENT KILL', // ip:port Kill the connection of a client
        'CLIENT LIST', // Get the list of client connections
        'CLIENT GETNAME', // Get the current connection name
        'CLIENT SETNAME', // connection-name Set the current connection name
        'CONFIG GET', // parameter Get the value of a configuration parameter
        'CONFIG SET', // parameter value Set a configuration parameter to the given value
        'CONFIG RESETSTAT', // Reset the stats returned by INFO
        'DBSIZE', // Return the number of keys in the selected database
        'DEBUG OBJECT', // key Get debugging information about a key
        'DEBUG SEGFAULT', // Make the server crash
        'DECR', // key Decrement the integer value of a key by one
        'DECRBY', // key decrement Decrement the integer value of a key by the given number
        'DEL', // key [key ...] Delete a key
        'DISCARD', // Discard all commands issued after MULTI
        'DUMP', // key Return a serialized version of the value stored at the specified key.
        'ECHO', // message Echo the given string
        'EVAL', // script numkeys key [key ...] arg [arg ...] Execute a Lua script server side
        'EVALSHA', // sha1 numkeys key [key ...] arg [arg ...] Execute a Lua script server side
        'EXEC', // Execute all commands issued after MULTI
        'EXISTS', // key Determine if a key exists
        'EXPIRE', // key seconds Set a key's time to live in seconds
        'EXPIREAT', // key timestamp Set the expiration for a key as a UNIX timestamp
        'FLUSHALL', // Remove all keys from all databases
        'FLUSHDB', // Remove all keys from the current database
        'GET', // key Get the value of a key
        'GETBIT', // key offset Returns the bit value at offset in the string value stored at key
        'GETRANGE', // key start end Get a substring of the string stored at a key
        'GETSET', // key value Set the string value of a key and return its old value
        'HDEL', // key field [field ...] Delete one or more hash fields
        'HEXISTS', // key field Determine if a hash field exists
        'HGET', // key field Get the value of a hash field
        'HGETALL', // key Get all the fields and values in a hash
        'HINCRBY', // key field increment Increment the integer value of a hash field by the given number
        'HINCRBYFLOAT', // key field increment Increment the float value of a hash field by the given amount
        'HKEYS', // key Get all the fields in a hash
        'HLEN', // key Get the number of fields in a hash
        'HMGET', // key field [field ...] Get the values of all the given hash fields
        'HMSET', // key field value [field value ...] Set multiple hash fields to multiple values
        'HSET', // key field value Set the string value of a hash field
        'HSETNX', // key field value Set the value of a hash field, only if the field does not exist
        'HVALS', // key Get all the values in a hash
        'INCR', // key Increment the integer value of a key by one
        'INCRBY', // key increment Increment the integer value of a key by the given amount
        'INCRBYFLOAT', // key increment Increment the float value of a key by the given amount
        'INFO', // [section] Get information and statistics about the server
        'KEYS', // pattern Find all keys matching the given pattern
        'LASTSAVE', // Get the UNIX time stamp of the last successful save to disk
        'LINDEX', // key index Get an element from a list by its index
        'LINSERT', // key BEFORE|AFTER pivot value Insert an element before or after another element in a list
        'LLEN', // key Get the length of a list
        'LPOP', // key Remove and get the first element in a list
        'LPUSH', // key value [value ...] Prepend one or multiple values to a list
        'LPUSHX', // key value Prepend a value to a list, only if the list exists
        'LRANGE', // key start stop Get a range of elements from a list
        'LREM', // key count value Remove elements from a list
        'LSET', // key index value Set the value of an element in a list by its index
        'LTRIM', // key start stop Trim a list to the specified range
        'MGET', // key [key ...] Get the values of all the given keys
        'MIGRATE', // host port key destination-db timeout Atomically transfer a key from a Redis instance to another one.
        'MONITOR', // Listen for all requests received by the server in real time
        'MOVE', // key db Move a key to another database
        'MSET', // key value [key value ...] Set multiple keys to multiple values
        'MSETNX', // key value [key value ...] Set multiple keys to multiple values, only if none of the keys exist
        'MULTI', // Mark the start of a transaction block
        'OBJECT', // subcommand [arguments [arguments ...]] Inspect the internals of Redis objects
        'PERSIST', // key Remove the expiration from a key
        'PEXPIRE', // key milliseconds Set a key's time to live in milliseconds
        'PEXPIREAT', // key milliseconds-timestamp Set the expiration for a key as a UNIX timestamp specified in milliseconds
        'PING', // Ping the server
        'PSETEX', // key milliseconds value Set the value and expiration in milliseconds of a key
        'PSUBSCRIBE', // pattern [pattern ...] Listen for messages published to channels matching the given patterns
        'PTTL', // key Get the time to live for a key in milliseconds
        'PUBLISH', // channel message Post a message to a channel
        'PUNSUBSCRIBE', // [pattern [pattern ...]] Stop listening for messages posted to channels matching the given patterns
        'QUIT', // Close the connection
        'RANDOMKEY', // Return a random key from the keyspace
        'RENAME', // key newkey Rename a key
        'RENAMENX', // key newkey Rename a key, only if the new key does not exist
        'RESTORE', // key ttl serialized-value Create a key using the provided serialized value, previously obtained using DUMP.
        'RPOP', // key Remove and get the last element in a list
        'RPOPLPUSH', // source destination Remove the last element in a list, append it to another list and return it
        'RPUSH', // key value [value ...] Append one or multiple values to a list
        'RPUSHX', // key value Append a value to a list, only if the list exists
        'SADD', // key member [member ...] Add one or more members to a set
        'SAVE', // Synchronously save the dataset to disk
        'SCARD', // key Get the number of members in a set
        'SCRIPT EXISTS', // script [script ...] Check existence of scripts in the script cache.
        'SCRIPT FLUSH', // Remove all the scripts from the script cache.
        'SCRIPT KILL', // Kill the script currently in execution.
        'SCRIPT LOAD', // script Load the specified Lua script into the script cache.
        'SDIFF', // key [key ...] Subtract multiple sets
        'SDIFFSTORE', // destination key [key ...] Subtract multiple sets and store the resulting set in a key
        'SELECT', // index Change the selected database for the current connection
        'SET', // key value Set the string value of a key
        'SETBIT', // key offset value Sets or clears the bit at offset in the string value stored at key
        'SETEX', // key seconds value Set the value and expiration of a key
        'SETNX', // key value Set the value of a key, only if the key does not exist
        'SETRANGE', // key offset value Overwrite part of a string at key starting at the specified offset
        'SHUTDOWN', // [NOSAVE] [SAVE] Synchronously save the dataset to disk and then shut down the server
        'SINTER', // key [key ...] Intersect multiple sets
        'SINTERSTORE', // destination key [key ...] Intersect multiple sets and store the resulting set in a key
        'SISMEMBER', // key member Determine if a given value is a member of a set
        'SLAVEOF', // host port Make the server a slave of another instance, or promote it as master
        'SLOWLOG', // subcommand [argument] Manages the Redis slow queries log
        'SMEMBERS', // key Get all the members in a set
        'SMOVE', // source destination member Move a member from one set to another
        'SORT', // key [BY pattern] [LIMIT offset count] [GET pattern [GET pattern ...]] [ASC|DESC] [ALPHA] [STORE destination] Sort the elements in a list, set or sorted set
        'SPOP', // key Remove and return a random member from a set
        'SRANDMEMBER', // key [count] Get one or multiple random members from a set
        'SREM', // key member [member ...] Remove one or more members from a set
        'STRLEN', // key Get the length of the value stored in a key
        'SUBSCRIBE', // channel [channel ...] Listen for messages published to the given channels
        'SUNION', // key [key ...] Add multiple sets
        'SUNIONSTORE', // destination key [key ...] Add multiple sets and store the resulting set in a key
        'SYNC', // Internal command used for replication
        'TIME', // Return the current server time
        'TTL', // key Get the time to live for a key
        'TYPE', // key Determine the type stored at key
        'UNSUBSCRIBE', // [channel [channel ...]] Stop listening for messages posted to the given channels
        'UNWATCH', // Forget about all watched keys
        'WATCH', // key [key ...] Watch the given keys to determine execution of the MULTI/EXEC block
        'ZADD', // key score member [score member ...] Add one or more members to a sorted set, or update its score if it already exists
        'ZCARD', // key Get the number of members in a sorted set
        'ZCOUNT', // key min max Count the members in a sorted set with scores within the given values
        'ZINCRBY', // key increment member Increment the score of a member in a sorted set
        'ZINTERSTORE', // destination numkeys key [key ...] [WEIGHTS weight [weight ...]] [AGGREGATE SUM|MIN|MAX] Intersect multiple sorted sets and store the resulting sorted set in a new key
        'ZRANGE', // key start stop [WITHSCORES] Return a range of members in a sorted set, by index
        'ZRANGEBYSCORE', // key min max [WITHSCORES] [LIMIT offset count] Return a range of members in a sorted set, by score
        'ZRANK', // key member Determine the index of a member in a sorted set
        'ZREM', // key member [member ...] Remove one or more members from a sorted set
        'ZREMRANGEBYRANK', // key start stop Remove all members in a sorted set within the given indexes
        'ZREMRANGEBYSCORE', // key min max Remove all members in a sorted set within the given scores
        'ZREVRANGE', // key start stop [WITHSCORES] Return a range of members in a sorted set, by index, with scores ordered from high to low
        'ZREVRANGEBYSCORE', // key max min [WITHSCORES] [LIMIT offset count] Return a range of members in a sorted set, by score, with scores ordered from high to low
        'ZREVRANK', // key member Determine the index of a member in a sorted set, with scores ordered from high to low
        'ZSCORE', // key member Get the score associated with the given member in a sorted set
        'ZUNIONSTORE', // destination numkeys key [key ...] [WEIGHTS weight [weight ...]] [AGGREGATE SUM|MIN|MAX] Add multiple sorted sets and store the resulting sorted set in a new key
    ];

    /**
     * redis的socket连接资源
     */
    private $_socket;

    /**
     * 
     * @param string $id
     * @param boolean $throwException
     * @return \libs\cache\Redis
     */
    public static function di($id = 'redis', $throwException = true)
    {
        return parent::di($id, $throwException);
    }

    /**
     * @return boolean 返回当前的redis连接是否已经建立
     */
    public function getIsActive()
    {
        return $this->_socket !== null;
    }

    /**
     * 打开redis连接
     * @throws \frame\base\Exception 连接失败抛出异常
     */
    public function open()
    {
        if ($this->_socket !== null) {
            return;
        }
        $connection = ($this->unix_socket ? : $this->hostname . ':' . $this->port) . ', database=' . $this->database;

        //@todo log 
        \libs\log\Loger::info('Opening redis DB connection: ' . $connection, 'redis.connect');
        $this->_socket = @stream_socket_client(
                        $this->unix_socket ? 'unix://' . $this->unix_socket : 'tcp://' . $this->hostname . ':' . $this->port, $errorNumber, $errorDescription, $this->connect_timeout ? $this->connect_timeout : ini_get("default_socket_timeout")
        );
        if ($this->_socket) {
            if ($this->data_timeout !== null) {
                stream_set_timeout($this->_socket, $timeout = (int) $this->data_timeout, (int) (($this->data_timeout - $timeout) * 1000000));
            }
            if ($this->password !== null) {
                $this->executeCommand('AUTH', [$this->password]);
            }
            $this->executeCommand('SELECT', [$this->database]);
        } else {
            \libs\log\Loger::error("Failed to open redis DB connection ($connection): $errorNumber - $errorDescription", 'redis');
            throw new \frame\base\Exception('Failed to open DB connection.', (int) $errorNumber);
        }
    }

    /**
     * 关闭当前的redis连接
     */
    public function close()
    {
        if ($this->_socket !== null) {
            $connection    = ($this->unix_socket ? : $this->hostname . ':' . $this->port) . ', database=' . $this->database;
            //@todo log
            \libs\log\Loger::info('Closing DB connection: ' . $connection, 'redis.connect');
            $this->executeCommand('QUIT');
            stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
            $this->_socket = null;
        }
    }

    /**
     * 实现__call魔术方法，方便直接对命令的调用,比如
     * FrameRedist::di()->lpush('mylist','hello');===>相当于 FrameRedist::di()->executeCommand('LPUSH',['mylist','hello']);
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public function __call($name, $params)
    {
        $redisCommand = strtoupper($name);
        if (in_array($redisCommand, $this->redisCommands)) {
            return $this->executeCommand($name, $params);
        } else {
            throw new \frame\base\Exception('call to unknow redis command ' . $name);
        }
    }

    /**
     * 执行redis命令
     * 具体命令的参数请参考 http://redis.io/commands.
     *
     * @param string $name 命令的名字
     * @param array $params 命令的参数列表
     * @return array|bool|null|string 
     * 
     * 返回值有以下几种可能:
     *
     * - `true` 当命令返回的"status reply"为`'OK'` 或者 `'PONG'`的时候
     * - `string` 当命令返回的"status reply"没有`'OK'`信息的时候(since version 2.0.1).
     * - `string` 当命令返回"integer reply"的时候
     *   作为一个有符号的64位整数
     * - `string` or `null` 当命令返回"bulk reply"的时候
     * - `array` 当命令返回"Multi-bulk replies"的时候
     *
     * See [redis protocol description](http://redis.io/topics/protocol)
     * @throws \frame\base\Exception 当命令返回 [error reply]的时候(http://redis.io/topics/protocol#error-reply).
     */
    public function executeCommand($name, $params = [])
    {
        $this->open();

        array_unshift($params, $name);
        $command = '*' . count($params) . "\r\n";
        foreach ($params as $arg) {
            $command .= '$' . mb_strlen($arg, '8bit') . "\r\n" . $arg . "\r\n";
        }
        //@todo log
        \libs\log\Loger::info(['command' => $name, 'params' => $params], 'redis.execute');
        fwrite($this->_socket, $command);

        return $this->parseResponse(implode(' ', $params));
    }

    /**
     * @param string $command
     * @return mixed
     * @throws \frame\base\Exception on error
     */
    private function parseResponse($command)
    {
        if (($line = fgets($this->_socket)) === false) {
            throw new \frame\base\Exception("Failed to read from socket.\nRedis command was: " . $command);
        }
        \libs\log\Loger::debug($command . "\n" . $line, 'redis.response');
        $type = $line[0];
        $line = mb_substr($line, 1, -2, '8bit');
        switch ($type) {
            case '+': // Status reply
                if ($line === 'OK' || $line === 'PONG') {
                    return true;
                } else {
                    return $line;
                }
            case '-': // Error reply
                throw new \frame\base\Exception("Redis error: " . $line . "\nRedis command was: " . $command);
            case ':': // Integer reply
                // no cast to int as it is in the range of a signed 64 bit integer
                return $line;
            case '$': // Bulk replies
                if ($line == '-1') {
                    return null;
                }
                $length = $line + 2;
                $data   = '';
                while ($length > 0) {
                    if (($block = fread($this->_socket, $length)) === false) {
                        throw new \frame\base\Exception("Failed to read from socket.\nRedis command was: " . $command);
                    }
                    $data .= $block;
                    $length -= mb_strlen($block, '8bit');
                }

                return mb_substr($data, 0, -2, '8bit');
            case '*': // Multi-bulk replies
                $count = (int) $line;
                $data  = [];
                for ($i = 0; $i < $count; $i++) {
                    $data[] = $this->parseResponse($command);
                }

                return $data;
            default:
                throw new \frame\base\Exception('Received illegal data from redis: ' . $line . "\nRedis command was: " . $command);
        }
    }
}
