<?php
declare(ticks=1);
namespace fky\classs\worker;
use fky\classs\Config;
use fky\classs\Phpredis as Redis;
use Swoole\Process;

/**
 * event Listener 工作进程, 主要用于执行数据事件监听任务
 */
class DbEventListener
{
    //kafka消费者offset， 参数说明： 第一个是topic名, 第二个是消费组id, 第三个分区id
    const CACHE_KAFKA_TOPIC_OFFSET = "kafka_consumer_topic_offset_%s_%s_%d";

    /**
     * 当前实例
     * @var DbEventListener
     */
    private static $_instance = null;


    /**
     * listeners配置
     */
    private $_conf;

    private $_listenerConf;

    /**
     * 初始化ListenerId
     * @var string
     */
    private $_listenerId = '';

    /**
     * 是否结束worker
     * @var bool
     */
    private $_flgWorkerExit = false;

    private $_kafkaConsumer;

    /**
     * 记录不同分区的offset
     * 格式:
     *    分区id => offset
     * @var array
     */
    private $_offsets = [];

    /**
     * 事件缓存
     * @var array
     */
    private $_eventBuffer = [];

    private function __construct($listenId)
    {
        $this->_listenerId = $listenId;

        //获取listeners配置
        $this->_conf = Config::getInstance()->get('', 'db_event_listener');
        if (!isset($this->_conf['listeners'][$listenId])) {
            throw new \Exception("event listener[{$listenId}] not found.");
        }
        $this->_listenerConf = $this->_conf['listeners'][$listenId];

        //注册信号处理
        pcntl_signal(SIGTERM, [$this, 'doSignal']);
        pcntl_signal(SIGQUIT, [$this, 'doSignal']);

//        $conf = new \RdKafka\Conf();
//        $conf->setRebalanceCb([$this,"kafkaRebalanceCb"]);
//        $env = Config::getInstance()->get("env");
//        if ($env == 'release') {
//            //release环境跟线上一致
//            $env = 'prod';
//        }
//        $groupId = "{$env}-group-{$listenId}";
//        $conf->set('group.id', $groupId);
//        $conf->set('metadata.broker.list', $this->_conf['kafka']['addrs']);
//        $conf->set('partition.assignment.strategy', 'range');
//        $topicConf = new \RdKafka\TopicConf();
//        $topicConf->set('auto.offset.reset', 'largest');
//        $conf->setDefaultTopicConf($topicConf);
//        $this->_kafkaConsumer = new \RdKafka\KafkaConsumer($conf);
//        $this->_kafkaConsumer->subscribe([$this->_conf['kafka']['binlogTopic']]);
    }


    /**
     * @param string $listenId    - 监听器id
     * @return DbEventListener
     */
    public static function getInstance($listenId)
    {
        if (self::$_instance == null) {
            self::$_instance = new DbEventListener($listenId);
        }
        return self::$_instance;
    }

    /**
     * 进程入口
     */
    public function run()
    {
        set_time_limit(0);

        //设置用户组
        $userName = $this->_conf['user'];
        $userInfo = posix_getpwnam($userName);
        if (empty($userName)) {
            Logger::error("start listener failure, get userinfo failure. user={$userName}");
            return;
        }
        posix_setuid($userInfo['uid']);
        posix_setgid($userInfo['gid']);
        $env = Config::getInstance()->get('env');
        $progName = "DbEventListener: {$env}-{$this->_listenerId}";
        \swoole_set_process_name($progName);

        //启动时间
        $startTime = time();
        //当前监听器处理任务数
        $currentExcutedTasks = 0;
        $this->_flgWorkerExit = false;
        while (!$this->_flgWorkerExit) {
            $currentTime = time();

            //处理任务
            $this->_doTask();
            $this->_app->end(0, false);
            $currentExcutedTasks++;

            if($currentExcutedTasks%2000 == 0)
            {
                $this->_saveOffsets();
            }

            if (($currentTime - $startTime) > $this->_conf['lifeTime']) {
                //超出存活时间，自动退出
                $this->_flgWorkerExit = true;
                Logger::info("Listener (listenerId={$this->_listenerId}) run time exceed lifetime, exit listener.");
                break;
            }

            //超出最大任务处理次数, 自动退出
            if ($currentExcutedTasks > $this->_conf['maxHandleNum']) {
                $this->_flgWorkerExit = true;
                Logger::info("Listener (listenerId={$this->_listenerId}) done tasks exceed maxHandleNum, exit listener.");
                break;
            }
        }

        // 退出前刷新缓存
        if (count($this->_eventBuffer) > 0) {
            $this->_flushBufferAndCallListener(true);
        }
        $this->_saveOffsets();
        Process::kill(posix_getpid(), SIGKILL);
    }

