<?php
namespace fky\classs;
use fky\classs\MessageServer;
use Swoole\Process;
use Swoole\Timer;
/**
 * Worker server, 主要用于管理和维护worker进程
 */
class WorkerServer
{
    /**
     * 当前实例
     * @var WorkerServer
     */
    private static $_instance = null;

    /**
     * 整个worker服务的配置
     */
    private $_conf;

    /**
     * 正在运行的workers
     * 格式:
     *    'Worker type' => [pid1 => true, pid2 => true, pid3 => true]
     * @var array
     */
    private $_runningWorkers = [];

    /**
     * pid to worker type
     * 格式:
     *  pid => worker type
     * @var array
     */
    private $_pidMapToWorkerType = [];

    /**
     * 监控worker的Timer ID
     */
    private $_monitorTimerId;

    /**
     * 用于控制dev,test环境，每个队列只启动1个进程
     * @var array
     */
    private $_queueWorkers = [];

    private function __construct()
    {
        $this->_log("start worker server...");
        $this->_conf = loadc('config')->get("", "worker");

        //masker进程注册相关信号处理
        Process::signal(SIGCHLD, [$this, 'doSignal']);
        Process::signal(SIGTERM, [$this, 'doSignal']);

        //根据 -d 参数确认是否后台运行
        $options = getopt('d');
        if (isset($options['d'])) {
            Process::daemon();
            file_put_contents($this->_conf['pid'], posix_getpid());
        }

        //初始化worker队列
        $this->_initWorkerMessageQueue();
    }

    /**
     * 获取定时任务服务
     * @return WorkerServer
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new WorkerServer();
        }
        return self::$_instance;
    }

    /**
     * 启动worker server
     */
    public function run()
    {
        $this->startWorker();

        //监控worker进程 (5分钟后触发回调函数)
        Timer::after(5*60*1000, function () {
            //每秒执行一次worker
            $this->_monitorTimerId = Timer::tick(1000, function () {
                $this->startWorker();
            });
        });
    }

    /**
     * 启动worker, 允许重复执行
     */
    public function startWorker()
    {
        $workersConf = $this->_conf['workerConf'];
        if (empty($workersConf)) {
            return;
        }

        foreach ($workersConf as $jobName => $conf) {
            if (!isset($conf['threadNum']) || !isset($conf['lifeTime']) || !isset($conf['maxHandleNum'])) {
                $this->_log("worker config error. jobName={$jobName}");
                continue;
            }

            //控制测试环境的进程数
            if (in_array(loadc('config')->get("env"), ['dev'])) {
                $jWorkers = 0;
                if (isset($this->_queueWorkers[$jobName])) {
                    $jWorkers = $this->_queueWorkers[$jobName];
                }
                else {
                    $this->_queueWorkers[$jobName] = 1;
                }
                if ($jWorkers > 0) {
                    continue;
                }
                //默认启动一个进程用于测试
                $conf['threadNum'] = 1;
            }

            //该队列目前有多少个worker进程在执行
            $workers = $this->_getWorkers($jobName);
            if ($workers >= $conf['threadNum']) {
                continue;
            }

            //启动设置的多进程处理worker任务
            $hasWorkers = $conf['threadNum'] - $workers;
            //启动worker
            for ($i=0; $i < $hasWorkers; $i++) {
                $workerProcess = new Process(function (Process $worker) use ($jobName) {
                    $this->_log("start worker, jobName={$jobName}, pid={$worker->pid}");

                    //执行脚本，传递t参数(值为队列名)，处理队列
                    $cmd = $this->_conf['php'];
                    $worker->exec($cmd,  ['worker.php', '-t', $jobName]);
                },false, false);

                $pid = $workerProcess->start();
                if ($pid === false) {
                    $this->_log("start worker failure. jobName={$jobName}");
                    continue;
                }
                //注册worker
                $this->_addWorker($jobName, $pid);
            }
        }
    }

    /**
     * 处理进程信号
     * @param int $sig  - 信号类型
     */
    public function doSignal($sig) {
        switch ($sig) {
            case SIGCHLD:
                //子进程退出时，回收子进程资源
                //必须为false，非阻塞模式
                while($ret =  Process::wait(false)) {
                    $pid = $ret['pid'];
                    $this->_delWorkerByPid($pid);
                    $this->_log("回收进程资源, pid={$ret['pid']}");
                }

                if ($this->_getTotalWorkers() == 0) {
                    //当子进程都退出后，结束masker进程
                    @unlink($this->_conf['pid']);
                    exit(0);
                }
                break;
            case SIGTERM:
                //主进程退出处理
                //关闭监控
                Timer::clear($this->_monitorTimerId);
                if (!empty($this->_pidMapToWorkerType)) {
                    $this->_log("worker server shutdown...");
                    foreach (array_keys($this->_pidMapToWorkerType) as $pid) {
                        Process::kill($pid, SIGTERM);
                    }
                }
                break;
        }
    }

    /**
     * 初始化消息队列
     */
    private function _initWorkerMessageQueue()
    {
        if (empty($this->_conf)) {
            return;
        }

        $messageServer = MessageServer::getInstance();
        foreach ($this->_conf['workerConf'] as $jobName => $workerConfig) {
            //获取根据环境拼接后的队列名称
            $queueName = $messageServer->getQueueNameByJobName($jobName);
            if (empty($queueName)) {
                $this->_log("creare worker message queue failure, get queue name failure. queueName={$queueName}");
                continue;
            }

            //创建队列（不存在则创建，存在则返回true）
            $ret = $messageServer->createQueue($queueName);
            if (!$ret) {
                $this->_log("creare worker message queue failure. queueName={$queueName}");
            }

            //worker获取消息后，多长时间内其他worker不能消费同一条消息，单位秒，最长12小时内
            if (isset($workerConfig['visibilityTimeout'])) {
                //设置队列属性
                $messageServer->setQueueAttributes($queueName, $workerConfig['visibilityTimeout']);
            }
        }
    }


    /**
     * 添加worker
     * @param string $jobName
     * @param  int $pid - 进程id
     */
    private function _addWorker($jobName, $pid)
    {
        if (!isset($this->_runningWorkers[$jobName])) {
            $this->_runningWorkers[$jobName] = [];
        }
        $this->_runningWorkers[$jobName][$pid] = true;
        $this->_pidMapToWorkerType[$pid] = $jobName;
    }

    /**
     * 根据jobName返回指定worker目前正在运行的worker数量
     * @param string $jobName
     * @return int
     */
    private function _getWorkers($jobName)
    {
        if (!isset($this->_runningWorkers[$jobName])) {
            return 0;
        }
        return count($this->_runningWorkers[$jobName]);
    }

    /**
     * 删除worker
     * @param int $pid      - 进程id
     * @return bool
     */
    private function _delWorkerByPid($pid) {
        if (!isset($this->_pidMapToWorkerType[$pid])) {
            return false;
        }
        $workerType = $this->_pidMapToWorkerType[$pid];
        unset($this->_pidMapToWorkerType[$pid]);
        if (isset($this->_runningWorkers[$workerType]) && isset($this->_runningWorkers[$workerType][$pid])) {
            unset($this->_runningWorkers[$workerType][$pid]);
        }
        return true;
    }

    /**
     * 返回workers总数
     * @return int
     */
    private function _getTotalWorkers()
    {
        if (empty($this->_runningWorkers)) {
            return 0;
        }
        $total = 0;
        foreach (array_keys($this->_runningWorkers) as $workerType) {
            $total += count($this->_runningWorkers[$workerType]);
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
}