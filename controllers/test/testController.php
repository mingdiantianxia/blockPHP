<?php
namespace controllers\test;
use controllers\test2\Test2Controller;
/**
* \HomeController
*/
class TestController extends \controllers\BaseController
{
  
  public function test()
  {
  	$test2 = new Test2Controller;
  	$test2->test();
	// $data=loadc('db')->select("account", "hash", ["uniacid" => 18]);
	echo loadc('template')->make('admin/hello', ['a' => 'blockPHP测试页', 'time' => time()])->render();
  }
}