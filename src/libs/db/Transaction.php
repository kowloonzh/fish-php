<?php

namespace libs\db;

/**
 * Description of Transaction
 * 数据库事务类
 * @author zhangjiulong
 */
class Transaction extends \frame\base\Object
{

    /**
     * 数据库链接类
     * @var \libs\db\DB
     */
    public $db;

    /**
     * 事务的层级，用来控制多层事务嵌套
     * @var bool
     */
    private $_level = 0;

    public function getIsActive()
    {
        return $this->_level > 0 && $this->db && $this->db->getIsActive();
    }

    /**
     * 开启事务
     * @throws \frame\base\Exception
     */
    public function begin()
    {
        if ($this->db === null) {
            throw new \frame\base\Exception('Transaction::db must be set.');
        }

        $this->db->open();

        if ($this->_level == 0) {
            HELL:
            try {

                @$this->db->pdo->beginTransaction();

            } catch (\PDOException $e) {

                //如果自动可以自动重连
                if ($this->db->auto_reconnect && in_array($e->errorInfo[1], [2013, 2006])) {
                    $this->db->pdo = null;
                    $this->db->open();
                    goto HELL;
                } else {
                    throw $e;
                }
            }
        }

        $this->_level++;
    }

    /**
     * 提交事务
     * @throws \frame\base\Exception
     */
    public function commit()
    {
        if (!$this->getIsActive()) {
            throw new \frame\base\Exception('Fail to commint transaction: transaction was inactive.');
        }
        $this->_level--;

        if ($this->_level == 0) {
            $this->db->pdo->commit();
        }
    }

    /**
     * 事务回滚
     * @param \Exception|null $e // 内层事务回滚时抛出的异常，如果不传值默认抛出内部事务错误
     * @throws \Exception
     * @throws \frame\base\Exception
     */
    public function rollBack(\Exception $e = null)
    {
        if (!$this->getIsActive()) {
            //do nothing
            return;
        }
        $this->_level--;

        if ($this->_level == 0) {
            $this->db->pdo->rollBack();
            //如果外层事务传递了$e,则抛出外部异常
            if ($e) {
                throw $e;
            } else {
                return;
            }
        }
        if ($e) {
            throw $e;
        } else {
            throw new \frame\base\Exception('The inner transaction error!');
        }
    }

}

