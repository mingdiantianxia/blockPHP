<?php
// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

define('FKY_PROJECT_PATH',  __DIR__ .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

require_once FKY_PROJECT_PATH.'fky/FkyLoad.php';

//date_default_timezone_set('PRC');
//loadc('db',loadc('config')->get("db", "config"));
loadc('loader')->run();

$options = getopt('t:');
if (!isset($options['t']) || empty($options['t'])) {
    echo "invalid params.";
    exit();
}

$listenerId = $options['t'];
\fky\classs\worker\DbEventListener::getInstance($listenerId)->run();