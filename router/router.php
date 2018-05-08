<?php 
loadc('Router')->setPrefix('\/');
loadc('Router')->get('test/', 'controllers\test\TestController@test');
loadc('Router')->get('vue/', 'controllers\vue\vueController@vue');
loadc('Router')->get('home/', 'controllers\HomeController@home');
loadc('Router')->error(function(){
  echo '404::Not Found！';
});
loadc('Router')->dispatch();
 ?>