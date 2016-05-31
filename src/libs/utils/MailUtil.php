<?php

namespace libs\utils;

/**
 * Description of MailUtil
 * 邮件发送工具类
 * @author KowloonZh
 */
class MailUtil
{

    const DEFAULT_DOMAIN = '@zjl.cn';
    const ALARM_DOMAIN   = '@alarm.zjl.cn';

    /**
     * 发送邮件
     * @param array $mailInfo ['from','to','subject','cc','content'] to,cc都支持数组
     * @return boolean
     */
    public static function send(array $mailInfo)
    {
        $tos     = self::formatMail($mailInfo['to']);
        $to      = implode(',', (array) $tos);
        $from    = self::formatMail($mailInfo['from']);
        $headers = "From: $from\r\n" . "Reply-To: $from\r\n";
        if (isset($mailInfo['cc'])) {
            $ccs = self::formatMail($mailInfo['cc']);
            $cc  = implode(',', (array) $ccs);
            $headers .= 'CC: ' . $cc . "\r\n";
        }
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        $subject = '=?UTF-8?B?' . base64_encode($mailInfo['subject']) . '?=';
        return mail($to, $subject, $mailInfo['content'], $headers);
    }

    /**
     * 调用qms接口发送邮件
     * @param array $mailInfo
     * array(
     *      'to'=>$to,//string|array
     *      'from'=>$from,//string
     *      'subject'=>$subject,//string
     *      'content'=>$content,//string
     *      'cc'=>$cc,//string 可选
     * )
     * @return json|boolean
     */
    public static function qmsSend(array $mailInfo)
    {
        $to   = self::formatMail($mailInfo['to']);
        $from = self::formatMail($mailInfo['from'], self::ALARM_DOMAIN);
        $cc   = self::formatMail($mailInfo['cc']);

        $data         = array();
        $mail         = array(
            'batch'   => true,
            'subject' => $mailInfo['subject'],
            'from'    => $from,
            'to'      => $to,
            'cc'      => $cc,
            'body'    => $mailInfo['content'],
            'format'  => 'html'
        );
        $data['mail'] = json_encode($mail);
        $url          = 'http://qms.addops.soft.zjl.cn:8zjl/interface/deliver.php';
        $ch           = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response     = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * 格式化邮件，将逗号分隔的字符串转换成数组，并将没有@ 邮箱域名的加上邮箱域名
     * @param string|array $param 收件人|抄送人|发送人邮箱信息
     * @param string $domain 如果收发件人邮箱信息中没有域名，需要补充的域名信息
     * @return string|array 返回格式化之后的邮箱数组或者字符串
     */
    static public function formatMail($param, $domain = self::DEFAULT_DOMAIN)
    {
        $params = array();
        if (empty($param)) {
            return $params;
        } elseif (is_string($param)) {
            $params = self::addMailDomain(explode(',', $param), $domain);
        } elseif (is_array($param)) {
            $params = self::addMailDomain($param, $domain);
        }
        //如果只有一个，就返回字串
        if (count($params) == 1) {
            return $params[0];
        }
        return $params;
    }

    //添加邮件域名
    static public function addMailDomain($params, $domain = self::DEFAULT_DOMAIN)
    {
        foreach ($params as $k => $v) {
            if (!preg_match('/@/', $v)) {
                $params[$k] = $v . $domain;
            }
        }
        return $params;
    }
}
