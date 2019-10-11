<?php
namespace fky\classs;
require_once __DIR__.'/../inc/sskaje/Autoloader.php';
use fky\classs\Config;

use sskaje\mqtt\MQTT;
use sskaje\mqtt\Debug;
use sskaje\mqtt\MessageHandler;
use sskaje\mqtt\Exception;
use sskaje\mqtt\Message;

/* phpMQTT */
class PhpMQTT {

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
            self::$_instance[$connectName] = new PhpMQTT($conf['host'], $conf['port'], $conf['client_id'],  $conf['username'], $conf['password']);
        }
        return self::$_instance[$connectName];
    }

    public function createClient($clientId = null, $cleanSession = true)
    {
        $this->broker = new MQTT($this->address.':'.$this->port, $clientId);
        $context = stream_context_create();
        $this->broker->setSocketContext($context);
        $this->broker->setConnectClean($cleanSession);
        $this->broker->setKeepalive($this->keepalive);
    }

    public function auth($username, $password)
    {
        $this->broker->setAuth($username, $password);
    }

    public function setConnectClean($cleanSession = false)
    {
        $this->broker->setConnectClean($cleanSession);
    }

    public function setKeepalive($keepalive = 10)
    {
        $this->broker->setKeepalive($keepalive);
    }

    public function setWill($topic, $message, $qos=0, $retain=0)
    {
        $this->broker->setWill($topic, $message, $qos, $retain);
    }

    /**
     * 设置回调。
     * @param $callbackArr
     * @throws \Exception
     */
    public function setCallback(array $callbackArr)
    {
        if (!is_array($callbackArr)) {
            throw new Exception('回调函数设置错误！', 500);
        }

        foreach ($callbackArr as $callbackName => $callback) {
            if(!is_callable($callback)){
                throw new Exception('回调函数设置错误！', 500);
            }
        }

        $callbackHandler = new MqttCallback();
        $callbackHandler->setCallback($callbackArr);
        $this->broker->setHandler($callbackHandler);
    }

    /**
     * 连接mqtt服务器
     */
    public function connect()
    {
        return $this->broker->connect();
    }

    public function reconnect($close_current=true)
    {
        return $this->broker->reconnect($close_current);
    }

    /**
     * Disconnect connection
     *
     * @return bool
     */
    public function disconnect()
    {
        return $this->broker->disconnect();
    }

    /**
     * @param array $topics ['topics' => qos]
     * @return mixed
     */
    public function subscribe(array $topics)
    {
        return $this->broker->subscribe($topics);
    }

    public function unsubscribe(array $topics)
    {
        return $this->broker->unsubscribe($topics);
    }

    public function publish($topic, $message, $qos=0, $retain=0, &$msgid=null)
    {
        return $this->broker->publish_async($topic, $message, $qos, $retain, $msgid);
    }

    public function setHandler(MessageHandler $handler)
    {
        $this->broker->setHandler($handler);
    }

    /**
     * 保持客户端连接
     */
    public function loop()
    {
        $this->broker->loop();
    }

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
     * @param $clientId -客户端id
     * @param $secretKey -mqtt AccessKeySecret
     * @param $accessKey -mqtt AccessKeyId
     * @param $instanceId -mqtt实例id
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


class MqttCallback extends MessageHandler
{
    private $callback = [];
    public function __construct(){
    }

    public function setCallback($callbackArr)
    {
        if (!is_array($callbackArr)) {
            throw new Exception('回调函数设置错误！', 500);
        }

        foreach ($callbackArr as $callbackName => $callback) {
            if(!is_callable($callback)){
                throw new Exception('回调函数设置错误！', 500);
            }
        }

        $this->callback = $callbackArr;
    }

    //设置消息回调
    public function publish(MQTT $mqtt, Message\PUBLISH $publish_object)
    {
//        printf(
//            "\e[32mI got a message\e[0m:(msgid=%d, QoS=%d, dup=%d, topic=%s) \e[32m%s\e[0m\n",
//            $publish_object->getMsgID(),
//            $publish_object->getQoS(),
//            $publish_object->getDup(),
//            $publish_object->getTopic(),
//            $publish_object->getMessage()
//        );

        if(isset($this->callback['message']) && is_callable($this->callback['message'])){
            if (is_object($this->callback['message'])) { //函数对象，直接调用
                $callback = $this->callback['message'];
                $callback($publish_object->getTopic(),$publish_object->getMessage(),$publish_object->getMsgID(),$publish_object->getQoS(),$mqtt);
            } else {
                call_user_func($this->callback['message'],$publish_object->getTopic(),$publish_object->getMessage(),$publish_object->getMsgID(),$publish_object->getQoS(),$mqtt);
            }
        }
    }


    public function connack(MQTT $mqtt, Message\CONNACK $connack_object)
    {
        if(isset($this->callback['connect']) && is_callable($this->callback['connect'])){
            if (is_object($this->callback['connect'])) { //函数对象，直接调用
                $callback = $this->callback['connect'];
                $callback($connack_object->getMsgID(),$mqtt);
            } else {
                call_user_func($this->callback['connect'],$connack_object->getMsgID(),$mqtt);
            }
        }
    }

    public function disconnect(MQTT $mqtt)
    {
        if(isset($this->callback['disconnect']) && is_callable($this->callback['disconnect'])){
            if (is_object($this->callback['disconnect'])) { //函数对象，直接调用
                $callback = $this->callback['disconnect'];
                $callback($mqtt);
            } else {
                call_user_func($this->callback['disconnect'],$mqtt);
            }
        }
    }

    public function suback(MQTT $mqtt, Message\SUBACK $suback_object)
    {
        if(isset($this->callback['subscribe']) && is_callable($this->callback['subscribe'])){
            if (is_object($this->callback['subscribe'])) { //函数对象，直接调用
                $callback = $this->callback['subscribe'];
                $callback($suback_object->getReturnCodes(),$mqtt);
            } else {
                call_user_func($this->callback['subscribe'],$suback_object->getReturnCodes(),$mqtt);
            }
        }
    }

    public function unsuback(MQTT $mqtt, Message\UNSUBACK $unsuback_object)
    {
        if(isset($this->callback['unsubscribe']) && is_callable($this->callback['unsubscribe'])){
            if (is_object($this->callback['unsubscribe'])) { //函数对象，直接调用
                $callback = $this->callback['unsubscribe'];
                $callback($unsuback_object->getMsgID(),$mqtt);
            } else {
                call_user_func($this->callback['unsubscribe'],$unsuback_object->getMsgID(),$mqtt);
            }
        }
    }

    public function pubcomp(MQTT $mqtt, Message\PUBCOMP $pubcomp_object)
    {
        if(isset($this->callback['publish']) && is_callable($this->callback['publish'])){
            if (is_object($this->callback['publish'])) { //函数对象，直接调用
                $callback = $this->callback['publish'];
                $callback($pubcomp_object->getMsgID(),$mqtt);
            } else {
                call_user_func($this->callback['publish'],$pubcomp_object->getMsgID(),$mqtt);
            }
        }

    }
}
