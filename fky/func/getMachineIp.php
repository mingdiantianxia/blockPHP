<?php 
namespace fky\func;

/**
 * 获取当前机器IP
 * @return string
 */
function getMachineIp()
{
    static $ip = "";
    if (!$ip) {
        $ip = "0.0.0.0";
        try {
            //通过socket解析DNS来获取本机ip
            $socket = \socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            \socket_connect($socket, '8.8.8.8', 53);
            \socket_getsockname($socket, $addr, $por);
            \socket_close($socket);
            $ip = $addr;
        }catch (\Throwable $ex){
        }
    }
    return $ip;
}
