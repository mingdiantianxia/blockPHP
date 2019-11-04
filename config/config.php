<?php 
return [
    'env' => 'dev',//环境名 dev开发，test测试，prod正式

    'db'=> [
            'database_type' => 'mysql',
            'database_name' => 'test',
//            'server' => 'localhost',
//            'username' => 'root',
            'password' => '123456',
            'charset' => 'utf8',
             // 可选参数
            'port' => 3306,
            // 可选，定义表的前缀
             'prefix' => '',
        ],
        
    'redis' => [
            //redis服务器地址
            'host'  => '127.0.0.1',
            //redis端口
            'port'  => '6379',
            //redis密码
            'password' => '123456',
//            'password' => '',
            //连接超时
            'timeout' => 3,
            //持久化链接
            'persistent' => true,
    ],

    'cmd_path' => [
            //根目录下对应的命令文件夹
            'path'  => 'controllers/console/',
            //对应的命名空间
            'namespace'  => 'controllers\console',
    ],
];
