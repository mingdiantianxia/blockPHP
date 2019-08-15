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
        $this->_conf = loadc('config')->get("", "mns");
        $aliConfig = new AlyConfig();
        $this->_client = new Client($this->_conf['endPoint'], $this->_conf['accessKeyId'], $this->_conf['accessSecret']);
        
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
            loadc('log')->error("create message queue failure. queue={$queueName}, mns code={$e->getMnsErrorCode()}, msg={$e->getMessage()}");
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
            loadc('log')->error("set message queue attr failure. queue={$queueName}, mns code={$e->getMnsErrorCode()}, msg={$e->getMessage()}");
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
            loadc('log')->error("send message failure. queue={$queueName}, mns code={$e->getMnsErrorCode()}, msg={$e->getMessage()}");
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
            loadc('log')->error("receive message failure. queue={$queueName}, mns code={$e->getMnsErrorCode()}, msg={$e->getMessage()}");
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
            loadc('log')->error("change message visibility failure. queue={$queueName}, msg={$e->getMessage()}");
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
            loadc('log')->error("delete message failure. queue={$queueName}, mns code={$e->getMnsErrorCode()}, msg={$e->getMessage()}");
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
    public function dispatch($workerType, array $params = [], $delaySeconds = null)
    {
        //dev环境不走消息队列, 直接调用消息处理器
        if (loadc('config')->get("env") == 'dev') {
            $workerConfig = null;
            try {
                $workerConfig = Config::getInstance()->get("workers.{$workerType}", 'worker');
            } catch (\Exception $e) {
                throw new \Exception("worker config not found, workerType={$workerType}");
            }

            if (!isset($workerConfig['handler']) || empty($workerConfig['handler'])) {
                throw new \Exception("worker config invalid, workerType={$workerType}");
            }

            $handler = $workerConfig['handler'];

            //添加worker控制器目录(用于加载和调用)
            $worker_path = loadc('config')->get("worker_path", "worker");
            LoadFactory::setClassDir(['dir' => $worker_path['path'], 'suffix' => $worker_path['suffix']]);
            //选中控制器目录
            LoadFactory::setDirMatchedStr($worker_path['path']);
            $srvObj = LoadFactory::lc($handler[0]);//单例实例化类
            $ret = call_user_func_array([$srvObj, $handler[1]], $params);
            if (!empty($ret)) {
                return true;
            }
            return false;
        }

        $env = loadc('config')->get("env");
        $queueName = $this->getQueueNameByWorkerType($workerType, $env);
        if (empty($queueName)) {
            return false;
        }

        $msg = new WorkerMessage();
        $msg->setWorkerType($workerType);
        $msg->setParams($params);
        $ret = $this->send($queueName, $msg->serialize(), $delaySeconds);
        if ($ret !== false) {
            return true;
        }
        return false;
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
            $env = loadc('config')->get("env");
        }

        //获取worker配置
        $config = loadc('config')->get("workers.{$workerType}", 'worker');
        if (empty($config)) {
            loadc('log')->error("worker config not found. workerType={$workerType}");
            return false;
        }

        $prefix = loadc('config')->get('jobNamePrefix', 'worker');
        $name = "{$env}-fkyWorker-{$prefix}{$config['jobName']}";
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
            $env = loadc('config')->get("env");
        }
        $prefix = loadc('config')->get('jobNamePrefix', 'worker');
        $name = "{$env}-fkyWorker-{$prefix}{$jobName}";
        return $name;
    }
}