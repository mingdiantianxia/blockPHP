<?php
/**
 * 定时任务
 */
return [
    "pid" => FKY_PROJECT_PATH .'/data/log/crond.pid',
    //php命令路径
    "php" => "/usr/local/php/bin/php",
    //进程运行角色
    "user"   => 'root',
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
            'id' => 'cron_test2_test',
            'title' => '每秒钟执行一次测试任务',
            'cron' => '*/1 * * * * *',
            'command' => 'test2 test',
        ],


    ]
];