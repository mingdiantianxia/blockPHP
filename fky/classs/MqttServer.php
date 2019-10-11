<?php
namespace fky\classs;
use Mosquitto\Client;
use Mosquitto\Exception;
/**
 * MQTT客户端 基于php Mosquitto扩展
 * Class MqttServer
 * @package fky\classs
 */
class MqttServer
{
    private $broker;
    public $keepalive = 60; //在没有收到消息的情况下，服务器应该ping客户端的部分数量。
    public $address;
    public $port;
    public $clientid;
    private $username;
    private $password;

    private static $_instance = [];

    public function __construct($address, $port, $clientid, $username = NUll, $password = NULL){
        $this->address = $address;
        $this->port = $port;
        $this->clientid = $clientid;

        if($username) $this->username = $username;
        if($password) $this->password = $password;

        $this->createClient($this->clientid);
        if ($username && $password) {
            $this->auth($username, $password);
        }
    }

    public static function getInstance($connectName = 'mqtt')
    {
        if (empty($connectName)) {
            return false;
        }

        if (!isset(self::$_instance[$connectName])) {
            $conf = Config::getInstance()->get($connectName, 'mqtt');
            self::$_instance[$connectName] = new MqttServer($conf['host'], $conf['port'], $conf['client_id'], $conf['username'], $conf['password']);
        }
        return self::$_instance[$connectName];
    }

    /**
     * 创建mqtt客户端,必须首先调用.
     * @param null $clientId -客户端ID,如果省略或者为null,会随机生成一个。
     * @param bool $cleanSession 如果设为false ，当 client 断开连接后，broker 会保留该 client 的订阅和消息，直到再次连接成功；如果设为 true（默认） ，client 断开连接后，broker 会将所有的订阅和消息删除。
     */
    public function createClient($clientId = null, $cleanSession = true)
    {
        $this->broker = new Client($clientId, $cleanSession);
    }

    /**
     * 设置客户端账号密码,必须在connect之前调用.
     * @param $username
     * @param $password
     */
    public function auth($username, $password)
    {
        $this->broker->setCredentials($username, $password);
    }


    /**
     * 设置“遗嘱消息”，当 broker 检测到网络故障、客户端异常等问题，需要关闭某个客户端的连接时，向该客户端发布一条消息。必须在connect之前调用.
     * @param $topic (string) – 发表遗嘱消息的主题。
     * @param $content(string) – 要发送的数据。
     * @param int $qos(int) –可选。服务质量。默认值为0.整数0,1或2。
     * @param bool $retain(boolean) – 可选。默认为false。设置为true，则该消息将被保留。
     */
    public function setWill($topic, $content, $qos = 0, $retain = false)
    {
        $this->broker->setWill($topic, $content, $qos, $retain);
    }

    /**
     * 删除之前设置遗嘱消息。没有参数。
     */
    public function clearWill()
    {
        $this->broker->clearWill();
    }

    /**
     * 设置连接回调。必须loopForever()持续连接，才能接收回调
     * @param $callback(callable)– 回调函数 function($rc,$message){} $rc (int)– 来自服务器的响应代码。$message (string)– 响应代码的字符串描述。
     * @return bool
     */
    public function onConnect($callback)
    {
        //不是函数对象
        if(!is_callable($callback) || !is_object($callback)){
            throw new Exception('回调函数设置错误！', 500);
        }
        $this->broker->onConnect($callback);
    }

    /**
     * 设置断开连接回调。当服务器收到断开连接命令并断开客户端连接时，会调用此命令。必须loopForever()持续连接，才能接收回调
     * @param $callback(callable)– 回调函数 function($rc){} $rc (int) – 断开的原因。0表示客户端请求断开。其他任何值表示意外断开连接。
     * @return bool
     */
    public function onDisconnect($callback)
    {
        //不是函数对象
        if(!is_callable($callback) || !is_object($callback)){
            throw new Exception('回调函数设置错误！', 500);
        }
        $this->broker->onDisconnect($callback);
    }

    /**
     * 设置日志记录回调。必须loopForever()持续连接，才能接收回调
     * @param $callback(callable)– 回调函数 function($level,$str){} $level (int) – 日志消息级别  $str (string) – 消息字符串。
     * 日志级别可以是以下之一：
        Client::LOG_DEBUG
        Client::LOG_INFO
        Client::LOG_NOTICE
        Client::LOG_WARNING
        Client::LOG_ERR
     * @return bool
     */
    public function onLog($callback)
    {
        //不是函数对象
        if(!is_callable($callback) || !is_object($callback)){
            throw new Exception('回调函数设置错误！', 500);
        }
        $this->broker->onLog($callback);
    }

    /**
     * 设置订阅回调，必须loopForever()持续连接，才能接收回调
     * @param $callback(callable)– 回调函数 function($mid,$qosCount){} $mid (int) – 订阅消息的消息ID。 $qosCount (int) – 授予订阅的数量。
     * @return bool
     */
    public function onSubscribe($callback)
    {
        //不是函数对象
        if(!is_callable($callback) || !is_object($callback)){
            throw new Exception('回调函数设置错误！', 500);
        }
        $this->broker->onSubscribe($callback);
    }

