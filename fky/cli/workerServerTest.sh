#!/bin/bash
pidPath="../../data/log/workerServer.pid"
phpbin="/usr/local/php/bin/php"
logFile="../../data/log/workerServer.log"

cd /data/wwwroot/www/free_mvc/fky/cli/
#crond server
stop() {
    if [ -f $pidPath ]; then
        pid=`cat $pidPath`
        echo "stop worker server, pid="$pid"..."

        check_crond_exist
        pidIsExits=$?
        if [ $pidIsExits -eq 1 ]; then
            kill $pid
        else
            echo "worker server not exist."
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
        echo "stop workerServer ok."
    fi
}

#启动workerServer
start() {
    check_crond_exist
    pidIsExits=$?
    if [ $pidIsExits -eq 1 ]; then
        echo "workerServer server had running..."
    else
        echo "start workerServer server..."
        cmd=$phpbin" workerServerTest.php -d"
        $cmd &> $logFile
    fi
}

#检测workerServer进程是否存在
check_crond_exist() {
    if [ ! -f $pidPath ]; then
        return 0
    fi

    pid=`cat $pidPath`
    pids=`ps aux | grep workerServerTest.php | grep -v grep | awk '{print $2}'`
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
    echo $"Usage: workerServerTest.sh {start|stop|help}"
    exit 1
esac
