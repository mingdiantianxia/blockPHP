<?php
namespace fky\func;

/**
 * 脚本常用函数
 */

/**
 * 安装定时任务
 */
function installCrontab()
{
    global $crontab;

    $cronPath = '/etc/crontab';
    if (!is_readable($cronPath) || !is_writable($cronPath)) {
        echo "需要root权限.\n";
        return;
    }

    $cron = file_get_contents($cronPath);

    //如果已经存在历史配置则替换
    $beginPos = strpos($cron, '#fky_cron_begin');
    if (false !== $beginPos) {
        $cron = substr($cron, 0, $beginPos); //删掉历史配置
    }

    $cron .= "\n#fky_cron_begin - 由此开始作为定时任务配置\n{$crontab}\n";
    file_put_contents($cronPath, $cron);

    if (empty($crontab)) {
        echo "卸载定时任务成功.\n";
    } else {
        echo "安装定时任务成功.\n";
        echo "定时任务为:\n";
        echo $crontab ."\n";
    }

}