<?php
namespace fky;
class FileCache{
	private $_dir;
	const EXT ='.txt';
	 public function __construct(){
	 	$this->_dir=dirname(__FILE__)."/../../cache/";

	}
        
	public function cacheData($key, $value = null, $expires = '', $path = ''){
	
		$filename = $this->_dir.$path.$key.self::EXT;
		if ($value !== null) {//将value值写入缓存
			if (empty($key)) {
				return false;
			}
			if ($value === '') {
				return @unlink($filename);
			}
			$dir = dirname($filename);
			if (!is_dir($dir)) {
				mkdir($dir, 0777);
			}
			$data['value'] = $value;
			if ($expires != '' && is_numeric($expires)) {
				$data['expires'] = time() + $expires;
			}
			return file_put_contents($filename, json_encode($data));
		}
		if (!is_file($filename)) {
			return;
		}else{
			$data = json_decode(file_get_contents($filename), true);
			if (!empty($data['expires']) && $data['expires'] < time()) {
				@unlink($filename);
				return;
			} else {
				return $data['value'];
			}
		}

	}

}