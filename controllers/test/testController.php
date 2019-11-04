<?php
namespace controllers\test;
use controllers\console\Test2Controller;
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

  	  $data = loadc('db')->pdo->query('show databases', \PDO::FETCH_ASSOC)->fetchAll();
  	  var_dump($data);
  	  die('good2');
  	 // $this->showResponse(200,'',$data,'arr');
      //  	 $response = loadc('HttpRequest')->GET('https://hao.360.cn/?360safe');

      echo loadc('template')->make('admin/hello', ['a' => 'blockPHP测试页', 'time' => time()])->render();
  }
}