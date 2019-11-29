<?php
/**
 * Created by IntelliJ IDEA.
 * User: KowloonZh
 * Date: 17/9/27
 * Time: 下午2:42
 */

namespace libs\cache;


use frame\base\God;

/**
 * Class RDao
 * @package common\libs\RDao
 */
abstract class RDao extends God
{

    /**
     * @param array $config
     * @return self
     */
    public static function ins($config = array())
    {
        return parent::ins($config);
    }

    abstract public function keyName();

    /**
     * @return Redis
     * @throws \frame\base\Exception
     */
    public function getRedis()
    {
        return \Load::$app->get('redis');
    }

    public function del()
    {
        return $this->getRedis()->del($this->keyName());
    }

    public function exsits()
    {
        return $this->getRedis()->exists($this->keyName());
    }

    public function expire($second)
    {
        return $this->getRedis()->expire($this->keyName(), $second);
    }

    public function expireat($timestamp)
    {
        return $this->getRedis()->expireat($this->keyName(), $timestamp);
    }

    public function pexpire($milliseconds)
    {
        return $this->getRedis()->pexpire($this->keyName(), $milliseconds);
    }

    public function pexpireat($millisecondTimestamp)
    {
        return $this->getRedis()->pexpireat($this->keyName(), $millisecondTimestamp);
    }

    public function pttl()
    {
        return $this->getRedis()->pttl($this->keyName());
    }

    public function ttl()
    {
        return $this->getRedis()->ttl($this->keyName());
    }

    /**
     * eg. scan $cursor [match,$pattern,count,$count]
     * @param $cursor
     * @param array $options
     * @return mixed
     */
    public function scan($cursor, $options = [])
    {
        $params = array_merge([$cursor], $options);
        return call_user_func_array([$this->getRedis(), 'scan'], $params);
    }

    public function decr()
    {
        return $this->getRedis()->decr($this->keyName());
    }

    public function decrby($decrement)
    {
        return $this->getRedis()->decrby($this->keyName(), $decrement);
    }

    public function get()
    {
        return $this->getRedis()->get($this->keyName());
    }

    public function getbit($offset)
    {
        return $this->getRedis()->getbit($this->keyName(), $offset);
    }

    public function getrange($start, $end)
    {
        return $this->getRedis()->getrange($this->keyName(), $start, $end);
    }

    public function incr()
    {
        return $this->getRedis()->incr($this->keyName());
    }

    public function incrby($increment)
    {
        return $this->getRedis()->incrby($this->keyName(), $increment);
    }

    public function incrbyfloat($increment)
    {
        return $this->getRedis()->incrbyfloat($this->keyName(), $increment);
    }

    public function getset($value)
    {
        return $this->getRedis()->getset($this->keyName(), $value);
    }

    public function psetex($milliseconds, $value)
    {
        return $this->getRedis()->psetex($this->keyName(), $milliseconds, $value);
    }

    public function set($value, $options = [])
    {
        $params = array_merge([$this->keyName(), $value], $options);

        return call_user_func_array([$this->getRedis(), 'set'], $params);
    }

    public function setbit($offset, $value)
    {
        return $this->getRedis()->setbit($this->keyName(), $offset, $value);
    }

    public function setex($seconds, $value)
    {
        return $this->getRedis()->setex($this->keyName(), $seconds, $value);
    }

    public function setnx($value)
    {
        return $this->getRedis()->setnx($this->keyName(), $value);
    }

    public function setrange($offset, $value)
    {
        return $this->getRedis()->setrange($this->keyName(), $offset, $value);
    }

    public function strlen()
    {
        return $this->getRedis()->strlen($this->keyName());
    }

    public function blpop($timeout)
    {
        return $this->getRedis()->blpop($this->keyName(), $timeout);
    }

    public function brpop($timeout)
    {
        return $this->getRedis()->brpop($this->keyName(), $timeout);
    }

    public function lindex($index)
    {
        return $this->getRedis()->lindex($this->keyName(), $index);
    }

    public function linsert($where, $pivot, $value)
    {
        return $this->getRedis()->linsert($this->keyName(), $where, $pivot, $value);
    }

    public function llen()
    {
        return $this->getRedis()->llen($this->keyName());
    }

    public function lpop()
    {
        return $this->getRedis()->lpop($this->keyName());
    }

