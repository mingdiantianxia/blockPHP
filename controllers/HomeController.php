<?php
namespace controllers;
/**
* \HomeController
*/
class HomeController extends BaseController
{
  
  public function home()
  {
  	die('home');
	$data=loadc('db')->select("account", "hash", ["uniacid" => 18]);
	echo loadc('template')->make('admin/hello', ['a' => 'Messy_MVC首页', 'time' => time(),'data'=>$data])->render();
  }
}