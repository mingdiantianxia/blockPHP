<?php
namespace fky\classs;
require __DIR__.'/../inc/aliyun-mns-php-sdk/mns-autoloader.php';

use AliyunMNS\Client;
use AliyunMNS\Exception\MessageNotExistException;
use AliyunMNS\Exception\MnsException;
use AliyunMNS\Exception\QueueAlreadyExistException;
use AliyunMNS\Model\QueueAttributes;
use AliyunMNS\Requests\CreateQueueRequest;
use AliyunMNS\Requests\SendMessageRequest;
use AliyunMNS\Config as AlyConfig;
//use config\constants\WorkerTypes;
//use lwmf\base\worker\WorkerMessage;
//use lwmf\services\ServiceFactory;

/**
 * 基于阿里云mns的消息队列服务
 */
class MessageServer
{
    /**
     * @var MessageServer
     */
    private static $_instance;

    private $_conf;

    private $_txLogs = [];

    /**
     * @var Client
     */
    private $_client;

    public function __construct()
    {
        $this->_conf = Config::getInstance()->get('', 'mns');
        $aliConfig = new AlyConfig();
        $this->_client = new Client($this->_conf['endPoint'], $this->_conf['accessKeyId'], $this->_conf['accessSecret']);
        
    }

    protected function tableName()
    {
        return '{{worker_log}}';
    }

    /**
     * 发送消息之前增加消息事务日志
     * @param array $info 日志内容
     * @return bool | id
     */
    private function addMessageLog($info)
    {
        $res = $this->getDbCommand()->insert($this->tableName(), $info);
        if($res)
        {
            return $this->getDbCommand()->getConnection()->getLastInsertID();
        }

        return false;
    }


    /**
     * 消息发送成功之后删除该消息事务日志
     * @param int $id 日志id
     * @return bool
     */
    private function delMessageLog($id)
    {
        if (empty($id)) {
            return false;
        }
        $this->getDbCommand()->delete($this->tableName(), "{$this->_pk}=:id", array(':id' => $id));
        
        return true;
    }

    /**
     * 获取消息服务
     * @return MessageServer
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new MessageServer();
        }
        return self::$_instance;
    }

    /**
     * 创建队列
     * @param string $queueName     - 队列名
     *
     * @return bool 成功返回true, 失败返回false
     */
    public function createQueue($queueName)
    {
        if (empty($queueName)) {
            return false;
        }
        $request = new CreateQueueRequest($queueName);
        try {
            $res = $this->_client->createQueue($request);
            return true;
        }
        catch (QueueAlreadyExistException $e) {
            //队列已经存在
            return true;
        }
        catch (MnsException $e) {
            Logger::error("create message queue failure. queue={$queueName}, mns code={$e->getMnsErrorCode()}, msg={$e->getMessage()}");
        }
        return false;
    }

    /**
     * 设置队列属性
     * @param int $visibilityTimeout - worker获取消息后，多长时间内其他worker不能消费同一条消息，单位秒，最长12小时内
     * @return bool
     */
    public function setQueueAttributes($queueName, $visibilityTimeout)
    {
        if (empty($queueName)) {
            return false;
        }

        $attr = new QueueAttributes();
        $attr->setVisibilityTimeout($visibilityTimeout);
        $queue = $this->_client->getQueueRef($queueName);
        try {
            $queue->setAttribute($attr);
            return true;
        }
        catch (MnsException $e) {
            Logger::error("set message queue attr failure. queue={$queueName}, mns code={$e->getMnsErrorCode()}, msg={$e->getMessage()}");
        }
        return false;
    }

    /**
     * 发送消息
     * @param string $queueName     - 队列名
     * @param string $msgBody       - 消息内容
     * @param int $delaySeconds     - 延迟时间，单位秒 0-604800秒（7天）范围内某个整数值
     * @return bool
     * 成功返回消息id, 失败返回false
     */
    public function send($queueName, $msgBody, $delaySeconds=null)
    {
        if (empty($queueName)) {
            return false;
        }

        $queue = $this->_client->getQueueRef($queueName);
        $request = new SendMessageRequest($msgBody,$delaySeconds);
        try {
            $res = $queue->sendMessage($request);
            return $res->getMessageId();
        }
        catch (MnsException $e) {
            Logger::error("send message failure. queue={$queueName}, mns code={$e->getMnsErrorCode()}, msg={$e->getMessage()}");
        }
        return false;
    }