    /**
     * 设置取消订阅回调，必须loopForever()持续连接，才能接收回调
     * @param $callback(callable)– 回调函数 function($mid){} $mid (int) – 取消订阅消息的消息ID
     * @return bool
     */
    public function onUnsubscribe($callback)
    {
        //不是函数对象
        if(!is_callable($callback) || !is_object($callback)){
            throw new Exception('回调函数设置错误！', 500);
        }
        $this->broker->onUnsubscribe($callback);
    }

    /**
     * 设置消息回调。收到从服务器返回的消息时调用,处理订阅的消息和点到点消息。，必须loopForever()持续连接，才能接收回调
     * @param $callback(callable)– 回调函数 function($message){} $message消息对象 主题$message->topic 质量$message->qos 消息ID:$message->mid 内容$message->payload 等
     * @return bool
     */
    public function onMessage($callback)
    {
        //不是函数对象
        if(!is_callable($callback) || !is_object($callback)){
            throw new Exception('回调函数设置错误！', 500);
        }
        $this->broker->onMessage($callback);
    }

    /**
     * 设置发布回调。，必须loopForever()持续连接，才能接收回调
     * 这可能会在publish之前调用返回消息ID，所以，最好需要创建一个队列来处理中间列表。
     * @param $callback(callable)– 回调函数 function($mid){} $mid (int) – 消息的消息ID
     * @return bool
     */
    public function onPublish($callback)
    {
        //不是函数对象
        if(!is_callable($callback) || !is_object($callback)){
            throw new Exception('回调函数设置错误！', 500);
        }
        $this->broker->onPublish($callback);
    }

    /**
     * 连接mqtt服务器
     */
    public function connect()
    {
        return $this->broker->connect($this->address, $this->port, $this->keepalive);
    }

    /**
     * 断开mqtt服务器连接
     */
    public function disconnect()
    {
        $this->broker->disconnect();
    }

    /**
     * 发布主题消息。
     * @param $topic(string) – 要发表的主题
     * @param $content(string) – 消息体
     * @param int $qos(int) – 服务质量，值0,``1或2
     * @param bool $retain (boolean) – 是否保留此消息，默认为false
     * return 服务器返回该消息ID（警告：消息ID并不是唯一的）。
     */
    public function publish($topic, $content, $qos = 0, $retain = false)
    {
        return $this->broker->publish($topic, $content, $qos, $retain);
    }

    /**
     * 订阅一个主题。
     * @param $topic (string) – 要订阅的主题。
     * @param $qos (int) – 服务质量
     * @return int 订阅消息的消息ID，所以这可以在onsubscribe回调中匹配。
     */
    public function subscribe($topic, $qos)
    {
        return $this->broker->subscribe($topic, $qos);
    }

    /**
     * 取消订阅。
     * @param $topic (string) – 主题。
     * @param $qos (int) – 服务质量
     * @return int 订阅消息的消息ID，所以这可以在onsubscribe回调中匹配。
     */
    public function unsubscribe($topic, $qos)
    {
        return $this->broker->unsubscribe($topic, $qos);
    }

    /**
     *
     * 客户端主网络循环，必须调用该函数来保持 client 和 broker 之间的通讯。收到或者发送消息时，它会调用相应的回调函数处理。当 QoS>0 时，它还会尝试重发消息。
     * @param int $timeout (int) – 可选。 等待网络活动的毫秒数。传递0即时超时。默认为1000。
     * @return mixed
     */
    public function loop($timeout = null)
    {
        if ($timeout) {
            $this->broker->loop($timeout);
        } else {
            $this->broker->loop();
        }
    }

    /**
     * 保持客户端的持续连接
     * 在无限的阻塞循环调用loop()，将根据需要调用回调。这将处理重新连接，如果连接丢失。调用disconnect在回调断开，并从循环中返回。或者，调用exitloop退出循环而不断开。您将需要再次重新进入循环以保持连接。
     * @param int $timeout (int) – 可选。 等待网络活动的毫秒数。传递0即时超时。默认为1000。
     * @return mixed
     */
    public function loopForever($timeout = null)
    {
        if ($timeout) {
            $this->broker->loopForever($timeout);
        } else {
            $this->broker->loopForever();
        }
    }

    /**
     * 退出loopforever事件循环而不断开连接。你将需要重新进入循环以保持连接。
     * @return mixed
     */
    public function exitLoop()
    {
        $this->broker->exitLoop();
    }

    /**
     * 获取客户端实例
     * @return mixed
     */
    public function getBroker()
    {
        return $this->broker;
    }

    /**
     * 生成MQTT账号密码
     * @param $clientId -客户端id 组id@@@自定义设备id
     * @param $secretKey -阿里云帐号 SecretKey
     * @param $accessKey -阿里云帐号 AccessKey
     * @param $instanceId -mqtt实例id 购买后从控制台获取
     * @return array
     */
    public static function getClientPassword($clientId, $secretKey, $accessKey, $instanceId)
    {
        ## 设置鉴权参数，参考 MQTT 客户端鉴权代码计算 username 和 password
        $username = 'Signature|'.$accessKey.'|'.$instanceId;
        $sigStr = hash_hmac("sha1", $clientId, $secretKey, true);
        $password = base64_encode($sigStr);

        return ['clientId' => $clientId,'username' => $username, 'password' => $password];
    }

}