<?php
declare(ticks=1);
namespace fky\classs\worker;
use fky\classs\RedisLock;
use fky\classs\Config;
use fky\classs\Phpredis as Redis;
use Swoole\Process;
use Swoole\Timer;
use Swoole\Http\Server;
/**
 * Listener管理器, 主要用于管理和维护db event listener进程
 */
class DbEventListenerManage
{
    //同步任务
    const CACHE_SYNC_TASKS = "fky_dbevent_fullsync_task";

    //prod环境listener运行版本
    const CACHE_PROD_LISTENER_VERSION = "fky_dbevent_prod_listener_version";

    //release环境listener运行版本
    const CACHE_RELEASE_LISTENER_VERSION = "fky_dbevent_release_listener_version";

    const CACHE_LISTENER_LOCK = "fky_dbevent_lock";

    /**
     * event listener配置
     */
    private $_conf;

    /**
     * 正在运行的listener
     * 格式:
     *    'ListenerId' => [pid1 => true, pid2 => true, pid3 => true]
     * @var array
     */
    private $_runningListeners = [];

    /**
     * pid to ListenerId
     * 格式:
     *  pid => ListenerId
     * @var array
     */
    private $_pidMapToListenerId = [];

    /**
     * 监控worker的Timer ID
     */
    private $_monitorTimerId;

    /**
     * 当前组, 如果为空则不区分组
     * @var string
     */
    private $_currentGroup = "";

    private $_redis;


    public function __construct()
    {
        $this->_log("start DbEventWorker...");
        $this->_conf = Config::getInstance()->get('', 'db_event_listener');

        //注册相关信号处理
        Process::signal(SIGCHLD, [$this, 'doSignal']);

        $this->_redis = Redis::getInstance();
        //根据 -d 参数确认是否后台运行
        $options = getopt('dg::');
        if(isset($options['g'])) {
            //设置当前分组
            $this->_currentGroup = strtolower(trim($options['g']));
        }

        //上报当前listener版本
        $listenersConf = $this->_conf['listeners'];
        //仅生产环境运行。
        if (!empty($listenersConf) && in_array(Config::getInstance()->get('env'), ['prod', 'release'])) {
            $versions = [];
            foreach ($listenersConf as $listenerId => $conf) {
                $ver = 0;
                if (isset($conf['version'])) {
                    $ver = $conf['version'];
                }
                $versions[$listenerId] = $ver;
            }
            $key = self::CACHE_PROD_LISTENER_VERSION;
            if (Config::getInstance()->get('env') == 'release') {
                $key = self::CACHE_RELEASE_LISTENER_VERSION;
            }
            Redis::getInstance()->hMSet($key, $versions);
        }

        $this->startEventListener();

        //监控worker进程
        Timer::after(5*1000, function () {
            $this->_monitorTimerId = Timer::tick(1000, function () {
                $this->startEventListener();
            });
        });
    }