    /**
     * @param string|array $value
     * @return mixed
     */
    public function lpush($value)
    {
        if (is_array($value)) {
            $values = $value;
        } else {
            $values = [$value];
        }
        $params = array_merge([$this->keyName()], $values);

        return call_user_func_array([$this->getRedis(), 'lpush'], $params);
    }

    public function lpushx($value)
    {
        return $this->getRedis()->lpushx($this->keyName(), $value);
    }

    public function lrange($start, $stop)
    {
        return $this->getRedis()->lrange($this->keyName(), $start, $stop);
    }

    public function lrem($count, $value)
    {
        return $this->getRedis()->lrem($this->keyName(), $count, $value);
    }

    public function lset($index, $value)
    {
        return $this->getRedis()->lset($this->keyName(), $index, $value);
    }

    public function ltrim($start, $stop)
    {
        return $this->getRedis()->ltrim($this->keyName(), $start, $stop);
    }

    public function rpop()
    {
        return $this->getRedis()->rpop($this->keyName());
    }

    public function rpush($value)
    {
        return $this->getRedis()->rpush($this->keyName(), $value);
    }

    public function rpushx($value)
    {
        return $this->getRedis()->rpushx($this->keyName(), $value);
    }

    /**
     * 删除一个或者多个field
     * @param string|array $field
     * @return mixed
     */
    public function hdel($field)
    {
        if (is_array($field)) {
            $fields = $field;
        } else {
            $fields = [$field];
        }
        $params = array_merge([$this->keyName()], $fields);

        return call_user_func_array([$this->getRedis(), 'hdel'], $params);
    }

    public function hexists($field)
    {
        return $this->getRedis()->hexists($this->keyName(), $field);
    }

    public function hget($field)
    {
        return $this->getRedis()->hget($this->keyName(), $field);
    }

    public function hgetall()
    {
        return $this->getRedis()->hgetall($this->keyName());
    }

    public function hincrby($field, $increment)
    {
        return $this->getRedis()->hincrby($this->keyName(), $field, $increment);
    }

    public function hincrbyfloat($field, $increment)
    {
        return $this->getRedis()->hincrbyfloat($this->keyName(), $field, $increment);
    }

    public function hkeys()
    {
        return $this->getRedis()->hkeys($this->keyName());
    }

    public function hlen()
    {
        return $this->getRedis()->hlen($this->keyName());
    }

    public function hmget(array $fields)
    {
        $params = array_merge([$this->keyName()], $fields);

        return call_user_func_array([$this->getRedis(), 'hmget'], $params);
    }

    /**
     * @param array $fieldValues // [$field1=>$value1,$field2=>$value2]
     * @return mixed
     */
    public function hmset(array $fieldValues)
    {
        $params = [$this->keyName()];

        foreach ($fieldValues as $field => $value) {

            $params[] = $field;
            $params[] = $value;
        }

        return call_user_func_array([$this->getRedis(), 'hmset'], $params);
    }

    public function hset($field, $value)
    {
        return $this->getRedis()->hset($this->keyName(), $field, $value);
    }

    public function hsetnx($field, $value)
    {
        return $this->getRedis()->hsetnx($this->keyName(), $field, $value);
    }

    public function hstrlen($field)
    {
        return $this->getRedis()->hstrlen($this->keyName(), $field);
    }

    public function hvals()
    {
        return $this->getRedis()->hvals($this->keyName());
    }

    /**
     * eg. hscan($cursor,[match,$pattern,count,$count])
     * @param $cursor
     * @param array $options
     * @return mixed
     */
    public function hscan($cursor, $options = [])
    {
        $params = array_merge([$this->keyName(), $cursor], $options);

        return call_user_func_array([$this->getRedis(), 'hscan'], $params);
    }

    /**
     * @param string|array $member
     * @return mixed
     */
    public function sadd($member)
    {
        if (is_array($member)) {
            $members = $member;
        } else {
            $members = [$member];
        }
        $params = array_merge([$this->keyName()], $members);

        return call_user_func_array([$this->getRedis(), 'sadd'], $params);
    }

    public function scard()
    {
        return $this->getRedis()->scard($this->keyName());
    }

    public function sdiff(array $keys)
    {
        $params = array_merge([$this->keyName()], $keys);

        return call_user_func_array([$this->getRedis(), 'sdiff'], $params);
    }

