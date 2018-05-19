<?php 
namespace fky\classs;
require __DIR__.'/../inc/wechat/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
class Log extends Logger{
	private $fkyhandler;
	function __construct($name = 'fky', array $handlers = array(), array $processors = array(), $path = __DIR__.'/../../data/log/fky.log'){
		$this->fkyhandler = new StreamHandler($path, Logger::DEBUG);
		array_unshift($handlers, $this->fkyhandler);
		parent::__construct($name,$handlers,$processors);
	}
}
 ?>
