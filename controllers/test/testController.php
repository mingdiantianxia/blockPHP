<?php
namespace controllers\test;
use controllers\test2\Test2Controller;
use fky\traits\BaseTool;
/**
* \HomeController
*/
class TestController extends \controllers\BaseController
{
	use BaseTool;
  
  public function test()
  {
  	 $data = loadc('db')->select("wx_pintuan_setting", "*",['LIMIT'=>20]);
  	 // $this->showResponse(200,'',$data,'arr');

	echo loadc('template')->make('admin/hello', ['a' => 'blockPHP测试页', 'time' => time()])->render();
  }
}