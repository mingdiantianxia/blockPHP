<?php
namespace fky\classs;
class FileCache{
	private $_dir;
	const EXT ='.txt';
	 public function __construct(){
	     //缓存主目录
	 	$this->_dir=dirname(__FILE__)."/../../cache/";
	}

    /**
     * @param $key 键
     * @param null $value 值
     * @param string $expires 过期时间（秒）
     * @param string $path 分类缓存目录
     * @return bool|int|void
     */
	public function cacheData($key, $value = null, $expires = '', $path = ''){
	
		$filename = $this->_dir.$path.$key.self::EXT;
		if ($value !== null) {//将value值写入缓存
			if (empty($key)) {
				return false;
			}
			if ($value === '') { //删除缓存
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

		//取出缓存
		if (!is_file($filename)) {
			return;
		}else{
			$data = json_decode(file_get_contents($filename), true);
			if (!empty($data['expires']) && $data['expires'] < time()) { //惰性删除
				@unlink($filename);
				return;
			} else {
				return $data['value'];
			}
		}

	}

}