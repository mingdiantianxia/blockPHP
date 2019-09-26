<?php
namespace controllers\test;

use controllers\BaseController;
use fky\classs\Config;
use fky\classs\Db;
use fky\classs\LoadFactory;
use fky\classs\Phpredis;
/**
* \SyncDatabaseController
*/
class SyncDatabaseController extends BaseController
{
	//同步数据库到从库
    public function syncDatabase()
    {
        set_time_limit(0);

        //需要同步的数据库数组
        $syncDatabaseArr = ['agent', 'jinxiaocun', 'lsjinxiaocun', 'lssystem', 'platform', 'weixin'];
        //每个数据表最大条数
        $limit = 500;

        $dbInstance = LoadFactory::lc('db', Config::getInstance()->get('db', 'config'));

        $databases = $dbInstance->pdo->query('show databases', \PDO::FETCH_ASSOC)->fetchAll();

        //记录已完成的数据表
        $redis = Phpredis::getInstance();
        $redis_key = 'dbSyncFinishTables';
        $redis_databasekey = 'dbSyncFinishDatabase_';

        foreach ($databases as $key => $database) {
            if (in_array($database['Database'], $syncDatabaseArr)) {

                $has = $redis->get($redis_databasekey . $database['Database']);
                if ($has) {
                    continue;
                }

                var_dump($database['Database']) ;
                $dbInstance->pdo->query('use ' . $database['Database']);
                $tables = $dbInstance->pdo->query('show tables', \PDO::FETCH_ASSOC)->fetchAll();

                foreach ($tables as $key => $table) {
                    $tableName = $table['Tables_in_' . $database['Database']];

                    $exist = $redis->hGet($redis_key, $database['Database'] . '_' . $tableName);
                    if ($exist) {
                        continue;
                    }

                    //创建数据库
                    $slaveDbConfig = Config::getInstance()->get('slaveDb', 'config');
                    $conn = mysqli_connect($slaveDbConfig['server'], $slaveDbConfig['username'], $slaveDbConfig['password']);
                    mysqli_set_charset($conn, 'utf8_general_ci');
                    mysqli_query($conn, 'CREATE DATABASE IF NOT EXISTS `'.$database['Database'].'` Character Set UTF8 collate utf8_general_ci');

                    //创建数据表
                    $createTableSql = $dbInstance->pdo->query('SHOW CREATE TABLE ' . $tableName, \PDO::FETCH_ASSOC)->fetch();
                    $createTableSql = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS ', $createTableSql['Create Table']);
                    $createTableSql = str_replace('"', '`', $createTableSql);

                    //去除不正确的时间默认值
                    $createTableSql = preg_replace(["/datetime NOT NULL DEFAULT '[-0-9]*\s?[:0-9]*'/Us", "/datetime DEFAULT '[-0-9]*\s?[:0-9]*'/Us"], "datetime DEFAULT NULL", $createTableSql);
                    $createTableSql = preg_replace(["/timestamp NOT NULL DEFAULT '[-0-9]*\s?[:0-9]*'/Us", "/timestamp DEFAULT '[-0-9]*\s?[:0-9]*'/Us"], "timestamp DEFAULT NULL", $createTableSql);
                    $createTableSql = preg_replace(["/date NOT NULL DEFAULT '[-0-9]*\s?[:0-9]*'/Us", "/date DEFAULT '[-0-9]*\s?[:0-9]*'/Us"], "date DEFAULT NULL", $createTableSql);

                    mysqli_select_db($conn, $database['Database']);
                    mysqli_query($conn, "SET NAMES utf8");//设置字符集，防止插入数据时中文乱码
                    $createResult = mysqli_query($conn, $createTableSql);
                    if (!$createResult) {
                        var_export($createTableSql);
//                        preg_match("/datetime NOT NULL DEFAULT '[-0-9]*\s?[:0-9]*'/Us",$createTableSql,$matches1);
//                        var_dump($matches1);die;

                        var_dump(mysqli_error_list($conn));
                        die('错误！');
                    }
                    mysqli_close($conn);

                    $sql = "select * from {$tableName} limit {$limit}";
                    $datas = $dbInstance->pdo->query($sql, \PDO::FETCH_ASSOC)->fetchAll();
                    if ($datas) {
                        $dbInstance2 = Db::getInstance('slaveDb');

                        $dbInstance2->pdo->query('use ' . $database['Database']);
//                        $dbInstance2->pdo->query('use old_weixin2');
                        $result = $dbInstance2->insert($tableName, $datas);
                        if ($result) {
                            $redis->hSet($redis_key, $database['Database'] . '_' . $tableName, 1, 86400);
                            var_dump($tableName);
                        }
                    }

                }

                $redis->set($redis_databasekey . $database['Database'], 1, 86400);
                //一次执行完，1秒刷新页面
                echo '<meta http-equiv="refresh" content="1">';
                break;

            }
        }

        echo 'end';
        return true;
    }
}