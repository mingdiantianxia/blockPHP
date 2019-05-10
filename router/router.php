<?php 
loadc('Router')->setPrefix('\/');
loadc('Router')->get('test/', 'controllers\test\TestController@test');
loadc('Router')->get('test2/', 'controllers\test2\Test2Controller@test');
loadc('Router')->get('vue/', 'controllers\vue\vueController@vue');
loadc('Router')->get('home/', 'controllers\HomeController@home');

loadc('Router')->get('\/?', 'controllers\vip\VedioVipController@Vip');
loadc('Router')->get('vip\/?', 'controllers\vip\VedioVipController@Vip');
loadc('Router')->get('getvip\/?', 'controllers\vip\VedioVipController@Getvip');
loadc('Router')->any('videolist\/?', 'controllers\vip\VedioVipController@GetVideoList');
loadc('Router')->error(function(){
  echo '404::Not Found！';
});
loadc('Router')->dispatch();
