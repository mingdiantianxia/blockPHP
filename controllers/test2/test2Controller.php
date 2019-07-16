<?php
namespace controllers\test2;
use controllers\BaseController;
/**
* \HomeController
*/
class Test2Controller extends BaseController
{
  
  public function test()
  {
  	echo '自动加载成功'.date('Y-m-d H:i:s')."\n";
  	// die('test2');
	// $data=loadc('db')->select("account", "hash", ["uniacid" => 18]);
	// echo loadc('template')->make('admin/hello', ['a' => 'Messy_MVC测试页', 'time' => time(),'data'=>$data])->render();
  }
}