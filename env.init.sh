#!/bin/sh
## App Env Init Script


DIRS=""
EXECUTES="tools/reload_scripts.sh"
APPS="web"
APP_NAME=${PWD##*/}


#if test $# -lt 1
#then
#    echo Usage: env.init.sh who
#    echo    eg: env.init.sh cc
#    exit
#fi

ROOT=`pwd`
USR=${1:-""}

#导入机房与主机名的配置信息
. $ROOT"/tools/"idc.sh

if [ "$USR" == '' ]
then
    USR=$(get_idc)
fi

echo create application environment for $USR

# link app config file
cd $ROOT/config

#处理nginx_conf

    if test -e nginx_conf.php
    then
        rm nginx_conf.php
    fi
    #如果nginx配置文件不存在,则创建一个
    nginx_conf_file=nginx/nginx_conf_$USR.php
    if [ ! -e $nginx_conf_file ];then
        cp nginx/nginx_conf_demo.php $nginx_conf_file
        sed -i -e "s/{usr}/$USR/g;s#{root}#$ROOT#g" $nginx_conf_file
    fi

    if (test -s $nginx_conf_file)
    then
        ln -s $nginx_conf_file nginx_conf.php
        echo link -s nginx_conf_file ........... OK
    else
        echo link -s nginx_conf_file  ........... Fail
    fi


#应用配置
for APP in $APPS
do
    #删除软连
    if test -e "$APP".php
    then
        rm "$APP".php
    fi

    #如果应用配置文件不存在,则创建
    appfile=common/"$APP"_$USR.php
    if [ ! -e $appfile ];then
        echo -e "<?php\n   return require('${APP}_dev.php');\n?>" > $appfile
    fi

    if (test -s $appfile)
    then
        ln -s common/"$APP"_$USR.php "$APP".php
        echo link -s "$APP".php ........... OK
    else
        echo link -s "$APP".php ........... Fail
    fi
done

cd $ROOT


for dir in $DIRS
do
    if (test ! -d $dir)
    then
        mkdir -p $dir
        chmod -R 777 $dir
        echo mkdir $dir ................ OK
    fi
done


#创建日志目录
if [ "$USR" != "prod" ];then
    if (test ! -d logs)
    then
        mkdir -p logs
        chmod -R 777 logs
        echo mkdir logs ................ OK
    fi
else
    logdir=/data/nginx/logs/"$APP_NAME"
    if [ ! -e $logdir ];then
        echo link -s logs .................. Fail
    else
        ln -s $logdir logs
        echo link -s logs .................. OK
    fi
fi

for execute in $EXECUTES
do
    sh $execute > /dev/null
    if test $? -eq 0
    then
        echo "sh $execute ................ OK"
    fi
done

