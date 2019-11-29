<?php

namespace libs\utils;

/**
 * Description of UploadFile
 * 文件上传类
 * usage: libs\utils\UploadFile::getIns($filename);
 * @author KowloonZh
 */
class UploadFile extends \frame\base\God
{

    /**
     * 上传文件名 eg:test.sh
     */
    public $name;

    /**
     * 文件临时位置 eg:tmp/phpqG3H7h
     */
    public $tmpName;

    /**
     * 文件类型 eg:application/octet-stream"
     */
    public $type;

    /**
     * 文件大小 19字节 单位:bytes
     * @var int 
     */
    public $size;

    /**
     * 错误码
     * @var int 
     */
    public $error;

    /**
     * 解析之后的$_FILES
     * @var array|null
     */
    private static $_files;

    /**
     * @return \libs\utils\UploadFile
     */
    static public function getIns($name)
    {
        $files = self::loadFiles();
        return isset($files[$name]) ? $files[$name] : null;
    }

    /**
     * @return array \libs\utils\UploadFile
     */
    static public function getInses($name)
    {
        $files = self::loadFiles();
        if (isset($files[$name])) {
            return $files[$name];
        }
        $results = [];
        foreach ($files as $key => $file) {
            if (strpos($key, "{$name}[") === 0) {
                $results[] = $file;
            }
        }
        return $results;
    }

    //加载上传文件
    static private function loadFiles()
    {
        if (self::$_files === null) {
            self::$_files = [];
            \libs\log\Loger::info($_FILES, 'upload');
            if (isset($_FILES) && is_array($_FILES)) {
                foreach ($_FILES as $key => $info) {
                    self::loadFilesRecursive($key, $info['name'], $info['tmp_name'], $info['type'], $info['size'], $info['error']);
                }
            }
        }
        return self::$_files;
    }

    //递归处理上传文件
    static private function loadFilesRecursive($key, $names, $tmpNames, $types, $sizes, $errors)
    {
        if (is_array($names)) {
            foreach ($names as $i => $name) {
                self::loadFilesRecursive($key . '[' . $i . ']', $name, $tmpNames[$i], $types[$i], $sizes[$i], $errors[$i]);
            }
        } elseif ($errors !== UPLOAD_ERR_NO_FILE) {
            self::$_files[$key] = new static([
                'name'    => $names,
                'tmpName' => $tmpNames,
                'type'    => $types,
                'size'    => $sizes,
                'error'   => $errors
            ]);
        }
    }

    /**
     * 保存上传文件
     * @param string $file 上传后的目的路径
     * @param boolean $deleteTmpFile
     * @return boolean
     */
    public function save($file, $deleteTmpFile = true)
    {
        if ($this->error == UPLOAD_ERR_OK) {
            $dir = dirname($file);
            if(!is_dir($dir)){
                @mkdir($dir, 0777, true);
            }
            if ($deleteTmpFile) {
                return move_uploaded_file($this->tmpName, $file);
            } elseif (is_uploaded_file($this->tmpName)) {
                return copy($this->tmpName, $file);
            }
        }
        return false;
    }

    //返回是否有错
    public function hasError()
    {
        return $this->error != UPLOAD_ERR_OK;
    }

}
