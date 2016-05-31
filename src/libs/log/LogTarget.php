<?php

namespace libs\log;

use frame\base\Exception;
use frame\base\Object;

/**
 * Description of LogTarget
 * 日志输出地
 * @author KowloonZh
 */
abstract class LogTarget extends Object
{

    public $enabled    = true;
    public $messages   = [];
    public $capacity   = 1000;
    public $categories = [];
    public $except     = [];
    private $_levels   = 0;

    public function collect($messages, $final)
    {
        $this->messages = array_merge($this->messages, $this->filterMessages($messages, $this->getLevels(), $this->categories, $this->except));

        $count = count($this->messages);
        if ($count > 0 && ($final || $count >= $this->capacity)) {
            $this->export();
            $this->messages = [];
        }
    }

    public function setLevels($levels)
    {

        static $levelMap = [
            'error' => Loger::LEVEL_ERROR,
            'info'  => Loger::LEVEL_INFO,
            'debug' => Loger::LEVEL_DEBUG,
        ];
        if (is_array($levels)) {
            $this->_levels = 0;
            foreach ($levels as $level) {
                if (isset($levelMap[$level])) {
                    $this->_levels |= $levelMap[$level];
                } else {
                    throw new Exception('Unknown log level: ' . $level);
                }
            }
        } else {
            $this->_levels = $levels;
        }
    }

    public function getLevels()
    {
        return $this->_levels;
    }

    public function filterMessages($messages, $levels = 0, $categories = [], $except = [])
    {
        foreach ($messages as $i => $message) {
            if ($levels && !($levels & $message[1])) {
                unset($messages[$i]);
                continue;
            }
            $matched = empty($categories);
            foreach ($categories as $category) {
                if ($message[2] === $category || !empty($category) && substr_compare($category, '*', -1, 1) === 0 && strpos($message[2], rtrim($category, '*')) === 0) {
                    $matched = true;
                    break;
                }
            }
            if ($matched) {
                foreach ($except as $cate) {
                    $prefix = rtrim($cate, '*');
                    if (strpos($message[2], $prefix) === 0 && ($message[2] === $cate || $prefix !== $cate)) {
                        $matched = false;
                        break;
                    }
                }
            }
            if (!$matched) {
                unset($messages[$i]);
            }
        }
        return $messages;
    }

    public function formatMessage($message)
    {
        list($text, $level, $category, $time) = $message;
        $levelName = Loger::getLevelName($level);
        if (!is_string($text)) {
            $text = print_r($text, true);
        }
        return date('Y-m-d H:i:s', $time) . " [$levelName][$category]\n" . $text;
    }

    abstract public function export();
}
