<?php

namespace controllers\worker;

use controllers\BaseController;
use fky\classs\LoadFactory;
use fky\classs\Db;

/**
 * 数据库worker控制器
 */
class DbWorkerController extends BaseController
{
    public function synchronizeDbData($data)
    {
        echo '开始插入数据';
        if(empty($data) || !isset($data[0]['TableName']) || !isset($data[0]['Schema']))
        {
            return true;
        }

        //处理数据
        foreach ($data as $key => $value)
        {
            if(empty($value['Data']))
            {
                continue;
            }

            $tableDatas[] = $value['Data'];

        }
        var_export($tableDatas);

        $dbInstance = Db::getInstance('slaveDb');
        echo $data[0]['TableName'];
        $result = $dbInstance->insert($data[0]['TableName'], $tableDatas);
        var_dump($result);

//        //事务执行
//        $dbInstance->action(function($database) use ($data, $tableDatas) {
//            try{
//                $database->insert($data[0]['TableName'], $tableDatas);
//            }catch (\Exception $e) {
//                var_export($e->getMessage());
//                return false;
//            }
//        });


        return true;
    }
}
