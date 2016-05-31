<?php

namespace libs\db;

/**
 * Description of DbExpression
 * db的sql表达式
 * @author KowloonZh
 */
class DbExpression
{
    public $expression;
    public $params = [];
    
    public function __construct($expression,$params=[]) {
        $this->expression = $expression;
        $this->params = $params;
    }
    
    public function __toString() {
        return $this->expression;
    }
}
