<?php
use fky\classs\Router;

Router::setPrefix('\/?');
Router::get('test/', 'controllers\test\TestController@test');
Router::any('docronworker\/?:any', 'controllers\test\TestController@test2');
Router::get('test2/', 'controllers\test\SyncDatabaseController@syncDatabase');
Router::get('vue/', 'controllers\vue\vueController@vue');
Router::get('home/', 'controllers\HomeController@home');

Router::get('vip:any', 'controllers\vip\VedioVipController@Vip');
Router::any('\/?', 'controllers\vip\VedioVipController@Vip');
Router::any('vip\/?', 'controllers\vip\VedioVipController@Vip');
Router::get('getvip\/?', 'controllers\vip\VedioVipController@Getvip');
Router::any('videolist\/?', 'controllers\vip\VedioVipController@GetVideoList');
Router::error(function(){
  echo '404:: ' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . ' Not Found！';
});
Router::dispatch();
