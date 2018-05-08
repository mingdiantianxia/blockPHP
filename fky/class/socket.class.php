<?php
namespace fky;
require_once(__DIR__.'/../inc/socket/class.PHPWebSocket.php');
class Socket extends PHPWebSocket{
	function __construct(array $params = array()){
		if (empty($params['said'])) {
			$params['said'] = null;
		}
		if (empty($params['join'])) {
			$params['join'] = null;
		}
		if (empty($params['left'])) {
			$params['left'] = null;
		}						
		parent::__construct($params['said'],$params['join'],$params['left']);
	}
}

?>