    /**
     * 启动Listener, 允许重复执行
     */
    public function startEventListener()
    {
        $listenersConf = $this->_conf['listeners'];
        //分组处理
        if (!empty($this->_currentGroup)) {
            $groupConf = [];
            foreach ($listenersConf as $listenerId => $conf) {
                $groupName = isset($conf['group']) ? strtolower($conf['group']) : 'default';
                if ($this->_currentGroup == $groupName) {
                    $groupConf[$listenerId] = $conf;
                }
            }
            $listenersConf = $groupConf;
        }

        if (empty($listenersConf)) {
            $this->_log("listeners config is null");
            return;
        }

        if (in_array(Config::getInstance()->get('env'), ['prod', 'release'])) {

            //过滤非正式环境的配置
            $listenersConf = $this->_filterListenerByVersion($listenersConf);
            //根据过滤结果，关闭踢出当前环境的listener, 一个listener只能在一个环境运行。
            if (!empty($this->_runningListeners)) {
                foreach ($this->_runningListeners as $listenerId => $pids) {

                    //正在运行的监听器，未找到配置，则踢出该监听器进程
                    if (!isset($listenersConf[$listenerId])) {
                        foreach ($pids as $pid => $t) {
                            $this->_log("踢出listener进程={$listenerId}, pid={$pid}");
                            Process::kill($pid, SIGTERM);
                        }
                    }
                }
            }
        }

        //获取全量同步的工作缓存
        $fullSyncTasks = Redis::getInstance()->hGetAll(self::CACHE_SYNC_TASKS);
        $checkFullSyncTasks = [];

        if (!empty($fullSyncTasks) && is_array($fullSyncTasks)) {
            foreach ($fullSyncTasks as $table => $status) {
                $st = json_decode($status, true);
                if (!empty($st) && isset($st['listenerId'])) {
                    $checkFullSyncTasks[$st['listenerId']] = true;
                }
            }
        }

        foreach ($listenersConf as $listenerId => $conf) {
            if (!isset($conf['subscribe']) || !isset($conf['workers']) || !isset($conf['handler'])) {
                $this->_log("listener config error. listenId={$listenerId}");
                continue;
            }

            //全量同步控制，正在全量同步
            if (isset($checkFullSyncTasks[$listenerId])) {

                //关闭增量同步
                if (isset($this->_runningListeners[$listenerId])) {
                    foreach ($this->_runningListeners[$listenerId] as $pid => $t) {
                        $this->_log("正在全量同步, 暂时关闭增量监听器 pid={$pid}");
                        Process::kill($pid, SIGTERM);
                    }
                }
                continue;
            }

            //redis锁控制同一时间，一个listener只能在一个环境运行
           if (in_array(Config::getInstance()->get('env'), ['prod', 'release'])) {
                $key = self::CACHE_LISTENER_LOCK . "_{$listenerId}";
                if (!$this->_lock($key)) {
                    $this->_log("listener 被其他环境锁住无法运行, listenerId={$listenerId}");
                    continue;
                }
           }

            //控制测试环境的进程数
           if (!in_array(Config::getInstance()->get('env'), ['prod', 'release'])) {
               //默认启动一个进程用于测试
               $conf['workers'] = 1;
           }

            $workers = $this->_getListeners($listenerId);
            if ($workers >= $conf['workers']) {
                continue;
            }

            $hasWorkers = $conf['workers'] - $workers;
            //启动worker
            for ($i=0; $i < $hasWorkers; $i++) {
                $workerProcess = new Process(function (Process $worker) use ($listenerId) {
                    $this->_log("start listener, listenId={$listenerId}, pid={$worker->pid}");
                    $cmd = $this->_conf['php'];
                    $path = FKY_PROJECT_PATH . '/fky/cli/DbEventListener.php';
                    $worker->exec($cmd,  [$path, '-t', $listenerId]);
                },false, false);

                $pid = $workerProcess->start();
                if ($pid === false) {
                    $this->_log("start listener failure. listenId={$listenerId}");
                    continue;
                }
                //注册listener
                $this->_addListener($listenerId, $pid);
            }
        }
    }

    /**
     * 灰度切换处理, 根据生产环境和灰度环境，竞争运行权限，只有版本号最高的获得运行权限
     *
     * @param array $listenersConf
     * @return array
     */
    private function _filterListenerByVersion(array $listenersConf)
    {
        if (empty($listenersConf)) {
            return $listenersConf;
        }

        $redis = Redis::getInstance();
        $prodVersions = $redis->hGetAll(self::CACHE_PROD_LISTENER_VERSION);
        $prodVersions = empty($prodVersions) ? [] : $prodVersions;
        $releaseVersion = $redis->hGetAll(self::CACHE_RELEASE_LISTENER_VERSION);
        $releaseVersion = empty($releaseVersion) ? [] : $releaseVersion;


        $newListeners = [];
        foreach ($listenersConf as $listenerId => $conf) {
            $prodVer = 0;
            if (isset($prodVersions[$listenerId])) {
                $prodVer = $prodVersions[$listenerId];
            }

            $rcVer = 0;
            if (isset($releaseVersion[$listenerId])) {
                $rcVer = $releaseVersion[$listenerId];
            }

            if (Config::getInstance()->get('env') == 'prod') {
                //如果当前环境是生产环境
                if ($prodVer >= $rcVer) {
                    $newListeners[$listenerId] = $conf;
                }
                else {
                    //过滤当前listener
                    continue;
                }
            }
            elseif (Config::getInstance()->get('env') == 'release') {
                //灰度环境
                if ($rcVer > $prodVer) {
                    $newListeners[$listenerId] = $conf;
                }
                else {
                    continue;
                }
            }
        }

        return $newListeners;
    }

    /**
     * 处理进程信号
     * @param int $sig  - 信号类型
     */
    public function doSignal($sig) {
        switch ($sig) {
            case SIGCHLD:
                //回收子进程资源
                //必须为false，非阻塞模式
                while($ret =  Process::wait(false)) {
                    $pid = $ret['pid'];
                    $this->_delListenerByPid($pid);
                    $this->_log("回收进程资源, pid={$ret['pid']}");
                }

                break;
        }
    }

