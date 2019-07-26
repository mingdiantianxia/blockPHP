<?php
namespace fky\classs;
 /**
 * redis事务
 * @author fukaiyao 2018-8-22 16:45:45
 */

class RedisTransaction {

	/** @var Redis */
	protected $redis;
	/** @var Redis */
	protected $trueRedis;

	/** @var Redis|null */
	protected $redisTransactionInstance = null;
	protected $redisTransactionLevel = 0;

	protected $isMultiActive;

	/**
	 * [__construct description]
	 * @param string $server [redis实例或者服务器地址]
	 */
	public function __construct($server = '') {

		if (empty($server)) {
			throw new \Exception('redis config failed');
		}

		$this->redis = $this->initRedisConnection($server);
//		$this->trueRedis = new Storage_RedisTrueResultCaller($this->redis);
	}

	protected function initRedisConnection($server) {
		try {
			//如果传入的是实例
	        if (gettype($server) == 'object') {
	            $redis = $server;
	        } else {
	            $redis = new \Redis();
	            $redis->connect($server['host'], $server['port'], $server['timeout']);
	            //密码不为空, 则需要密码验证
	            if (isset($server['password']) && !empty($server['password'])) {
	                $ret = $redis->auth($server['password']);
	            }
	        }
		}
		catch(Exception $exception) {
			throw new \Exception('Connection to "' . $host . ':' . $port . '" failed');
		}
		return $redis;
	}

	public function begin() {
		if(!$this->redisTransactionInstance) {
			$this->redisTransactionInstance = $this->redis;
		}
		if(!$this->redisTransactionLevel) {
			$this->redis = $this->redisTransactionInstance->multi();
		}
		$this->redisTransactionLevel++;
		return $this;
	}

	public function commit() {
		if(!$this->redisTransactionLevel) {
			throw new \Exception('There is no active Redis transaction');
		}
		$this->redisTransactionLevel--;
		$result = null;
		if(!$this->redisTransactionLevel) {
			$result = $this->redis->exec();
			$this->redis = $this->redisTransactionInstance;
            $this->redisTransactionInstance = null;
        }
		return $result;
	}

	public function rollback() {
		if(!$this->redisTransactionLevel) {
			throw new \Exception('There is no active Redis transaction');
		}
		$this->redis->discard();
		$this->redisTransactionLevel = 0;
		$this->redis = $this->redisTransactionInstance;
        $this->redisTransactionInstance = null;
    }

    /**
     * 监听键值(用于事务辅助，必须在begin()方法执行之前执行)
     * @param string $key
     * @author fukaiyao 2019-6-5 09:29:35
     * @return \Redis
     */
    public function watch($key)
    {
        return $this->redis->watch($key);
    }

    /**
     * 取消监听(用于事务辅助，必须在commit()返回false之后执行)
     * @author fukaiyao 2019-6-5 09:29:35
     * @return \Redis
     */
    public function unwatch()
    {
        return $this->redis->unwatch();
    }

	protected static function replaceFalseToNull($data) {
		return $data === false ? null : $data;
	}

	public function __call($method, $args = array()) {
		return call_user_func_array(array($this->redis, $method), $args);
	}

	public function __destruct() {
		if($this->redisTransactionLevel) {
			$this->rollback();
		}
	}
}

class Storage_RedisTrueResultCaller {

	protected $redis;

	public function __construct($redis) {
		$this->redis = $redis;
	}

	public function __call($method, $args = array()) {
		$result = call_user_func_array(array($this->redis, $method), $args);
		if($result === false) {
			throw new \Exception('Redis method "' . $method . '" returns false');
		}
		return $result;
	}
}