    public function sinter(array $keys)
    {
        $params = array_merge([$this->keyName()], $keys);

        return call_user_func_array([$this->getRedis(), 'sinter'], $params);
    }

    public function sismember($member)
    {
        return $this->getRedis()->sismember($this->keyName(), $member);
    }

    public function smembers()
    {
        return $this->getRedis()->smembers($this->keyName());
    }

    public function spop()
    {
        return $this->getRedis()->spop($this->keyName());
    }

    public function srandmember()
    {
        return $this->getRedis()->srandmember($this->keyName());
    }

    public function srem($member, $members = [])
    {
        $params = array_merge([$this->keyName(), $member], $members);

        return call_user_func_array([$this->getRedis(), 'srem'], $params);
    }

    /**
     * eg.  sscan($cursor, [match,$pattern,count,$count])
     * @param $cursor
     * @param array $options
     * @return mixed
     */
    public function sscan($cursor, $options = [])
    {
        $params = array_merge([$this->keyName(), $cursor], $options);

        return call_user_func_array([$this->getRedis(), 'sscan'], $params);
    }

    /**
     * @param $score
     * @param $member
     * @param array $options // ['nx'=>true,'ch'=>true,'incr'=>true]
     * @return mixed
     */
    public function zadd($score, $member, $options = [])
    {
        $nx     = $options['nx'] ?: false;
        $xx     = $options['xx'] ?: false;
        $ch     = $options['ch'] ?: false;
        $incr   = $options['incr'] ?: false;
        $params = [];
        if ($nx) {
            $params[] = 'nx';
        }
        if ($xx) {
            $params[] = 'xx';
        }
        if ($ch) {
            $params[] = 'ch';
        }
        if ($incr) {
            $params[] = 'incr';
        }
        $params[] = $score;
        $params[] = $member;
        $params   = array_merge([$this->keyName()], $params);
        return call_user_func_array([$this->getRedis(), 'zadd'], $params);
    }

    public function zcard()
    {
        return $this->getRedis()->zcard($this->keyName());
    }

    public function zcount($min, $max)
    {
        return $this->getRedis()->zcount($this->keyName(), $min, $max);
    }

    public function zincrby($increment, $member)
    {
        return $this->getRedis()->zincrby($this->keyName(), $increment, $member);
    }

    public function zrange($start, $stop, $withScores = false)
    {
        if ($withScores) {
            return $this->getRedis()->zrange($this->keyName(), $start, $stop, 'WITHSCORES');
        } else {
            return $this->getRedis()->zrange($this->keyName(), $start, $stop);
        }
    }

    /**
     * @param $min
     * @param $max
     * @param array $options // ['withscores','limit',$offset,$count]
     * @return mixed
     */
    public function zrangebyscore($min, $max, $options = [])
    {
        $params = array_merge([$this->keyName(), $min, $max], $options);

        return call_user_func_array([$this->getRedis(), 'zrangebyscore'], $params);
    }

    public function zrank($member)
    {
        return $this->getRedis()->zrank($this->keyName(), $member);
    }

    public function zrem($member, $members = [])
    {
        $params = array_merge([$this->keyName(), $member], $members);

        return call_user_func_array([$this->getRedis(), 'zrem'], $params);
    }

    public function zremrangebyrank($start, $stop)
    {
        return $this->getRedis()->zremrangebyrank($this->keyName(), $start, $stop);
    }

    /**
     * @param $max
     * @param $min
     * @param array $options // ['withscores','limit',$offset,$count]
     * @return mixed
     */
    public function zremrangebyscore($max, $min, $options = [])
    {
        $params = array_merge([$this->keyName(), $max, $min], $options);

        return call_user_func_array([$this->getRedis(), 'zremrangebyscore'], $params);
    }

    public function zrevrank($member)
    {
        return $this->getRedis()->zrevrank($this->keyName(), $member);
    }

    public function zscore($member)
    {
        return $this->getRedis()->zscore($this->keyName(), $member);
    }

    /**
     * eg. zscan($cursor,['match',$pattern,'count',$count])
     * @param $cursor
     * @param array $options
     * @return mixed
     */
    public function zscan($cursor, $options = [])
    {
        $params = array_merge([$this->keyName(), $cursor], $options);

        return call_user_func_array([$this->getRedis(), 'zscan'], $params);
    }

    public function publish($message)
    {
        $channel = $this->keyName();

        return $this->getRedis()->publish($channel, $message);
    }
}