    /**
     * 获取消息
     * @param string $queueName     - 队列名
     * @param int $waitSeconds      - 队列消息为空时等待多长时间,非0表示这次receiveMessage是一次http long polling，如果queue内刚好没有message，那么这次request会在server端等到queue内有消息才返回。最长等待时间为waitSeconds的值，最大为30。
     * @return \AliyunMNS\Responses\ReceiveMessageResponse
     * 成功返回消息对象，失败返回false
     */
    public function receive($queueName, $waitSeconds = 0)
    {
        $queue = $this->_client->getQueueRef($queueName);
        try {
            return $queue->receiveMessage($waitSeconds);
        }
        catch (MessageNotExistException $e) {
            //没有消息不抛异常
            return false;
        }
        catch (MnsException $e) {
            Logger::error("receive message failure. queue={$queueName}, mns code={$e->getMnsErrorCode()}, msg={$e->getMessage()}");
            return false;
        }
    }

    /**
     * 修改消息可见时间, 既从现在到下次可被用来消费的时间间隔
     * @param string $queueName     - 队列名
     * @param string $receiptHandle   - 消息句柄
     * @param int $visibilityTimeout  - 从现在到下次可被用来消费的时间间隔，单位为秒
     * @return bool 成功返回true, 失败false
     */
    public function changeMessageVisibility($queueName, $receiptHandle, $visibilityTimeout)
    {
        $queue = $this->_client->getQueueRef($queueName);
        try {
            $queue->changeMessageVisibility($receiptHandle,$visibilityTimeout);
            return true;
        }
        catch (MnsException $e) {
            Logger::error("change message visibility failure. queue={$queueName}, msg={$e->getMessage()}");
            return false;
        }
    }

    /**
     * 删除消息
     * @param string $queueName     - 队列名
     * @param mixed $receiptHandle  - 消息句柄
     * @return bool 删除成功返回true, 失败返回false
     */
    public function delete($queueName, $receiptHandle)
    {
        $queue = $this->_client->getQueueRef($queueName);
        try {
            $res = $queue->deleteMessage($receiptHandle);
            return true;
        }
        catch (MnsException $e)
        {
            Logger::error("delete message failure. queue={$queueName}, mns code={$e->getMnsErrorCode()}, msg={$e->getMessage()}");
            return false;
        }
    }

    /**
     * 把消息发送给指定的worker执行
     * @param string $workerType - worker type
     * @param array $params - 任务参数
     * @param int $delaySeconds - 延迟时间，单位秒 0-604800秒（7天）范围内某个整数值
     * @param int $id   - 事务日志id
     * @param bool $switchToGray - 是否切换至灰度环境
     * @return bool 成功返回true, 失败返回false
     * @throws \CDbException
     */
    public function dispatch($workerType, array $params = [], $delaySeconds = null, $id = 0, $switchToGray = false)
    {
        //dev环境不走消息队列, 直接调用消息处理器
        if (LWM_ENV == 'dev') {
            $workerConfig = null;
            try {
                $workerConfig = Config::getInstance()->get("workers.{$workerType}", 'worker');
            } catch (\CDbException $e) {
                throw new \CDbException("worker config not found, workerType={$workerType}");
            }

            if (!isset($workerConfig['handler']) || empty($workerConfig['handler'])) {
                throw new \CDbException("worker config invalid, workerType={$workerType}");
            }

            $handler = $workerConfig['handler'];
            $srvObj = ServiceFactory::getService($handler[0]);
            $ret = call_user_func_array([$srvObj, $handler[1]], $params);
            if (!empty($ret)) {
                if($id)
                {
                    $this->delMessageLog($id);
                }
                return true;
            }
            return false;
        }

        $env = Config::getInstance()->get('env');
        if ($switchToGray && LWM_ENV == 'prod') {
            $env = 'release';
        }
        $queueName = $this->getQueueNameByWorkerType($workerType, $env);
        if (empty($queueName)) {
            return false;
        }

        $msg = new WorkerMessage();
        $msg->setWorkerType($workerType);
        $msg->setParams($params);
        $ret = $this->send($queueName, $msg->serialize(), $delaySeconds);
        if ($ret !== false) {
            //如果消息事务日志id存在则删除该事务日志，避免定时任务一直跑这条事务
            if($id)
            {
                $this->delMessageLog($id);
            }
            return true;
        }
        return false;
    }

