<?php

namespace libs\widgets;

/**
 * Description of TextWidget
 * 文本挂件
 * @author KowloonZh
 */
class TextWidget extends \libs\widgets\Widget
{

    public $title = '';
    public $content; //实际输出的内容
    public $footer = '';
    public $width = 900;

    public function run()
    {
        return $this->render('text_widget');
    }
}
