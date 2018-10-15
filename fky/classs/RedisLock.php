<?php
namespace lwm\commons\base;
use lwmf\datalevels\Redis;

/**
 * redis锁
 * @author fukaiyao 2018-07-02
 */
class RedLock
{
    private static $_instance = null;

    private $retryDelay;
    private $retryCount;
    private $clockDriftFactor = 0.01;

    private $_redis = null;

    //key前缀
    private $_lockPrefix = "common_base_lock:";

    /**
     * RedLock constructor.
     */
    function __construct()
    {
        $this->_redis = Redis::getInstance();

        //默认取锁等待3~6秒
        $this->retryDelay = 160; //每次取锁最大延迟毫秒数，最小延迟为此值的一半
        $this->retryCount = 40;  //取锁重试次数

    }

    public static function getInstance()
    {
        if(self::$_instance == null)
        {
            self::$_instance = new RedLock();
        }
        return self::$_instance;
    }

    /**
     * @param string $key 锁名
     * @param int $ttl      有效时间(秒)
     * @param int $retryDelay   每次取锁最大延迟毫秒数，最小延迟为此值的一半
     * @param int $retryCount   取锁重试次数
     * @param bool $is_choke   是否阻塞取锁，false则直接返回取锁结果
     * @return array|bool
     */
    public function lock($key, $ttl = 5, $retryDelay = null, $retryCount = null, $is_choke = true)
    {
        if (empty($retryCount)) {
            $retryCount = $this->retryCount;
        }
        if (empty($retryDelay)) {
            $retryDelay = $this->retryDelay;
        }
        $token = uniqid();

        do {
            $startTime = microtime(true) * 1000;

            $result = $this->_redis->set($this->_getKeyName($key), $token, ['NX', 'PX' => $ttl]);

            # 对生存时间的偏差中增加2毫秒来计算Redis的到期时间
            # 1毫秒加上1毫秒的偏差
            $drift = ($ttl * $this->clockDriftFactor) + 2;

            $validityTime = $ttl - (microtime(true) * 1000 - $startTime) - $drift;

            $lock_token = [
                'validity' => $validityTime,
                'key' => $key,
                'token'    => $token,
            ];

            if (!$is_choke) {
                if ($result) {
                    return $lock_token;
                } else {
                    return false;
                }
            }

            if ($result && $validityTime > 0) {
                return $lock_token;
            }

            // 在重试之前等待一个随机延迟，为的是分散取锁的时间，避免集中抢锁
            $delay = mt_rand(floor($retryDelay / 2), $retryDelay);
            usleep($delay * 1000);//毫秒

            $retryCount--;

        } while ($retryCount > 0);

        return false;
    }

    public function unlock(array $lock)
    {
        $key = $lock['key'];
        $token = $lock['token']; //用请求的唯一token来解锁，避免锁过期，导致的交叉请求相互解锁的问题

        $token1 = $this->_redis->get($this->_getKeyName($key));
        if ($token1 == $token) {
            return $this->_redis->delete($this->_getKeyName($key));
        } else {
            return false;
        }

    }

    private function _getKeyName($key)
    {
        return $this->_lockPrefix . $key;
    }
}
