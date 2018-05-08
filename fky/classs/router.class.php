<?php
namespace fky\classs;
require_once(__DIR__.'/../inc/router/autoload.php');
use \marcfowler\macaw\Macaw;

class Router extends Macaw{
	public function __construct(){

	} 
	public static function get($router = '/', $callback = ''){
		parent::get($router, $callback);
	}
	public static function post($router = '/', $callback = ''){
		parent::post($router, $callback);
	}
	public static function put($router = '/', $callback = ''){
		parent::put($router, $callback);
	}
	public static function delete($router = '/', $callback = ''){
		parent::delete($router, $callback);
	}
	public static function options($router = '/', $callback = ''){
		parent::options($router, $callback);
	}
	public static function head($router = '/', $callback = ''){
		parent::head($router, $callback);
	}
	public static function dispatch(){
		parent::dispatch();
	}
	public static function __callstatic($method, $params) {
		parent::__callstatic($method, $params);
	}
	public static function error($callback) {
  		parent::error($callback);
  	}
	public static function haltOnMatch($flag = true) {
  		parent::haltOnMatch($flag);
	}		
	public static function setMethod($method) {
  		parent::setMethod($method);
	}
	public static function setPrefix($prefix = '') {
  		parent::setPrefix($prefix);
	}
	public static function getPathInfo() {
  		parent::getPathInfo();
	}

}

?>