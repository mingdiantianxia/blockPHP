<?php
namespace controllers\test;
/**
* \HomeController
*/
class TestController extends \controllers\BaseController
{
  
  public function test()
  {
	// $data=loadc('db')->select("account", "hash", ["uniacid" => 18]);
	echo loadc('template')->make('admin/hello', ['a' => 'free_MVC测试页', 'time' => time()])->render();
  }
}