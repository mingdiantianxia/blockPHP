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
  	// $test2 = new Test2Controller();
  	// $test2->test();
  	loadc('log')->info('good',['good1','good2']);

  	  $data = loadc('db')->select("wx_pintuan_setting", "*",['LIMIT'=>20]);
  	 // $this->showResponse(200,'',$data,'arr');
      //  	 $response = loadc('HttpRequest')->GET('https://hao.360.cn/?360safe');

      echo loadc('template')->make('admin/hello', ['a' => 'blockPHP测试页', 'time' => time()])->render();
  }
}