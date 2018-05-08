<?php
namespace controllers\vue;
/**
* \HomeController
*/
class VueController extends \controllers\BaseController
{
  
  public function vue()
  {
	$data=loadc('db')->select("account", "hash", ["uniacid" => 18]);
	echo loadc('template')->make('vue/vue', ['a' => 'Messy_MVC测试页', 'time' => time(),'data'=>$data])->render();
  }
}