    /**
     * 关闭worker
     */
    public function close()
    {
        if (!empty($this->_pidMapToListenerId)) {
            $this->_log("DbEventWorker  shutdown...");
            pcntl_signal(SIGCHLD, [$this, 'doSignal']);
            Timer::clear($this->_monitorTimerId);
            foreach (array_keys($this->_pidMapToListenerId) as $pid) {
                Process::kill($pid, SIGTERM);
            }

            $this->_log("开始回收db listener进程资源...");
            foreach (array_keys($this->_pidMapToListenerId) as $pid) {
                $ret = Process::wait(true);
                $this->_delListenerByPid($pid);
                $this->_log("回收进程资源2, pid={$ret['pid']}");
            }
            $this->_log("回收db listener进程资源结束.");
        }
    }


    /**
     * 添加listener
     * @param string $listenerId
     * @param  int $pid - 进程id
     */
    private function _addListener($listenerId, $pid)
    {
        if (!isset($this->_runningListeners[$listenerId])) {
            $this->_runningListeners[$listenerId] = [];
        }
        $this->_runningListeners[$listenerId][$pid] = true;
        $this->_pidMapToListenerId[$pid] = $listenerId;
    }

    /**
     * 根据listenerId返回目前正在运行的listener数量
     * @param $listenerId
     * @return int
     */
    private function _getListeners($listenerId)
    {
        if (!isset($this->_runningListeners[$listenerId])) {
            return 0;
        }
        return count($this->_runningListeners[$listenerId]);
    }

    /**
     * 删除listener
     * @param int $pid      - 进程id
     * @return bool
     */
    private function _delListenerByPid($pid) {
        if (!isset($this->_pidMapToListenerId[$pid])) {
            return false;
        }
        $listenerId = $this->_pidMapToListenerId[$pid];
        
        if (in_array(Config::getInstance()->get('env'), ['prod', 'release'])) {
            $key = self::CACHE_LISTENER_LOCK . "_{$listenerId}";
            $this->_unlock($key);
        }

        unset($this->_pidMapToListenerId[$pid]);
        if (isset($this->_runningListeners[$listenerId]) && isset($this->_runningListeners[$listenerId][$pid])) {
            unset($this->_runningListeners[$listenerId][$pid]);
        }
        return true;
    }

    /**
     * 返回listener总数
     * @return int
     */
    private function _getTotalListeners()
    {
        if (empty($this->_runningListeners)) {
            return 0;
        }
        $total = 0;
        foreach (array_keys($this->_runningListeners) as $listenerId) {
            $total += count($this->_runningListeners[$listenerId]);
        }
        return $total;
    }

    /**
     * 输出日志
     * @param $msg
     */
    private function _log($msg)
    {
        $dateStr = date("Y-m-d H:i:s");
        $pid = posix_getpid();
        echo "[{$dateStr}] [pid={$pid}] {$msg}\n";
    }

    /**
     * 非阻塞式加锁，如果获取到锁，可以继续延长锁的超时时间
     * @return bool 成功返回true, 失败返回false
     */
    private function _lock($key)
    {
        $owner = sprintf("env-%s-%d", gethostname(), posix_getpid());
        $now = time();
        $meta = ['owner' => $owner, 'expire' => $now + 5];
        $isLock = $this->_redis->setNx($key, $meta);
        if ($isLock) {
            return true;
        }
        else {
            $oldMeta = $this->_redis->get($key);
            if (empty($oldMeta)) {
                return $this->_lock();
            }
            if ($oldMeta['owner'] == $owner) {
                //自己
                //延长锁的时间
                $this->_redis->set($key, $meta);
                return true;
            }

            //timeout checking
            if ($oldMeta['expire'] < $now) {
                $this->_redis->delete($key);
                //retry again          
                $isLock = $this->_redis->setNx($key, $meta);
                if ($isLock) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 释放锁
     * @param string $key
     * @return boolean 成功返回true，失败返回false
     */
    private function _unlock($key)
    {
        $meta = $this->_redis->get($key);
        if (empty($meta)) {
            return true;
        }
        $owner = sprintf("env-%s-%d", gethostname(), posix_getpid());
        if ($owner == $meta['owner']) {
            //只能释放自己的锁
            return $this->_redis->delete($key);
        }
        return false;
    }
}