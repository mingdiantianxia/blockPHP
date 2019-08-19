<?php
namespace fky\classs\worker;
use fky\classs\Config;
use fky\classs\LoadFactory;
use fky\classs\Phpredis as Redis;
use Swoole\Timer;

/**
 * 全量同步
 * Class DbEventFullSyncWorker
 */
class DbEventFullSyncWorker
{
    //同步任务
    const CACHE_SYNC_TASKS = "fky_dbevent_fullsync_task";

    /**
     * event listener配置
     */
    private $_conf;

    /**
     * @var Redis
     */
    private $_redis;

    /**
     * 任务状态
     * 格式：
     *  table => [
     *          "listenerId" => "监听器id",
     *          "isSync"    => 0, //是否开始同步, 1 表示开始
     *          "pk"    => "id", //主键ID
     *          "offset"    => 0, //当前同步位置
     *          "maxOffset" => 0, //最大位置
     *          "sync" => [
     *                  //规则 大于等于beginOffset, 小于endOffset
     *                  ["beginOffset" => 0, "endOffset" => 1],
     *                  ["beginOffset" => 0, "endOffset" => 1],
     *              ],
     *      ]
     * @var array
     */
    private $_status = [];

    /**
     * 正在运行的任务
     * 格式:
     *      [
     *         "task序号" => ["beginOffset" => 0, "endOffset" => 1],
     *      ]
     * @var array
     */
    private $_runningTask = [];

    /**
     * 当前正在同步的表
     */
    private $_currentSyncTable;

    /**
     * @var DbEventServer
     */
    private $_eventServer;

    /**
     * 数据库连接实例
     */
    private $_dbInstance;

    public function __construct($eventServer, $isTask = false)
    {
        $this->_conf = Config::getInstance()->get('', 'db_event_listener');
        $this->_redis = Redis::getInstance();
        $this->_eventServer = $eventServer;
        $this->_dbInstance = LoadFactory::lc('db', Config::getInstance()->get('db', 'config'));

        if (!$isTask) {
            //task环境不需要初始化，任务管理器

            $this->_log("加载同步任务...");
            $tasks = $this->_redis->hGetAll(self::CACHE_SYNC_TASKS);
            if (!empty($tasks)) {
                foreach ($tasks as $table => $status) {
                    $st = json_decode($status, true);
                    if (!empty($st) && isset($st['listenerId']) && isset($st['pk'])) {
                        $this->_status[$table] = $st;
                        if ($st['isSync'] == 1) {
                            $this->_currentSyncTable = $table;
                        }
                        $this->_log("加载同步任务, table={$table}, status=$status");
                    }
                }
            }

            //启动任务管理器
            Timer::tick(100, function () {
                $this->manageTask();
            });

            //每1秒保存下状态
            Timer::tick(1000, function () {
                $status = [];
                if (!empty($this->_status)) {
                    foreach ($this->_status as $k => $v) {
                        $status[$k] = json_encode($v);
                    }
                }
                $this->_redis->hMSet(self::CACHE_SYNC_TASKS, $status);
                if (!empty($this->_currentSyncTable) && !empty($this->_status)) {
                    $status = $this->_status[$this->_currentSyncTable];
                    $this->_log("全量同步状态：{$this->_currentSyncTable} [{$status['offset']}, {$status['maxOffset']}]");

                }
            });
        }
    }

