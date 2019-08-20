<?php 
namespace fky\classs;
require __DIR__.'/../inc/predis/autoload.php';
use Predis\Client;
class Redis extends Client{

    /**
     * 集群设置
     * Redis constructor.
     * @param array $parameters  ['tcp://127.0.0.1:6379',];
     * @param null $options		['cluster' => 'redis','parameters' => ['password' => '集群的统一密码']];
     */
	function __construct($parameters = array('host' => '127.0.0.1', 'port' => 6379, 'database' => 15), $options = null){
		parent::__construct($parameters, $options);
	}
}




