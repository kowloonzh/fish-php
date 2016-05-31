<?php

namespace libs\widgets;

/**
 * Description of Widget
 * 挂件基类
 * @author KowloonZh
 */
class Widget extends \frame\base\Object
{
    /**
     * 视图文件的后缀
     * @var string 
     */
    public $fileExtension = '.php';

    public static function widget($configs = []) {
        ob_start();
        ob_implicit_flush(false);
        $className = get_called_class();
        if ($className == __CLASS__) {
            throw new \frame\base\Exception('Widget不能直接调用');
        }
        $widget = new $className($configs);
        $out    = $widget->run();
        return ob_get_clean() . $out;
    }

    public function init() {
        
    }

    public function run() {
        
    }

    public function render($viewName, $params = array()) {
        $viewFile       = $this->getViewFile($viewName);
        $params['this'] = $this;
        return $this->renderInternal($viewFile, $params);
    }

    protected function renderInternal($_viewFile_, $_data_ = null) {
        ob_start();
        ob_implicit_flush(false);
        if (is_array($_data_)) {
            extract($_data_, EXTR_PREFIX_SAME, 'data');
        } else {
            $data = $_data_;
        }
        require($_viewFile_);
        return ob_get_clean();
    }

    public function getViewFile($viewName) {
        $viewFile = $this->getViewPath() . DIRECTORY_SEPARATOR . $viewName . $this->fileExtension;
        if (!is_file($viewFile)) {
            throw new \frame\base\Exception('找不到视图文件' . $viewFile);
        }
        return $viewFile;
    }

    public function getViewPath() {
        $class = new \ReflectionClass($this);
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'views';
    }
}
