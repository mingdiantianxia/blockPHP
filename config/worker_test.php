<?php
use config\constants\WorkerTypes;
use lwm\services\SrvType;
/**
 * worker test配置
 *
 * 任务名
 * defaultJob - 默认任务队列，主要处理一些小任务
 */
return [
    //worker队列控制器目录
    'worker_path' => [
        //根目录下对应的文件夹
        'path' => 'controllers/test/',
        //对应的命名空间
        'namespace' => 'controllers\test',
        //后缀,如Controller
        'suffix' => 'Controller',
    ],
    "pid" => FKY_PROJECT_PATH .'/data/log/workerServer.pid',
    //php命令路径
    "php" => "/usr/local/php/bin/php",
    //进程运行角色
    "user"   => 'root',
    //当队列为空时候，获取消息时等待多少秒，范围限制：0-30秒
    "pollingWaitSeconds" => 30,
    //任务名前缀
    "jobNamePrefix" => 'fky',
    //worker配置
    "workerConf" => [
//      'jobName' => [
//            //worker获取消息后，多长时间内其他worker不能消费同一条消息，单位秒，最长12小时内
//           "visibilityTimeout" => 300,
//           //true则预先消费消息，worker获取消息后立即删除消息, false则任务执行返回为真才会删除消息
//           "preConsume" => false,
//           //当前任务并发执行的worker数量
//    		"threadNum" => 10,
//            //每个worker生存时间, 超时则重启
//    		"lifeTime" => 3600,
//            //每个worker最大任务处理数，超过则重启
//    		"maxHandleNum" =>  10000,
//       ],
        'defaultJob' => [
            "visibilityTimeout" => 300,
            "preConsume" => true,
            "threadNum" => 5,
            "lifeTime" => 3600,
            "maxHandleNum" =>  3,
        ],

    ],
    //worker任务配置
    "workers" => [
//    	WorkerType  => [
//    	    //任务名, 任务名相同则共用同一个消息队列
//    		"jobName" => "defaultJob",
//            //任务处理器, 格式[队列文件夹下的控制器名/或者带命名空间的类名, '方法名']
//    		"handler"    => ['\controllers\test\testController', 'test'],
//            //任务描述信息
//          "desc"  => '描述信息',
//    	],
    	'controller_console_test2_test'  => [
    	    //任务名, 任务名相同则共用同一个消息队列
    		"jobName" => "defaultJob",
            //任务处理器, 格式[队列文件夹下的控制器名/或者带命名空间的类名, '方法名']
    		"handler"    => ['controllers\worker\testController', 'test'],
            //任务描述信息
            "desc"  => '描述信息',
    	],
    ]
];