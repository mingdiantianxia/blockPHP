#!/bin/bash
pidPath="../../data/log/dbevent_worker.pid"
phpbin="/usr/local/php/bin/php"
logFile="../../data/log/dbevent_worker.log"

cd /data/wwwroot/www/free_mvc/fky/cli/
#crond server
stop() {
    if [ -f $pidPath ]; then
        pid=`cat $pidPath`
        echo "stop dbEventWorker server, pid="$pid"..."

        check_crond_exist
        pidIsExits=$?
        if [ $pidIsExits -eq 1 ]; then
            kill $pid
        else
            echo "worker dbEventWorker not exist."
            rm -f $pidPath
        fi


        try=0
        while test $try -lt 60; do
            if [ ! -f "$pidPath" ]; then
                try=''
                break
            fi
            echo -n
            try=`expr $try + 1`
            sleep 1
        done
        echo "stop dbEventWorker ok."
    fi
}

#启动dbEventWorker
start() {
    check_crond_exist
    pidIsExits=$?
    if [ $pidIsExits -eq 1 ]; then
        echo "dbEventWorker server had running..."
    else
        echo "start dbEventWorker server..."
        cmd=$phpbin" dbEventWorker.php -d"
        $cmd &> $logFile
    fi
}

#检测dbEventWorker进程是否存在
check_crond_exist() {
    if [ ! -f $pidPath ]; then
        return 0
    fi

    pid=`cat $pidPath`
    pids=`ps aux | grep dbEventWorker.php | grep -v grep | awk '{print $2}'`
    pidIsExits=0;
    for i in ${pids[@]}
        do
            if [ "$i" -eq "$pid" ]; then
                pidIsExits=1
                break
            fi

        done
    return  $pidIsExits
}


case "$1" in
start)
    start
    ;;
stop)
    stop
    ;;
*)
    echo $"Usage: dbEventWorker.sh {start|stop|help}"
    exit 1
esac
