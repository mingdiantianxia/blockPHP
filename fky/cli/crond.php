<?php
namespace fky\cli;

// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

require_once '..'.DIRECTORY_SEPARATOR.'FkyLoad.php';

//定义项目根目录
define('FKY_PROJECT_PATH',  __DIR__ .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

date_default_timezone_set('PRC');
loadc('db',loadc('config')->get("db", "config"));
loadc('loader')->run();
loadc('Router');
require FKY_PROJECT_PATH.'/router/router.php';
//启动定时任务服务
\fky\classs\Crond::getInstance()->start();