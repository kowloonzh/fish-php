<?php

namespace libs\log;

use frame\base\Exception;
use Load;

/**
 * Description of FileTarget
 * 日志输出到文件
 * @author KowloonZh
 */
class FileTarget extends LogTarget
{

    /**
     * 日志文件日期后缀，默认为年月日，此参数会做为date($dateFormat)的参数生成日期
     * @var string
     */
    public $dateFormat = 'Ymd';

    /**
     * 文件模式 (eg:0755)
     * @var int 八进制
     */
    public $filemode;

    /**
     * 将messages按category分组
     * @var array
     */
    private $_messageGroup = [];    //

    public function export()
    {
        // 将message按category分组
        foreach ($this->messages as $i => $message) {
            $this->addMessageToGroup($message);
        }
        // 每个category创建一个文件
        foreach ($this->_messageGroup as $category => $messages) {

            $logfile = $this->getLogFile($category);
            $text    = implode("\n\n", array_map([$this, 'formatMessage'], $messages)) . "\n\n";

            if (($fp = @fopen($logfile, 'a')) === false) {
                throw new Exception('Unable to append logfile:' . $logfile);
            }
            @flock($fp, LOCK_EX);
            @fwrite($fp, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
            if ($this->filemode !== null) {
                @chmod($logfile, $this->filemode);
            }
        }
        $this->_messageGroup = [];
    }

    public function addMessageToGroup($message)
    {
        $category = $message[2];

        $this->_messageGroup[$category][] = $message;
    }

    public function getLogFile($filename)
    {
        $file = Load::$app->getLogPath() . '/' . $filename . '.log';
        if (!empty($this->dateFormat)) {
            $file .= '.' . date($this->dateFormat);
        }
        return $file;
    }

    /**
     * 覆写父类方法,去掉一些信息,节省一点空间
     * @param $message
     * @return string
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $time) = $message;

        unset($level, $category);

        if (!is_string($text)) {
            $text = var_export($text, true);
        }
        return date('Y-m-d H:i:s', $time) . " " . $text;
    }

}
