<?php
return [
    "user" => 'www',
    "pid" => FKY_PROJECT_PATH .'/data/log/dbevent_worker.pid',
    "log" => FKY_PROJECT_PATH .'/data/log/dbevent_worker.log',
    //php命令路径
    "php" => "/usr/local/php/bin/php",
    //db event worker监听地址
    "listen" => [
        "ip" => "127.0.0.1",
//        "port" => 12359,
        "port" => 8181,
    ],
    //全量同步并发数
    "fullsyncWorkers" => 5,
    //监听器生存时间, 超时则重启
    "lifeTime" => 86400,
    //每个监听器最大任务处理数，超过则重启
    "maxHandleNum" =>  10000,
    "kafka" => [
        //kafka server list
        "addrs" => "172.16.81.52:9092,172.16.81.53:9092,172.16.81.54:9092",
        "binlogTopic" => "dev2-rds-binlog",
    ],
    //是否开启增量监听器
    "openListeners" => false,
    "listeners" => [
        //监听器配置
        /*"listenerId" => [
            //需要订阅的数据库表，格式:
            //         "数据库名" => ["表1", "表2", "表3"]
            "subscribe" => [
                "weixin" => ["wx_config","wx_admin"],
                "platform" => ["wx_config","wx_admin"],
            ],
            //并发数
            "workers" => 3,
            //监听器版本，主要用于灰度升级，灰度环境的版本号大于生产环境的版本号，则由灰度环境运行该监听器，反之由线上运行。
            "version" => 1,
            //批处理大小，一次性处理多少条事件
            "batchSize" => 128,
            //handler, 事件处理器 格式[service类型, '业务接口']
            //事件处理器，函数参数为：events数组
            //函数原型: public function handler(array $evs)
            //evs 参数格式:
            // [
            //    ["EvId" => "事件id", "TableName" => "表名", "Schema" => "数据库名", "PK" => "主键", "Action" => "事件类型，目前包括update，insert, delete", "Data" => [表字段数组]]
            // ]
            //处理成功返回true, 失败返回false, 失败会无限重试.
            "handler" => [SrvType::COMMON_HELPER, 'test']
        ],*/
        //会员数据同步
//        "member" => [
//            "subscribe" => [
//               "weixin" => ["wx_lewaimai_member"]
//            ],
//            "workers" => 4,
//            "version" => 1,
//            "batchSize" => 128,
//            "handler" => ['controllers\test\testController', 'test']
//        ],
        "test" => [
            "subscribe" => [
                "weixin" => ["wx_lewaimai_member"]
            ],
            "workers" => 4,
            "version" => 1,
            "batchSize" => 128,
            "handler" => ['controllers\worker\DbWorkerController', 'synchronizeDbData']
        ],
    ]
];