#!/bin/bash

# 指定要监控的进程的 PID 文件
PID_FILE="../../../../../public/version/AppVcs/appvcs.pid"

# 检查进程是否正在运行
check_process() {
    if ! kill -0 $(cat $PID_FILE) 2>/dev/null; then
        # 如果进程不在运行，重启它
        restart_process
    fi
}

# 重启进程
restart_process() {
    echo "Restarting process..."
    # 清除旧的 PID 文件
    rm -f $PID_FILE
    # 启动进程
    nohup /media/psf/wwwroot/tzkj/gentou/vendor/lcli/app-vcs/src/Cli/../../../../..//public/version/AppVcs/cron.sh > /dev/null 2>&1 &
    # 记录新进程的 PID
    echo $! > $PID_FILE
}

# 启动进程
restart_process

# 每隔一段时间检查一次进程状态
while true; do
    check_process
    sleep 60
done

