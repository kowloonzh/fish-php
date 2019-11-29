<?php

namespace libs\base;

/**
 * Description of Model
 * 业务model基类
 * @author KowloonZh
 */
class Model extends \frame\base\God
{

    use \libs\utils\ValidateTrait;

    public function getValidateValue($attribute)
    {
        return $this->{$attribute};
    }

    //捕捉属性不存在的异常
    protected function __setException($name, $value)
    {
        $this->{$name} = $value;
    }

    //覆写父类，解决默认值覆盖的问题
    public static function configure($object, $properties = array())
    {
        if (!empty($properties) && is_array($properties)) {
            //根据对象获取对象反射类
            $reflect_obj = new \ReflectionObject($object);
            foreach ($properties as $name => $value) {
                //当属性待赋值为空，并且属性存在默认值时，保留默认值
                if (\libs\utils\Validator::isEmpty($value) && $reflect_obj->hasProperty($name)) {
                    $reflect_prop  = $reflect_obj->getProperty($name);
                    $default_value = $reflect_prop->getValue($object);
                    if ($default_value === null) {
                        $object->$name = $value;
                    }
                } else {
                    $object->$name = $value;
                }
            }
        }
        return $object;
    }

}
