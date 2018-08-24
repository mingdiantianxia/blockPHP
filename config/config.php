<?php 
return [
    'db'=> [
            'database_type' => 'mysql',
            'database_name' => 'test',
            'server' => 'localhost',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
             // 可选参数
            'port' => 3306,
            // 可选，定义表的前缀
            // 'prefix' => 'wx_',
        ],
        
    'redis' => [
            //redis服务器地址
            'host'  => '127.0.0.1',
            //redis端口
            'port'  => '6379',
            //redis密码
            'password' => '',
            //连接超时
            'timeout' => 3,
            //持久化链接
            'persistent' => true,
    ],
];