    public function kafkaRebalanceCb(\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null)
    {
        switch ($err) {
            case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                $redis = Redis::getInstance();
                foreach ($partitions as $k => $partition) {
                    $pid = $partition->getPartition();
                    $this->_log("Assign partition id = {$pid} offset=" . $partition->getOffset());
                    $key = $this->_getOffsetsCacheKey($pid);
                    $offset = $redis->get($key);
                    if (!empty($offset)) {
                        //重置offset
                        $this->_log("reset offset partition id={$pid} offset={$offset}");
                        $partition->setOffset($offset + 1);
                    }
                }
                $kafka->assign($partitions);
                break;

            case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                foreach ($partitions as $k => $partition) {
                    $pid = $partition->getPartition();
                    $this->_log("Revoke partition id = {$pid}");
                    if (isset($this->_offsets[$pid])) {
                        unset($this->_offsets[$pid]);
                    }
                }
                $kafka->assign(NULL);
                break;
            default:
                throw new \Exception($err);
        }
    }

    /**
     * 处理进程信号
     * @param int $sig  - 信号类型
     */
    public function doSignal($sig) {
        switch ($sig) {
            case SIGTERM:
                //进程退出处理
                $this->_flgWorkerExit = true;
                Logger::info("listener recv terminate signal. pid=" . posix_getpid());
                break;
        }
    }

    /**
     * 处理监听任务
     * @throws \CDbException
     * @throws \RedisException
     */
    private function _doTask()
    {
        try {
            $message = $this->_kafkaConsumer->consume(1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->_updateOffset($message->partition, $message->offset);
                    $event = json_decode($message->payload, true);
                    if (empty($event)) {
                        Logger::error("解析事件失败, msg={$message->payload}");
                        return false;
                    }

                    //过滤事件
                    $subscribe = $this->_listenerConf['subscribe'];
                    if (!empty($subscribe)) {
                        $scheme     = $event['Schema'];
                        $tableName  = $event['TableName'];
                        if (!isset($subscribe[$scheme])) {
                            return false;
                        }

                        if (!is_array($subscribe[$scheme])) {
                            return false;
                        }

                        if (!empty($subscribe[$scheme]) && !in_array($tableName, $subscribe[$scheme])) {
                            return false;
                        }
                    }

                    $this->_eventBuffer[] = $event;

                    //检测是否刷新缓存
                    $bufSize = count($this->_eventBuffer);
                    if ($bufSize >= $this->_listenerConf['batchSize']) {
                        //flush buffer
                        $this->_flushBufferAndCallListener();
                    }

                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    if (count($this->_eventBuffer) > 0) {
                        $this->_flushBufferAndCallListener();
                    }
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    if (count($this->_eventBuffer) > 0) {
                        $this->_flushBufferAndCallListener();
                    }
                    break;
                default:
                    if (count($this->_eventBuffer) > 0) {
                        $this->_flushBufferAndCallListener();
                    }
                    Logger::error("listener event error, msg=" . $message->errstr());
                    break;
            }
        } catch (\CDbException $e) {
            //如果出现数据库异常直接抛出异常退出worker
            throw $e;
        } catch (\RedisException $e) {
            //redis异常直接退出
            throw $e;
        } catch (\Exception $e) {
            Logger::error("listener error, msg={$e->getMessage()}");
            //异常休眠1秒
            sleep(1);
        }
        return true;
    }

    /**
     * 输出日志
     * @param $msg
     */
    private function _log($msg)
    {
        $dateStr = date("Y-m-d H:i:s");
        $pid = posix_getpid();
        echo "[{$dateStr}] [pid={$pid}] consumer group id={$this->_listenerId} {$msg}\n";
    }

    private function _flushBufferAndCallListener($force = false)
    {
        $hander = $this->_listenerConf['handler'];
        //设置路由
        $route = implode("->", $hander);
        Logger::$route = $route;
        $this->_app->setRoute($route);
        //设置开始执行时间
        \Yii::setBeginTime();

        //失败无限重试
        while (!$this->_flgWorkerExit || $force) {
            $srvObj = ServiceFactory::getService($hander[0]);
            Logger::debug("listener handler execute handler=" . json_encode($hander));
            $ret = call_user_func_array([$srvObj, $hander[1]], [$this->_eventBuffer]);
            Logger::debug("listener handler execute handler result=" . json_encode($ret));

            if (!empty($ret)) {
                //处理成功
                $this->_eventBuffer = [];
                $this->_saveOffsets();
                break;
            } else {
                if ($force) {
                    //强制刷新不重试
                    break;
                }
                Logger::error("retry execute handler = " . json_encode($hander));
                sleep(1);
            }
        }
        \Yii::setEndTime();
    }

    //保存offsets
    private function _saveOffsets()
    {
        if (!empty($this->_offsets)) {
            $redis = Redis::getInstance()->getOriginInstance()->multi();
            foreach ($this->_offsets as $pid => $offset) {
                $key = $this->_getOffsetsCacheKey($pid);
                $redis = $redis->set($key, $offset);
            }
            $redis->exec();
        }
    }

    /**
     * 根据分区id获取offset 缓存key
     * @param int $pid 分区id
     * @return string
     */
    private function _getOffsetsCacheKey($pid) {
        return sprintf(self::CACHE_KAFKA_TOPIC_OFFSET, $this->_conf['kafka']['binlogTopic'], $this->_listenerId, $pid);
    }

    /**
     * 更新分区offset
     * @param int $pid  - 分区id
     * @param int $offset
     */
    private function _updateOffset($pid, $offset)
    {
        $this->_offsets[$pid] = $offset;
    }

    private function _filter()
    {

    }
}