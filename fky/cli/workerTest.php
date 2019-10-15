<?php
namespace fky\cli;

// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

define('FKY_PROJECT_PATH',  __DIR__ .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

require_once FKY_PROJECT_PATH.'fky/FkyLoad.php';

date_default_timezone_set('PRC');
loadc('db',loadc('config')->get("db", "config"));
loadc('loader')->run();

//添加worker控制器目录(用于加载和调用)
$worker_path = loadc('config')->get("worker_path", "worker");
\fky\classs\LoadFactory::setClassDir(['dir' => $worker_path['path'], 'suffix' => $worker_path['suffix']]);

$options = getopt('t:');
if (!isset($options['t']) || empty($options['t'])) {
    echo "invalid params.";
    exit();
}

$jobName = $options['t'];
\fky\classs\worker\test\WorkerTest::getInstance($jobName)->run();