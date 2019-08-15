<?php
declare(ticks=1);//每执行一次低级语句会检查一次该进程是否有未处理过的信号（用于调用信号处理器）
namespace fky\classs;
require __DIR__.'/../inc/aliyun-mns-php-sdk/mns-autoloader.php';

use fky\classs\MessageServer;
use fky\classs\exceptions\WorkerMessageInvalidException;
/**
 * Worker 工作进程, 主要用于执行异步任务(依赖于pcntl和swoole扩展)
 */
class Worker
{
    /**
     * 当前实例
     * @var Worker
     */
    private static $_instance = null;


    /**
     * worker任务配置
     */
    private $_conf;

    /**
     * 当前 worker队列名
     * @var string
     */
    private $_workerQueueName = '';

    /**
     * 初始化jobName
     * @var string
     */
    private $_jobName = '';

    /**
     * 当前worker配置
     */
    private $_workerConf;

    /**
     * 是否结束worker
     * @var bool
     */
    private $_flgWorkerExit = false;

    private function __construct($jobName)
    {
        $this->_jobName = $jobName;

        $this->_workerQueueName = MessageServer::getInstance()->getQueueNameByJobName($jobName);
        if (empty($this->_workerQueueName)) {
            loadc('log')->error("worker get queue name failure, config invalid. jobName={$jobName}");
            throw new \Exception("worker get queue name failure, config invalid. jobName={$jobName}");
        }

        //获取worker配置
        $this->_conf = loadc('config')->get('', "worker");

        //注册信号处理
        pcntl_signal(SIGTERM, [$this, 'doSignal']);
        pcntl_signal(SIGQUIT, [$this, 'doSignal']);
    }

    /**
     * 获取定时任务服务
     * @param string $jobName
     * @return Worker
     */
    public static function getInstance($jobName)
    {
        if (self::$_instance == null) {
            self::$_instance = new Worker($jobName);
        }
        return self::$_instance;
    }

    /**
     * worker进程入口
     */
    public function run()
    {
        set_time_limit(0);

        //设置用户组
        $userName = $this->_conf['user'];
        $userInfo = posix_getpwnam($userName);
        if (empty($userName)) {
            loadc('log')->error("start worker failure, get userinfo failure. user={$userName}");
            return;
        }
        posix_setuid($userInfo['uid']);
        posix_setgid($userInfo['gid']);

        //获取该工作队列的配置信息
        $config = $this->_conf['workerConf'][$this->_jobName];
        $this->_workerConf = $config;
        $progName = "fky-worker: {$this->_workerQueueName}";

        //修改进程名称。 等同于\Swoole\Process::name($progName);
        \swoole_set_process_name($progName);

        //启动时间
        $startTime = time();
        //当前worker处理任务数
        $currentExcutedTasks = 0;
        $this->_flgWorkerExit = false;
        while (!$this->_flgWorkerExit) {
            $currentTime = time();

            //处理任务
            $this->_doWorkerTask($this->_workerQueueName);

            $currentExcutedTasks++;
            if (($currentTime - $startTime) > $config['lifeTime']) {
                //超出存活时间，自动退出
                $this->_flgWorkerExit = true;
                loadc('log')->info("worker (jobName={$this->_jobName}) run time exceed lifetime, exit worker.");
                break;
            }

            //超出最大任务处理次数, 自动退出
            if ($currentExcutedTasks > $config['maxHandleNum']) {
                $this->_flgWorkerExit = true;
                loadc('log')->info("worker (jobName={$this->_jobName}) done tasks exceed maxHandleNum, exit worker.");
                break;
            }
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
                loadc('log')->info("worker recv terminate signal. pid=" . posix_getpid());
                break;
        }
    }

    /**
     * 处理worker任务
     * @param string $workerMsgQueueName - 队列名
     * @throws \RedisException
     */
    private function _doWorkerTask($workerMsgQueueName)
    {
        $response = null;
        try {
            $waitSeconds = $this->_conf['pollingWaitSeconds'];
            $response = MessageServer::getInstance()->receive($workerMsgQueueName,$waitSeconds);
            if ($response === false) {
                //没有消息休眠1秒
                sleep(1);
                return;
            }

            $pid = posix_getpid();
            loadc('log')->info("worker recv message msgId={$response->getMessageId()}, msg={$response->getMessageBody()}, pid={$pid}");

            $workerMsg = new WorkerMessage($response->getMessageBody());
            $workerType = $workerMsg->getWorkerType();
            if (!isset($this->_conf['workers'][$workerType])) {
                loadc('log')->info("invalid message, worker config not found. worker type={$workerType}");
                MessageServer::getInstance()->delete($workerMsgQueueName, $response->getReceiptHandle());
                return;
            }

            $config = $this->_conf['workers'][$workerType];
            if ($this->_workerConf['preConsume']) {
                //预先删除消息
                MessageServer::getInstance()->delete($workerMsgQueueName, $response->getReceiptHandle());
                loadc('log')->debug("pre delete message. msgId={$response->getMessageId()}");
            }

            //执行队列的处理方法
            $hander = $config['handler'];

            //选中控制器目录
            LoadFactory::setDirMatchedStr($this->_conf['worker_path']['path']);
            $srvObj = LoadFactory::lc($hander[0]);//单例实例化类
            loadc('log')->debug("worker execute message handler=" . json_encode($hander) . ", msgId={$response->getMessageId()}");

            $ret = call_user_func_array([$srvObj, $hander[1]], $workerMsg->getParams());
            loadc('log')->debug("worker execute message handler result=" . json_encode($ret) .", msgId={$response->getMessageId()}");

            if (!empty($ret) && !$this->_workerConf['preConsume']) {
                //任务处理成功，删除消息
                MessageServer::getInstance()->delete($workerMsgQueueName, $response->getReceiptHandle());
                loadc('log')->debug("finish task delete message. msgId={$response->getMessageId()}");
            }

        } catch (WorkerMessageInvalidException $e) {
            //消息格式不正确
            if ($response) {
                //删除消息
                MessageServer::getInstance()->delete($workerMsgQueueName, $response->getReceiptHandle());
                loadc('log')->error("worker error, error={$e->getMessage()}");
            }
        } catch (\RedisException $e) {
            //redis异常直接抛出异常退出worker
            throw $e;
        } catch (\Exception $e) {
            loadc('log')->error("worker error, msg={$e->getMessage()}");

            //异常休眠1秒
            sleep(1);
        }
    }
}