    /**
     * 管理同步任务
     */
    public function manageTask()
    {
        if (empty($this->_status)) {
            return;
        }

        if (empty($this->_currentSyncTable)) {
            $this->_selectNextTask();
        }

        $status = $this->_status[$this->_currentSyncTable];
        if (is_numeric($status['maxOffset']) && $status['maxOffset'] == 0) {
            $this->_reloadMaxOffset();
        }

        if ($status['offset'] > $status['maxOffset'] && count($status['sync']) == 0) {
            //同步完成
            $this->_completeTask($this->_currentSyncTable);
            $this->_selectNextTask();
        }

        if (!isset($this->_conf['listeners'][$status['listenerId']])) {
            //任务配置不存在
            $this->_completeTask($this->_currentSyncTable);
            return;
        }

        if (!isset($this->_status[$this->_currentSyncTable])) {
            return;
        }

        //检测是否需要重启旧的任务
        $lastTasks = count($this->_status[$this->_currentSyncTable]['sync']);
        if ($lastTasks > 0) {
            $check = [];
            if (!empty($this->_runningTask)) {
                foreach ($this->_runningTask as $task) {
                    $check["{$task['beginOffset']}-{$task['endOffset']}"] = true;
                }
            }

            foreach ($this->_status[$this->_currentSyncTable]['sync'] as $task) {
                $key = "{$task['beginOffset']}-{$task['endOffset']}";
                if (!isset($check[$key])) {
                    $this->_log("重启同步任务 {$this->_currentSyncTable}, 子任务: [{$task['beginOffset']}, {$task['endOffset']}]");
                    $this->_addConcurrencyTask($this->_status[$this->_currentSyncTable]['listenerId'],$this->_status[$this->_currentSyncTable]['pk'], $task['beginOffset'], $task['endOffset'], true);
                }
            }
        }

        //重新统计正在运行的任务数
        $runs = count($this->_runningTask);
        $validWorkers = $this->_conf['fullsyncWorkers'] - $runs;
        if ($validWorkers < 1) {
            return;
        }

        $listenConf = $this->_conf['listeners'][$status['listenerId']];
        $batchSize = $listenConf['batchSize'];
        for ($i = 0; $i < $validWorkers; $i++) {
            //启动新的任务
            $status = $this->_status[$this->_currentSyncTable];
            if ($status['offset'] <= $status['maxOffset']) {
                $beginOffset = $status['offset'];
                $endOffset = $beginOffset + $batchSize;
                $this->_status[$this->_currentSyncTable]['offset'] = $endOffset;
                $this->_addConcurrencyTask($status['listenerId'],$status['pk'], $beginOffset, $endOffset);
            }
        }
    }


    /**
     * 添加同步任务
     * @param string $table - 表名
     * @param string $listenerId - 监听器id
     * @param string $pk - 主键id
     * @param int $offset - 当前同步位置
     * @param int $maxOffset - 最大位置
     */
    public function addTask($table, $listenerId, $pk, $offset = 0 , $maxOffset = 0)
    {
        $table = trim($table);
        $listenerId = trim($listenerId);
        if (empty($table) || empty($listenerId)) {
            return;
        }

        $status = ['listenerId' => $listenerId, 'sync' => [], 'isSync' => 0, 'offset' => $offset, "pk" => $pk, "maxOffset" => $maxOffset];
        $this->_status[$table] = $status;
        $this->_redis->hSet(self::CACHE_SYNC_TASKS, $table, json_encode($status));
    }

    /**
     * 删除同步任务
     * @param $table
     * @return string
     */
    public function deleteTask($table)
    {
        $table = trim($table);
        if (empty($table)) {
            return $table;
        }

        $this->_redis->hDel(self::CACHE_SYNC_TASKS, $table);
        unset($this->_status[$table]);
    }

    private function _addConcurrencyTask($listenerId, $pk, $beginOffset, $endOffset, $historyTask = false)
    {
        $msg = ['beginOffset' => $beginOffset, 'endOffset' => $endOffset];
        if (!$historyTask) {
            $this->_status[$this->_currentSyncTable]['sync'][] = $msg;
        }


        $msg2 = ['beginOffset' => $beginOffset, 'endOffset' => $endOffset, 'listenerId' => $listenerId, 'pk' => $pk, 'currentSyncTable' => $this->_currentSyncTable];
        $taskId = $this->_eventServer->sendTask($msg2);
        $this->_runningTask[$taskId] = $msg;
    }

    public function clearRunningStatus($taskId)
    {
        unset($this->_runningTask[$taskId]);
    }

