<?php

namespace libs\base;

/**
 * Description of Controller
 * 业务控制器基类
 * @author KowloonZh
 */
class Controller extends \frame\web\Controller
{

    use \libs\utils\ValidateTrait;

    /**
     * 返回的结果
     * @var array
     */
    protected $_msg = [
        'errno'  => 0,
        'errmsg' => '',
        'data'   => [
        ]
    ];

    public function init()
    {
        parent::init();
        //\Load::$app->getResponse()->addHeaders("node", posix_uname()['nodename']);
    }

    /**
     * 设置返回的_msg里面的data
     * @param $data
     * @return array
     */
    public function setData($data)
    {
        $this->_msg['data'] = $data;
        return $this->_msg;
    }

    protected function beforeAction()
    {
        $this->setScenario($this->actionId);
        $this->doFilterRequest();
        $this->validate();
        return parent::beforeAction();
    }

    public function getValidateValue($attribute)
    {
        return $this->request($attribute);
    }

    //过滤request请求的字段的值
    protected function doFilterRequest()
    {
        foreach ($this->filters() as $filter) {
            //[0] 待过滤的参数 [1] 过滤回调函数 [2]-参数
            if (isset($filter[0], $filter[1])) {
                //获取验证方法
                if (!is_callable($filter[1])) {
                    throw new \frame\base\Exception('The filter callback must be callable.');
                }
                //获取待验证的参数
                $attributes = $filter[0];
                if (is_string($attributes)) {
                    $attributes = preg_split('/[\s,]+/', $attributes, -1, PREG_SPLIT_NO_EMPTY);
                }

                //获取其他的参数
                $params = array_slice($filter, 2);

                //参数解析出验证的场景和条件
                list($on, $except, $when) = $this->resolveValidateParams($params);
                //根据条件确定是否需要验证
                $flag = $this->_isNeedValidate($on, $except, $when);

                if (!$flag) {
                    continue;
                }
                foreach ($attributes as $attribute) {
                    $request = $this->request();
                    if (isset($request[$attribute])) {
                        //设置过滤之后的值
                        $this->setRequestValue($attribute, call_user_func($filter[1], $request[$attribute]));
                    }
                }
            } else {
                throw new \frame\base\Exception('Invalid filter,attributes and callback is required.');
            }
        }
    }

    /**
     * 过滤器,过滤request的字段
     * @return array
     */
    public function filters()
    {
        return [];
    }

}
