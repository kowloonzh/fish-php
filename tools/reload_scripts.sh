#!/bin/bash


ROOT=`pwd`

if [ "$ROOT" == "tools" ];then
    echo "Use this script under the root path of your project."
    exit 1
fi

# 优雅的杀掉常驻进程 /usr/local/bin/php index.php home/default/test
num=`ps aux|grep index.php|grep -v grep|grep -v lockf|awk '{print $2}'|wc -l`

if (( ${num} > 0 ));then
    kill -USR2 `ps aux|grep index.php|grep -v grep|grep -v lockf|awk '{print $2}'`
fi
