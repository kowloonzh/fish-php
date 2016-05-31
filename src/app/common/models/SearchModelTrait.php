<?php

namespace common\models;

/**
 * Description of SearchModelTrait
 * 搜索模型辅助类
 * @author JIU
 */
trait SearchModelTrait {

    public $page;
    public $pagesize;

    //设置query的偏移量
    public function applyLimit(\libs\db\Query $query, $defaultSize = 20)
    {
        $this->page     = $this->page? : 1;
        $this->pagesize = $this->pagesize? : $defaultSize;
        $query->page($this->page, $this->pagesize);
        return $query;
    }

}
