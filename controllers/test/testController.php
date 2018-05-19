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
	echo loadc('template')->make('admin/hello', ['a' => 'blockPHP测试页', 'time' => time()])->render();
  }
}