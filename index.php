<?php
require 'fky/load.fky.php';
require 'config/config.php';
date_default_timezone_set('PRC');
loadc('db',$config['db']);
loadc('template');
loadc('Router');
require 'router/router.php';