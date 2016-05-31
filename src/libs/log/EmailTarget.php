<?php

namespace libs\log;

/**
 * Description of EmailTarget
 * 发送邮件的日志
 * @author KowloonZh
 */
class EmailTarget extends LogTarget
{

    public $categories = ['email.*'];
    public $capacity   = 20;

    /**
     * 数组展示的深度
     * @var int 
     */
    public $depth = 3;

    /**
     * 将messages按category分组
     * @var array 
     */
    private $_messageGroup = [];

    /**
     * 邮件发送有效回调
     * @var callable
     */
    public $sender         = ['\libs\utils\MailUtil', 'send'];
    public $from;
    public $to;
    public $cc             = '';
    public $subject;

    public function export()
    {
        //将message分类 每个category发一封邮件
        $this->divideMessages();
        foreach ($this->_messageGroup as $category => $messages) {
            $content = implode(PHP_EOL . "<br /><br />", array_map([$this, 'formatMessage'], $messages));
            $subject = $this->subject . '-' . $category;
            $content = \libs\widgets\TextWidget::widget(['content' => $content]);
            $this->sendEmail($subject, $content);
        }
        $this->_messageGroup = [];
    }

    //将message按分类分组
    protected function divideMessages()
    {
        foreach ($this->messages as $message) {
            list($text, $level, $category, $time) = $message;
            //添加消息到组
            $this->_messageGroup[$category][] = $message;
        }
        //清空messages
        $this->messages = [];
    }

    //只取数组的depth层
    public function formatArr($arr, $depth)
    {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                if ($depth <= 1) {
                    $arr[$k] = '[..]';
                } else {
                    $arr[$k] = $this->formatArr($v, $depth - 1);
                }
            }
        }
        return $arr;
    }

    public function formatMessage($message)
    {
        list($text, $level, $category, $time) = $message;
        if (!is_array($text)) {
            $content['message'] = $text;
        } else {
            $content = $text;
        }
        $content['logtime'] = date('Y-m-d H:i:s', $time);
        $content['user_ip'] = $_SERVER['REMOTE_ADDR'];
        $this->formatArr($content, $this->depth);
        return \libs\widgets\KvWidget::widget(['arr' => $content, 'wrap' => false]);
    }

    public function sendEmail($subject, $content)
    {
        return call_user_func($this->sender, ['to' => $this->to, 'from' => $this->from, 'cc' => $this->cc, 'subject' => $subject, 'content' => $content]);
    }

}
