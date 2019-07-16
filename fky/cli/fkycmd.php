<?php
namespace fky\cli;

// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

//定义项目根目录
define('FKY_PROJECT_PATH',  __DIR__ .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

require_once FKY_PROJECT_PATH.'fky/FkyLoad.php';

date_default_timezone_set('PRC');
loadc('db',loadc('config')->get("db", "config"));
loadc('loader')->run();

$cmd_config = loadc('config')->get("cmd_path", "config");
$result = loadf('cliRun', [$argc, $argv], $cmd_config['path'], $cmd_config['namespace']);
if ($result['code'] == -1) {
	//搜集没有返回true的任务日志
	loadc('log')->info('crond_error:'.$result['msg'], $argv);
	return false;
} else {
    return true;
}