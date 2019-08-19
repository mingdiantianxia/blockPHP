<?php
// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

define('FKY_PROJECT_PATH',  dirname(dirname(__DIR__)));

require_once FKY_PROJECT_PATH.'fky/FkyLoad.php';

//date_default_timezone_set('PRC');
//loadc('db',loadc('config')->get("db", "config"));
loadc('loader')->run();

//启动DbEventWorker
$srv = new \fky\classs\worker\DbEventServer();
$srv->start();