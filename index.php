<?php
require 'fky/FkyLoad.php';
// use fky\FkyLoad as FkyLoad;
require 'config/config.php';
date_default_timezone_set('PRC');
loadc('db',$config['db']);
loadc('template');
loadc('Router');
loadc('loader')->run();
$GPC = loadf('call:GPC');
var_dump($GPC('good'));die;

require 'router/router.php';