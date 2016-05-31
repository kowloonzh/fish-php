#!/bin/bash


ROOT=`pwd`

if [ "$ROOT" == "tools" ];then
    echo "Use this script under the root path of your project."
    exit 1
fi

#更新src/task目录下.signal开头的文件内容
genUpdateFile()
{
    for file in `ls -A "$ROOT/src/task"`
    do 
        filename=$ROOT/src/task/$file
        if [ "${file:0:7}" == ".signal" ];then
            echo "update" > $filename
            echo -e "Update script $file ok ...\n"
        fi
    done
}

genUpdateFile