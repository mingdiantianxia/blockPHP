<?php 
// namespace fky;
/*
加载函数
 */
function loadf($name='', $params = array()){
	if ($name == '') {
		die('function name is empty!');
	} else {
	    $func =  dirname(__FILE__).'/func/' . strtolower($name) . '.func.php';
	    if (!is_file($func)) {
	        die(' function ' . $name . ' Not Found!');
	    }
	    require $func;
	    $function = 'fky\\'.$name;
	    if (!empty($params)) {
	    	return $function($params);
	    } else { 
	    	return $function();
	    }
	}
}
/*
加载类
 */
function loadc($name='', $params = array()){
		if ($name == '') {
			die('class name is empty!');
		}
		static $fky_class = array();
	   if (isset($fky_class[$name])) {
	        return $fky_class[$name];
	    }
	    $class =  dirname(__FILE__).'/class/' . strtolower($name) . '.class.php';
	    if (!is_file($class)) {
	        die(' class ' . $name . ' Not Found!');
	    }
	    require $class;
	    $class_name = 'fky\\'.ucfirst($name);
	    if (!empty($params)) {
	   	    $fky_class[$name] = new $class_name($params);
	    } else {
	   	    $fky_class[$name] = new $class_name();
	    }
	    return $fky_class[$name];
}
 ?>
