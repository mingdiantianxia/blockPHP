<?php 
namespace fky\classs;


/**
 * Socket服务端
 */
class SocketServer
{
    protected $serv;

    /**
     * Socket Connection Resource
     *
     * @var resource
     */
    protected $socket;

    /**
     * Server Address
     *
     * @var string
     */
    protected $address;

    protected $port;

    /**
     * Stream Context
     *
     * @var resource
     */
    protected $context;
    public function __construct($address, $port)
    {
        $this->address = $address;
        $this->port = $port;

        //创建一个tcp socket服务
        $this->serv = stream_socket_server("tcp://{$this->address}:{$this->port}", $errno, $errstr);
        if (!$this->serv) {
            exit("{$errno} : {$errstr} \n");
        }

    }
    public function __destruct()
    {
        fclose($this->serv);
        if (is_resource($this->serv)) {
            return fclose($this->serv);
        }
    }
    /**
     * Get Server Address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }
    /**
     * Set Stream Context
     *
     * @param resource $context    A valid context resource created with stream_context_create()
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    public function start()
    {
        while (true) {
            $this->socket = stream_socket_accept($this->serv);

            if ($this->socket) {
                //读取
                $buf = $this->read();
                //解析客户端发送过来的协议
                $classRet = preg_match('/Rpc-Class:\s(.*);\r\n/i', $buf, $class);
                $methodRet = preg_match('/Rpc-Method:\s(.*);\r\n/i', $buf, $method);
                $paramsRet = preg_match('/Rpc-Params:\s(.*);\r\n/i', $buf, $params);

                //把运行后的结果返回给客户端
                $data = '测试测试测试';
                $this->write($data);
                //关闭客户端
                $this->close();
            }
        }
    }

    /**
     * Set Blocking Mode
     */
    public function set_blocking()
    {
        if (!$this->socket || !is_resource($this->socket)) return false;
        stream_set_blocking($this->socket, true);
        return true;
    }
    /**
     * Set Non-Blocking Mode
     */
    public function set_non_blocking()
    {
        if (!$this->socket || !is_resource($this->socket)) return false;
        stream_set_blocking($this->socket, false);
        return true;
    }
    /**
     * Send data
     *
     * @param string $packet
     * @param int    $packet_size
     * @return int
     */
    public function write($packet, $packet_size = 0)
    {
        if (!$this->socket || !is_resource($this->socket)) return false;
        return fwrite($this->socket, $packet, $packet_size);
    }
    /**
     * Read data
     *
     * @param int $length
     * @return string
     */
    public function read($length = 8192)
    {
        if (!$this->socket || !is_resource($this->socket)) return false;
        $string = "";
        $togo = $length;
        while (!feof($this->socket) && $togo>0) {
            $togo = $length - strlen($string);
            if($togo) $string .= fread($this->socket, $togo);
        }
        return $string;
    }
    /**
     * Close socket
     *
     * @return bool
     */
    public function close()
    {
        if (is_resource($this->socket)) {
            return fclose($this->socket);
        }
        return true;
    }
    /**
     * Is EOF
     *
     * @return bool
     */
    public function eof()
    {
        return feof($this->socket);
    }
    /**
     * stream_select
     *
     * @param int $timeout
     * @return int
     */
    public function select($timeout)
    {
        $read = array($this->socket);
        $write = $except = NULL;
        return stream_select($read, $write, $except, $timeout);
    }
}