<?php

namespace libs\widgets;

/**
 * Description of KvWidget
 * 根据键值对来生成内容
 * @author KowloonZh
 */
class KvWidget extends TextWidget
{

    /**
     * key=>value数组
     * @var array
     */
    public $arr;

    /**
     * 需要加颜色的key列表
     * @var array 
     */
    public $colors;

    /**
     * 是否在外面包裹
     * @var boolean
     */
    public $wrap   = true;
    public $border = 0;
    public $width = 600;

    public function run()
    {
        $this->content = $this->render('kv_widget', ['arr' => $this->arr]);
        if ($this->wrap) {
            return parent::run();
//            return TextWidget::widget(['content' => $content]);
        } else {
            return $this->content;
        }
    }

    public function getKeyStyle($color = true)
    {
        if ($color) {
            return 'width: 20%;padding:8px;color:#d71345;word-wrap:break-word;word-break:break-all;border-bottom:1px solid #ddd;';
        } else {
            return 'width: 20%;padding:8px;color:#000;word-wrap:break-word;word-break:break-all;border-bottom:1px solid #ddd;';
        }
    }

    public function getValStyle($color = true)
    {
        if ($color) {
            return 'padding:8px;color:#d71345;border-bottom:1px solid #ddd;';
        } else {
            return 'padding:8px;color:#000;border-bottom:1px solid #ddd;';
        }
    }

}
