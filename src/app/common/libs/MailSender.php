<?php

namespace common\libs;

/**
 * Description of MailSender
 * 邮件发送类
 * @author JIU
 */
class MailSender extends \frame\base\God implements \libs\utils\MailInterface
{

    public $sendFunc = "send";

    /**
     * @return MailSender
     */
    public static function di($id = 'mail', $throwException = true)
    {
        return parent::di($id, $throwException);
    }

    public function send($from, $to, $content, $subject, $cc)
    {
        return call_user_func([__NAMESPACE__ . '\MailUtil', $this->sendFunc], [
            'from'    => $from,
            'to'      => $to,
            'cc'      => $cc,
            'content' => $content,
            'subject' => $subject
        ]);
    }

}
