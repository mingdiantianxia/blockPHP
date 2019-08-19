<?php
namespace fky\classs\worker;
use fky\classs\Config;
use fky\classs\Phpredis as Redis;
use Swoole\Process;
use Swoole\Timer;
use Swoole\Http\Server;
/**
 * Db event Server
 */
class DbEventServer {
    //参数: 进程id
    const CACHE_TASKPID_TO_TASKID = "fky_dbevent_server_%d";

    /**
     * event listener配置
     */
    private $_conf;

    /**
     * swoole http server实例
     */
    private $_httpServer;

    /**
     * @var DbEventListenerManage
     */
    private $_eventWorker;

    /**
     * @var DbEventFullSyncWorker
     */
    private $_fullSyncWorker;

    public function __construct()
    {
        $this->_log("start DbEventWorker...");
        $this->_conf = Config::getInstance()->get('', 'db_event_listener');

        $this->_httpServer = new Server($this->_conf['listen']['ip'], $this->_conf['listen']['port']);
        //server配置
        $opt = [
            'worker_num' => 1,    //worker process num, 只能启动一个worker
            'task_worker_num' => $this->_conf['fullsyncWorkers'], //配置此项，则开启task_worker
            'user' => $this->_conf['user'],
            'group' => $this->_conf['user'],
            'pid_file' => $this->_conf['pid'],
            'log_file' => $this->_conf['log'],
            'log_level' => 2,
            'reload_async' => true,
            'max_wait_time' => 60,
        ];

        //根据 -d 参数确认是否后台运行
        $options = getopt('dg::');//接收d和g(分组)两个参数
        if (isset($options['d'])) {
            $opt['daemonize'] = 1;
        }

        $this->_httpServer->set($opt);

        //注册回调函数
        $this->_httpServer->on('request', [$this, "doHttpRequest"]);//处理http请求的回调函数
        $this->_httpServer->on('WorkerStart', [$this, "doWorkerStart"]);//主进程worker的开始回调函数
        $this->_httpServer->on('WorkerStop', [$this, "doWorkerStop"]);//主进程worker的结束回调函数
        $this->_httpServer->on('Task', [$this, "doTask"]);//task_worker
        $this->_httpServer->on('Finish', [$this, "doFinish"]);//task_worker
        $this->_httpServer->on('Shutdown', [$this, "doShutdown"]);//服务器退出回调函数
    }

    public function start()
    {
        $this->_httpServer->start();
    }

    /**
     * 投递任务（由全量同步DbEventFullSyncWorker.php调用）
     * @param $msg
     */
    public function sendTask($msg)
    {
        return $this->_httpServer->task($msg);
    }

    /**
     * 用于初始化工作进程
     * @param $serv
     * @param $worker_id - 不是进程ID，仅仅是一个worker编号
     */
    public function doWorkerStart($serv, $worker_id)
    {
        $env = Config::getInstance()->get('env');
        if ($serv->taskworker) {
            //task进程
            $progName = "{$env}-DbEventTaskWorker-{$worker_id}";
        }
        else {
            //worker进程
            $progName = "{$env}-DbEventWorker-{$worker_id}";

            //初始化listener 管理器
            if ($worker_id == 0) {
                //只在第一个worker进程启动
                $this->_eventWorker = new DbEventListenerManage();
                $this->_log("start DbEventFullSyncWorker...");
                $this->_fullSyncWorker = new DbEventFullSyncWorker($this);
            }
        }
        \swoole_set_process_name($progName);
    }


    /**
     * worker退出回调函数
     * @param $serv
     * @param $worker_id
     */
    public function doWorkerStop($serv, $worker_id)
    {
        if(!$serv->taskworker) {
            if ($worker_id == 0) {
                $this->_eventWorker->close();
            }
        }
    }

    /**
     * 处理http请求
     * @param $request
     * @param $response
     */
    public function doHttpRequest($request, $response)
    {
        $uri = $request->server['path_info'];
        switch ($uri) {
            default:
                $this->_addTask($request, $response);
        }

    }

    private function _addTask($request, $response)
    {
        $get = $request->get;
        if (!isset($get['table']) || !isset($get['listenerid'])) {
            $response->end("invalid params.");
            return;
        }

        $table = trim($get['table']);
        $listenerId = trim($get['listenerid']);
        if (empty($table) || empty($listenerId)) {
            $response->end("invalid params2.");
            return;
        }

        $pk = "id";
        if (isset($get['pk']) && !empty($get['pk'])) {
            $pk = $get['pk'];
        }

        $this->_fullSyncWorker->addTask($table, $listenerId, $pk);
        $response->end("ok");
    }

    /**
     * 处理task任务
     * @param $serv
     * @param $task_id
     * @param $src_worker_id
     * @param $data
     */
    public function doTask($serv, $task_id, $src_worker_id, $data)
    {
        try {
            $key = sprintf(self::CACHE_TASKPID_TO_TASKID, $serv->worker_pid);
            Redis::getInstance()->set($key, $task_id,300);

            if (!empty($data)) {
                $worker = new DbEventFullSyncWorker($this, true);
                $serv->finish($worker->doConcurrencyTask($data));
            }
        } catch (\Exception $e) {
            $this->_log($e->getMessage());
            sleep(1);
            $serv->finish("error");
        }
    }

    /**
     * task任务处理结果回调函数
     * @param $serv
     * @param $task_id
     * @param $data
     */
    public function doFinish($serv, $task_id, $data)
    {
        if (!empty($data)) {
            if (is_string($data) && $data == 'error') {
                $this->_fullSyncWorker->clearRunningStatus($task_id);
            }
            else {
                $this->_fullSyncWorker->finishConcurrencyTask($data);
            }
        }
    }

    /**
     * server退出回调函数
     * @param $server
     */
    public function doShutdown($server)
    {
        $this->_log("db event server exit.");
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