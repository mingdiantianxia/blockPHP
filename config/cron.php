<?php
/**
 * 定时任务
 */
return [
    "pid" => FKY_PROJECT_PATH .'/data/log/crond.pid',
    "log" => FKY_PROJECT_PATH .'/data/log/crond.log',
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
            'title' => '饭点提醒吃饭',
            'cron' => '0 0 11,12,18,19,21 * * *',
            'command' => 'test2 test',
        ],
        [
            'id' => 'cron_to_test2_test',
            'title' => '饭点提醒吃饭',
            'cron' => '0 30 9,18 * * *',
            'command' => 'test2 test',
        ],
        [
            'id' => 'cron_to_test2_test2',
            'title' => '30分钟活动提醒',
            'cron' => '0 */30 9-12,13-21, * * *',
            'command' => 'test2 test2',
        ],
    ]
];