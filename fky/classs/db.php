<?php 
namespace fky\classs;
require __DIR__.'/../inc/db/Medoo.php';
use fky\classs\Config;

class Db extends \Medoo\Medoo
{
    /**
     * @var Db实例数组
     */
    private static $_instance = [];

	public function __construct($options = null){
	    if (!is_array($options) || !isset($options['server']) || !isset($options['username']) || empty($options['server']) || empty($options['username'])) {
	        return false;
        }
		parent::__construct($options);
	}

    /**
     * 获取db实例
     * @param string $connectName 数据库连接配置名
     * @return Db
     */
    public static function getInstance($connectName = 'db')
    {
        if (empty($connectName)) {
            return false;
        }

        if (!isset(self::$_instance[$connectName])) {
//            self::$_instance[$connectName] = new Db(loadc('config')->get($connectName, "config"));
            self::$_instance[$connectName] = new Db(Config::getInstance()->get($connectName, 'config'));
        }
        return self::$_instance[$connectName];
    }

    /**
     * 获取Sql
     * @param $table
     * @param $join
     * @param null $columns
     * @param null $where
     * @return mixed|null|string|string[]
     * @see Medoo::select()
     */
    public function getSql($table, $join, $columns = null, $where = null)
    {
        $map = [];
        return $this->generate($this->selectContext($table, $map, $join, $columns, $where), $map);
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