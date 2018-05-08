<?php 
namespace fky\classs;
require __DIR__.'/../inc/predis/autoload.php';
use Predis;
class Redis extends Predis\Client{
	function __construct($parameters = array('host' => '127.0.0.1', 'port' => 6379, 'database' => 15), $options = null){
		parent::__construct($parameters, $options);
	}
}




 ?>