    /**
     * 消息事务begin
     * @param string $workerType - worker type
     * @param array $params - 任务参数
     * @param int $delaySeconds - 延迟时间，单位秒 0-604800秒（7天）范围内某个整数值
     * @param bool $switchToGray - 是否切换至灰度环境
     * @return bool 成功返回true, 失败返回false
     * @throws \CDbException
     */
    public function dispatchTx($workerType, array $params = [], $delaySeconds = null, $switchToGray = false)
    {
        $addArr = [
            'worker_type' => $workerType,
            'params' => $params,
            'delay_seconds' => $delaySeconds,
            'gray' => $switchToGray
        ];
        $info = ['params'=>json_encode($addArr),'ctime'=>date('Y-m-d H:i:s')];
        
        $res = $this->addMessageLog($info);
        if($res)
        {
            $this->_txLogs[$res] = $addArr;
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 消息事务commit
     * @throws \CDbException
     */
    public function dispatchCommit()
    {
        if($this->_txLogs)
        {
            foreach ($this->_txLogs as $key => $value) 
            {
                $workerType = $value['worker_type'];
                $params['data'] = $value['params'];
                $delaySeconds = $value['delay_seconds'];
                $id = $key;
                $this->dispatch($workerType,$params['data'],$delaySeconds,$id,$value['gray']);
            }
        }
    }

    /**
     * 定时任务补偿机制
     * @throws \CDbException
     */
    public function queryDispatch()
    {
        $date = date('Y-m-d H:i:s',time()-600);
        $res = $this->getDbCommand()->from($this->tableName())->limit(100)->where("ctime<:ctime",[":ctime" => $date])->queryAll();
        
        if(!empty($res))
        {
            foreach ($res as $key => $value) 
            {
                $params = json_decode($value['params'],true);
                $workerType = $params['worker_type'];
                $data = $params['params'];
                $delaySeconds = $params['delay_seconds'];
                $id = $value['id'];
                $this->dispatch($workerType,$data,$delaySeconds,$id);
            }
        }
    }

    /**
     * 根据worker type获取队列名
     * @param string $workerType        - worker type
     * @param string $env               - 环境
     * @return bool|string 成功返回队列名, 失败返回false
     */
    public function getQueueNameByWorkerType($workerType, $env = '')
    {
        if (empty($workerType)) {
            return false;
        }

        if (empty($env)) {
            $env = Config::getInstance()->get('env');
        }

        //获取worker配置
        $config = Config::getInstance()->get("workers.{$workerType}", 'worker');
        if (empty($config)) {
            Logger::error("worker config not found. workerType={$workerType}");
            return false;
        }

        $prefix = Config::getInstance()->get('jobNamePrefix', 'worker');
        $name = "{$env}-lwmWorker-{$prefix}{$config['jobName']}";
        return $name;
    }

    /**
     * 根据jobName获取队列名
     * @param string $jobName
     * @param string $env   - 环境名
     * @return string
     */
    public function getQueueNameByJobName($jobName, $env = '')
    {
        if (empty($env)) {
            $env = Config::getInstance()->get('env');
        }
        $prefix = Config::getInstance()->get('jobNamePrefix', 'worker');
        $name = "{$env}-lwmWorker-{$prefix}{$jobName}";
        return $name;
    }
}