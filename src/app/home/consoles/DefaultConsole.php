<?php

namespace home\consoles;

/**
 * Description of DefaultConsole
 *
 * @author KowloonZh
 */
class DefaultConsole extends \frame\console\Console
{
    
    public function testAction()
    {
        
    }
    
    public function genAction($module)
    {
        $modulePath = \Load::getAlias('@app/'.$module);
        if(!is_dir($modulePath)){
            //生成controllers，consoles,models,daos目录
            foreach (['controllers','consoles','models','daos'] as $dir) {
                mkdir($modulePath.'/'.$dir,0755,true);
            }
        }
        return 'done!';
    }
    
}