    /**
     * 处理并发子任务, 当前任务不要依赖$this->_status和$this->_currentSyncTable, $this->_runningTask变量。
     * @param $msg
     * @return mixed
     */
    public function doConcurrencyTask($msg)
    {
        if (empty($msg)) {
            return;
        }
        $listenerId = $msg['listenerId'];
        $currentSyncTable = $msg['currentSyncTable'];

        $pk = "id";
        if (isset($msg['pk']) && !empty($msg['pk'])) {
            $pk = $msg['pk'];
        }

        if (!isset($this->_conf['listeners'][$listenerId])) {
            $this->_log("doConcurrencyTask 同步失败, 监听器不存在.");
            return ;
        }

        if(is_numeric($msg['beginOffset']))
        {
            $sql = "select * from {$currentSyncTable} where {$pk} >= {$msg['beginOffset']} and {$pk} < {$msg['endOffset']}";
            $datas = $this->_dbInstance->query($sql)->fetchAll();
        }
        else
        {
            $datas = [];
        }
        
        if (!empty($datas)) {
            $evs = [];
            $scheme = Config::getInstance()->get('db.database_name', 'config');

            //处理带scheme的表名
            $p = '/(\w+)\.(\w+)/';
            $result = null;
            if (preg_match($p, $currentSyncTable, $result)) {
                $currentSyncTable = trim($result[2]);
                $scheme = trim($result[1]);
            }

            foreach ($datas as $data) {
                $e = ["EvId" => $data[$pk], "TableName" => $currentSyncTable, "Schema" => $scheme, "PK" => $pk, "Action" => "insert", "Data" => []];
                $e['Data'] = $data;
                $evs[] = $e;
            }

            //消费事件
            $this->_callListener($listenerId, $evs);
        }
        return $msg;
    }

    private function _callListener($listenerId, $evs)
    {
        $listenConf = $this->_conf['listeners'][$listenerId];
        $hander = $listenConf['handler'];

        //失败无限重试
        while (true) {
            $srvObj = LoadFactory::lc($hander[0]);//单例实例化类
            LoadFactory::lc('log')->debug("listener handler execute handler=" . json_encode($hander));

            $ret = call_user_func_array([$srvObj, $hander[1]], [$evs]);
            LoadFactory::lc('log')->debug("listener handler execute handler result=" . json_encode($ret));


            if (!empty($ret)) {
                //处理成功
                break;
            } else {
                LoadFactory::lc('log')->error("retry execute handler = " . json_encode($hander));
                sleep(1);
            }
        }
    }

    public function finishConcurrencyTask($msg)
    {
        if (empty($msg) || !is_array($msg) || !isset($msg['beginOffset']) || !isset($msg['endOffset'])) {
            return;
        }

        $beginOffset = $msg['beginOffset'];
        $endOffset = $msg['endOffset'];
        if (!isset($this->_status[$this->_currentSyncTable])) {
            var_dump($this->_status);
            var_dump($msg);
        }
        foreach ($this->_status[$this->_currentSyncTable]['sync'] as $k => $task) {
            if ($task['beginOffset'] == $beginOffset && $task['endOffset'] == $endOffset) {
                unset($this->_status[$this->_currentSyncTable]['sync'][$k]);
                break;
            }
        }

        //clear running status
        foreach ($this->_runningTask as $k => $msg) {
            if ($msg['beginOffset'] == $beginOffset && $msg['endOffset'] == $endOffset) {
                unset($this->_runningTask[$k]);
                break;
            }
        }
    }

    private function _reloadMaxOffset()
    {
        $status = $this->_status[$this->_currentSyncTable];
        $pk = "id";
        if (isset($status['pk']) && !empty($status['pk'])) {
            $pk = $status['pk'];
        }
        $sql = "select max({$pk}) as maxId, min({$pk}) as minId from {$this->_currentSyncTable}";
        $data = $this->_dbInstance->query($sql)->fetch();
        if (!empty($data)) {
            if($data['maxId'] > 0)
            {
                $this->_status[$this->_currentSyncTable]['offset'] = $data['minId'];
                $this->_status[$this->_currentSyncTable]['maxOffset'] = $data['maxId'];
                $this->_log("初始化 {$this->_currentSyncTable}, 开始结束offset[{$data['minId']}, {$data['maxId']}]");
            }
        }
    }

    private function _completeTask($table)
    {
        if (empty($table)) {
            return;
        }
        $this->_log("完成同步任务: {$table}");
        unset($this->_status[$table]);
        $this->_currentSyncTable = "";
        $this->_runningTask = [];
        $this->deleteTask($table);
    }

    private function _selectNextTask()
    {
        foreach ($this->_status as $table => $status) {
            $this->_currentSyncTable = $table;
            break;
        }
        $this->_runningTask = [];
        if (!empty($this->_status)) {
            $this->_status[$this->_currentSyncTable]['isSync'] = 1;
        }
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