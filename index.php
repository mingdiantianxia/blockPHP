<?php
require 'fky/FkyLoad.php';
// use fky\FkyLoad as FkyLoad;

//定义项目根目录
define('FKY_PROJECT_PATH', dirname(__FILE__));

date_default_timezone_set('PRC');
loadc('db',loadc('config')->get("db", "config"));
loadc('template');
loadc('Router');
loadc('loader')->run(FKY_PROJECT_PATH);
require 'router/router.php';