<?php
namespace fky\classs;
/**
 * redis分布式锁
 * @author fukaiyao 2018-07-02
 */
class RedLock
{
    private $retryDelay;
    private $retryCount;
    private $clockDriftFactor = 0.01;

    private $quorum;

    private $servers = array();
    private $instances = array();

    /**
     * RedLock constructor.
     * @param array $servers  redis实例或者服务器地址，如：[object, [host,port,timeout,password]]
     * @param int $retryDelay   最大延迟毫秒数
     * @param int $retryCount   重试次数
     */
    function __construct(array $servers, $retryDelay = 200, $retryCount = 3)
    {
        $this->servers = $servers;

        $this->retryDelay = $retryDelay;
        $this->retryCount = $retryCount;

        $this->quorum  = min(count($servers), (count($servers) / 2 + 1));
    }

    public function lock($resource, $ttl)
    {
        $this->initInstances();

        $token = uniqid();
        $retry = $this->retryCount;

        do {
            $n = 0;

            $startTime = microtime(true) * 1000;

            foreach ($this->instances as $instance) {
                if ($this->lockInstance($instance, $resource, $token, $ttl)) {
                    $n++;
                }
            }

            # Add 2 milliseconds to the drift to account for Redis expires
            # precision, which is 1 millisecond, plus 1 millisecond min drift
            # for small TTLs.
            $drift = ($ttl * $this->clockDriftFactor) + 2;

            $validityTime = $ttl - (microtime(true) * 1000 - $startTime) - $drift;

            if ($n >= $this->quorum && $validityTime > 0) {
                return [
                    'validity' => $validityTime,
                    'resource' => $resource,
                    'token'    => $token,
                ];

            } else {
                foreach ($this->instances as $instance) {
                    $this->unlockInstance($instance, $resource, $token);
                }
            }

            // Wait a random delay before to retry
            $delay = mt_rand(floor($this->retryDelay / 2), $this->retryDelay);
            usleep($delay * 1000);//毫秒

            $retry--;

        } while ($retry > 0);

        return false;
    }

    public function unlock(array $lock)
    {
        $this->initInstances();
        $resource = $lock['resource'];
        $token    = $lock['token'];

        foreach ($this->instances as $instance) {
            $this->unlockInstance($instance, $resource, $token);
        }
    }

    private function initInstances()
    {
        if (empty($this->instances)) {
            foreach ($this->servers as $server) {
                //如果传入的是实例
                if (gettype($server) == 'object') {
                    $redis = $server;
                } else {
                    list($host, $port, $timeout, $password) = $server;
                    $redis = new \Redis();
                    $redis->connect($host, $port, $timeout);
                    //密码不为空, 则需要密码验证
                    if (!empty($password)) {
                        $ret = $redis->auth($password);
                    }
                }

                $this->instances[] = $redis;
            }
        }
    }

    private function lockInstance($instance, $resource, $token, $ttl)
    {
        return $instance->set($resource, $token, ['NX', 'PX' => $ttl]);
    }

    private function unlockInstance($instance, $resource, $token)
    {
        // $token1 = $instance->get($resource);
        // if ($token1 == $token) {
        //     return $instance->delete($resource);
        // } else {
        //     return 0;
        // }

        $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
        ';
        return $instance->eval($script, [$resource, $token], 1);
    }
}
