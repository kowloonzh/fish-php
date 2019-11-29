<?php

namespace libs\utils;

use frame\base\Exception;

/**
 * Description of ValidateTrait
 * 验证辅助类
 * @author KowloonZh
 */
trait ValidateTrait
{

    private $_scenario = ''; //场景

    private $_errors = []; // 错误信息


    protected function addError($error)
    {
        $this->_errors[] = $error;
        return $this;
    }

    public function hasError()
    {
        return !empty($this->_errors);
    }

    public function getError()
    {
        return implode(', ', $this->_errors);
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * 设置验证场景
     * @param string $value
     * @return \libs\utils\ValidateTrait
     */

    public function setScenario($value)
    {
        $this->_scenario = $value;
        return $this;
    }

    /**
     * 获取场景
     * @return string
     */
    public function getScenario()
    {
        return $this->_scenario;
    }

    /**
     * 返回待验证的属性的值,trait使用者必须覆写此方法
     * @param string $attribute
     * @return mixed
     * @throws Exception
     */
    public function getValidateValue($attribute)
    {
        unset($attribute);
        throw new Exception('The ValidateTrait user must overwrite the method "getValidateValue"');
    }

    protected function beforeValidate()
    {
        return true;
    }

    protected function afterValidate()
    {

    }

    // 解析验证器的参数
    protected function resolveValidateParams($params)
    {
        if (isset($params['on'])) {
            if (is_array($params['on'])) {
                $on = $params['on'];
            } else {
                $on = preg_split('/[\s,]+/', $params['on'], -1, PREG_SPLIT_NO_EMPTY);
            }
        } else {
            $on = [];
        }
        if (isset($params['except'])) {
            if (is_array($params['except'])) {
                $except = $params['except'];
            } else {
                $except = preg_split('/[\s,]+/', $params['except'], -1, PREG_SPLIT_NO_EMPTY);
            }
        } else {
            $except = [];
        }
        if (isset($params['when'])) {
            $when = $params['when'];
        } else {
            $when = null;
        }
        return [$on, $except, $when];
    }

    /**
     * 根据场景和条件觉得是否要验证
     * @param array $on // 需要验证的场景
     * @param array $except // 不需要验证的场景
     * @param callable $when // 验证的条件
     * @return boolean // 返回真则需要验证
     */
    private function _isNeedValidate($on, $except, $when)
    {
        $scenario = $this->getScenario();
        if (!empty($scenario)) {
            // 如果当前的scenario 不在 应用的场景下 不做验证
            if (!in_array($scenario, $on) && !empty($on)) {
                return false;
            }
            // 如果当前的actionId 在 except场景里 则也不做验证
            if (!empty($except) && in_array($scenario, $except)) {
                return false;
            }
        }
        // 如果有when 并且执行when的回调为false 不做验证
        if ($when != null && !call_user_func($when)) {
            return false;
        }
        return true;
    }

    public function validate($throwExcept = true)
    {
        if ($this->beforeValidate()) {
            foreach ($this->rules() as $rule) {
                // [0] 待验证的参数 [1] 验证器 [2]-参数
                if (isset($rule[0], $rule[1])) {
                    // 获取验证方法
                    $validateMethod = $rule[1];

                    // 判断验证方法是否存在
                    if (!method_exists('\libs\utils\Validator', $validateMethod)) {
                        throw new Exception('Unknown rule name: ' . $rule[1]);
                    }

                    // 获取待验证的参数
                    $attributes = $rule[0];
                    if (is_string($attributes)) {
                        $attributes = preg_split('/[\s,]+/', $attributes, -1, PREG_SPLIT_NO_EMPTY);
                    }

                    // 获取其他的参数
                    $params = array_slice($rule, 2);

                    // 执行验证
                    $this->_doValidate($attributes, $validateMethod, $params, $throwExcept);
                } else {
                    throw new Exception('Invalid rule,attributes and rule name is required.');
                }

                // 如果
                if (!$throwExcept && $this->hasError()) {
                    return false;
                }
            }
            $this->afterValidate();

            return true;
        } else {
            return false;
        }
    }

    /**
     * 执行验证
     * @param array $attributes // 待验证的属性
     * @param string $validateMethod // 验证方法
     * @param array $params // 验证方法的参数
     * @param bool $throwExcept
     * @throws Exception
     * @throws \ReflectionException
     */
    private function _doValidate($attributes, $validateMethod, $params, $throwExcept = true)
    {
        // 参数解析出验证的场景和条件
        list($on, $except, $when) = $this->resolveValidateParams($params);

        // 根据条件确定是否需要验证
        $flag = $this->_isNeedValidate($on, $except, $when);

        if (!$flag) {
            return;
        }
        $method = new \ReflectionMethod('\libs\utils\Validator', $validateMethod);
        $args   = [];
        foreach ($method->getParameters() as $k => $param) {
            $name = $param->getName();
            if ($k == 0) {
                // 第一个是待验证的值
                $args[0] = null;
                continue;
            }
            if (array_key_exists($name, $params)) {
                $args[] = $params[$name];
                unset($params[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }

        // 缺失验证参数
        if (!empty($missing)) {
            throw new Exception('Missing the param:' . implode(' | ', $missing) . ' of the validate method:' . $validateMethod);
        }
        foreach ($attributes as $attribute) {
            $message = isset($params['message']) ? $params['message'] : Validator::$messages[$validateMethod];

            // 从url中获取attribute对应的值进行验证
            $validate_value = $value = $this->getValidateValue($attribute);

            if (isset($params['before']) && is_callable($params['before'])) {
                $validate_value = call_user_func($params['before'], $value);
            }
            $args[0] = $validate_value;
            $res     = call_user_func_array(['\libs\utils\Validator', $validateMethod], $args);

            // 如果验证失败 抛出异常
            if (!$res) {
                $val_str = ' ' . implode(', ', (array)$value) . ' ';
                $message = $this->getAttributeLabel($attribute) . ' ' . str_replace('{$value}', $val_str, $message);
                if ($throwExcept) {
                    throw new Exception('参数错误: ' . $message, 1);
                } else {
                    $this->addError($message);
                }
            }
        }
    }

    /**
     * 标签名数组
     * @return array
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * 验证规则
     * @return array
     */
    public function rules()
    {
        return [];
    }

    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();
        if (isset($labels[$attribute])) {
            return $labels[$attribute];
        } else {
            return $attribute;
        }
    }
}
