<?php
/**
 * 定时任务
 */
return [
    "pid" => FKY_PROJECT_PATH .'/runtime/crond.pid',
    //php命令路径
    "php" => "",
    //进程运行角色
    "user"   => 'www',
    //定时任务
    'jobs' => [
//        [
//            'id' => 'test_job1',
//            'title' => '测试任务',
//             //定时配置，相对于linux的crontab, 系统支持精确到秒，第一位就是秒的配置，格式跟系统的crontab配置一样
//            'cron' => '* * * * * *',
//            'command' => 'system version',
//        ],

        [
            'id' => 'cron_push_benchmark',
            'title' => '每分钟上报一次性能监控数据',
            'cron' => '0 */1 * * * *',
            'command' => 'statis push',
        ],


    ]
];