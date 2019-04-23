#!/bin/bash
pidPath="../runtime/crond.pid"
phpbin="/usr/local/php/bin/php"
logFile="../runtime/crond.log"

#cd /data/wwwroot/free_mvc/fky/cli/
cd ../
cd cli
#crond server
stop() {
    if [ -f $pidPath ]; then
        pid=`cat $pidPath`
        echo "stop crond server, pid="$pid"..."

        check_crond_exist
        pidIsExits=$?
        if [ $pidIsExits -eq 1 ]; then
            kill $pid
        else
            echo "crond server not exist."
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
        echo "stop crond ok."
    fi
}

#启动crond server
start() {
    check_crond_exist
    pidIsExits=$?
    if [ $pidIsExits -eq 1 ]; then
        echo "crond server had running..."
    else
        echo "start crond server..."
        cmd=$phpbin" crond.php -d"
        $cmd &> $logFile
    fi
}

#检测crond进程是否存在
check_crond_exist() {
    if [ ! -f $pidPath ]; then
        return 0
    fi

    pid=`cat $pidPath`
    pids=`ps aux | grep crond.php | grep -v grep | awk '{print $2}'`
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

#当输入的参数为
case "$1" in
start)
    start
    ;;
stop)
    stop
    ;;
*)
    echo $"Usage: crond.sh {start|stop|help}"
    exit 1
esac
