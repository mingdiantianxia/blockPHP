<?php
namespace fky\classs;
use Swoole\Process;
use Swoole\Timer;

/**
 * 定时任务服务
 */
class Crond
{
    /**
     * 当前实例
     * @var Crond
     */
    private static $_instance = null;

    /**
     * 定时任务配置
     */
    private $_conf;

    /**
     * @var WorkerApplication
     */
    private $_app;

    private $_runningTasks = [];

    /**
     * 退出状态
     * @var bool
     */
    private $_flgExit = false;

    private function __construct()
    {
        $this->_conf = loadc('config')->get("", "cron");
        //注册子进程回收信号处理
        Process::signal(SIGCHLD, [$this, 'doSignal']);
        Process::signal(SIGTERM, [$this, 'doSignal']);
    }

    /**
     * 获取定时任务服务
     * @return Crond
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new Crond();
        }
        return self::$_instance;
    }

    public function start()
    {
        $options = getopt('d');
        $this->_log("start cron server...");
        if (isset($options['d'])) {
            Process::daemon();
            file_put_contents($this->_conf['pid'], posix_getpid());
        }

        Timer::tick(1000, [$this, 'doTask']);
        //10s 加载一次配置
        Timer::tick(10000, function () {
            $this->_conf = loadc('config')->get("", "cron", true);
        });
    }

    /**
     * 定时器每秒回调函数
     * @param int $timer_id     - 定时器的ID
     * @param mixed $params
     */
    public function doTask($timer_id, $params = null)
    {
        //开始任务
        $currentTime = time();
        if (isset($this->_conf['jobs']) && !empty($this->_conf['jobs'])) {
            //轮询执行定时任务
            foreach ($this->_conf['jobs'] as $jobId => $job) {
                if (!isset($job['title']) || !isset($job['cron']) || !isset($job['command']) || !isset($job['id'])) {
                    $this->_log("crontab job config error");
                    continue;
                }

                //当前时间在可执行时间范围
                if ($this->_isTimeByCron($currentTime, $job['cron'])) {

                    //最新的定时任务还未退出，阻塞不执行
                    if (isset($this->_runningTasks[$job['id']])) {
                        $this->_log("last cron worker not exit. job id={$job['id']}");
                        continue;  
                    }

                    //启动任务
                    $cronWorker =  new Process(function (Process $worker) use($job) {
                        $this->doCronTask($worker, $job);
                    });

                    $pid = $cronWorker ->start();
                    if ($pid === false) {
                        $this->_log("start cron worker failure.");
                        continue;
                    }
                    $this->_runningTasks[$job['id']] = $pid;//记录该定时任务的子进程id
                    $cronWorker->write(json_encode($job));
                }
            }
        }
    }

    /**
     * do cron worker
     */
    public function doCronTask($worker, $job)
    {
        //设置用户组
        $userName = $this->_conf['user'];
        $userInfo = posix_getpwnam($userName);
        if (empty($userName)) {
            $this->_log("start crontab failure, get userinfo failure. user={$userName}");
            return;
        }
        posix_setuid($userInfo['uid']);
        posix_setgid($userInfo['gid']);

        //clear log
        //这里写清空日志代码
        if (is_file($this->_conf['log'])) {
            file_put_contents($this->_conf['log'], '');
        }

        $this->_log("cron worker running task={$job['title']}, jobId={$job['id']}");
        $command = dirname(__FILE__) . '/../cli/fkycmd';
         set_time_limit(0);
         $cmdArgs = explode(' ',  $job['command']);
         $worker->exec($command,  $cmdArgs);
    }

    /**
     * 处理进程信号
     * @param int $sig  - 信号类型
     */
    public function doSignal($sig) {
        $pidToJobId = array_flip($this->_runningTasks);//反转键和值
        switch ($sig) {
            case SIGCHLD: //子进程退出
                //必须为false，非阻塞模式
                while($ret =  Process::wait(false)) {
//                    echo "recycle child process PID={$ret['pid']}\n";
                    $exitPid = $ret['pid'];
                    if (isset($pidToJobId[$exitPid])) {
                        $jobId = $pidToJobId[$exitPid];
                        unset($this->_runningTasks[$jobId]);
                    }
                }
                //当子进程都退出后，结束masker进程
                if (empty($this->_runningTasks) && $this->_flgExit) {
                    @unlink($this->_conf['pid']);
                    exit(0);
                }

                break;
            case SIGTERM: //终止信号，子进程全都退出
                $this->_log("recv terminate signal, exit crond.");
                $this->_flgExit = true;
                break;
        }
    }

    /**
     * 根据定时任务时间配置，检测当前时间是否在指定时间内
     * @param int $time     - 当前时间
     * @param string $cron  - 定时任务配置
     * @return bool 不在指定时间内返回false, 否则返回true
     */
    private function _isTimeByCron($time, $cron)
    {
        $cronParts = explode(' ', $cron);
        if (count($cronParts) != 6) {
            return false;
        }

        list($sec, $min, $hour, $day, $mon, $week) = $cronParts;

        $checks = array('sec' => 's', 'min' => 'i', 'hour' => 'G', 'day' => 'j', 'mon' => 'n', 'week' => 'w');

        $ranges = array(
            'sec' => '0-59',
            'min' => '0-59',
            'hour' => '0-23',
            'day' => '1-31',
            'mon' => '1-12',
            'week' => '0-6',
        );

        foreach ($checks as $part => $c) {
            $val = $$part;
            $values = array();

            /*
                For patters like 0-23/2
            */
            if (strpos($val, '/') !== false) {
                //Get the range and step
                list($range, $steps) = explode('/', $val);

                //Now get the start and stop
                if ($range == '*') {
                    $range = $ranges[$part];
                }
                list($start, $stop) = explode('-', $range);

                for ($i = $start; $i <= $stop; $i = $i + $steps) {
                    $values[] = $i;
                }
            } /*
                For patters like :
                2
                2,5,8
                2-23
            */
            else {
                $k = explode(',', $val);

                foreach ($k as $v) {
                    if (strpos($v, '-') !== false) {
                        list($start, $stop) = explode('-', $v);

                        for ($i = $start; $i <= $stop; $i++) {
                            $values[] = $i;
                        }
                    } else {
                        $values[] = $v;
                    }
                }
            }

            if (!in_array(date($c, $time), $values) and (strval($val) != '*')) {
                return false;
            }
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
            echo "[{$dateStr}] {$msg}\n";
    }
}