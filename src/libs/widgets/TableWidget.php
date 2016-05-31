<?php

namespace libs\widgets;

/**
 * Description of TableWidget
 * 表格挂件
 * @author KowloonZh
 */
class TableWidget extends BaseWidget
{

    public $style       = 'font-size:12px;';
    public $width;
    public $border      = 1;
    public $cellspacing = 0;
    public $cellpadding = 5;
    public $tdColor     = '#C41A16';
    public $headers     = [];
    public $contents    = [];

    public function run()
    {
        return $this->render('table_widget');
    }

}
