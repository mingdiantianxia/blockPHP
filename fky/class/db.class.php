<?php 
namespace fky;
require __DIR__.'/../inc/db/Medoo.php';

class Db extends \Medoo\Medoo{
	public function __construct($options = null){
		parent::__construct($options);
	}

}

 
// $database = loadc('db',[
//     'database_type' => 'mysql',
//     'database_name' => 'weitata',
//     'server' => 'localhost',
//     'username' => 'root',
//     'password' => 'root',
//     'charset' => 'utf8',
//      // 可选参数
//     'port' => 3306,
//     // 可选，定义表的前缀
//     'prefix' => 'ims_',
// ]);