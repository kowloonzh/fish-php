<?php

namespace libs\widgets;

/**
 * Description of TextWidget
 * 文本挂件
 * @author zhangjiulong
 */
class TextWidget extends \libs\widgets\Widget
{

    public $title = 'HULK 云平台';
    public $content; //实际输出的内容
    public $footer = '360 Hulk system';
    public $width = 900;

    public function run()
    {
        return $this->render('text_widget');
    }
}
