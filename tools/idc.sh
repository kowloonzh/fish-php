#!/bin/bash

#线上集群列表 idc=>hostname
LINE_CLUSTER_LIST=(
    "dev=>"
)

#当前主机的ip列表
iplist=(`/sbin/ip add|grep inet|grep eth|sort -k 7|awk '{print $2}'|grep -o "\([0-9]*[0-9].\)\{3\}[0-9]*[0-9]"`)

#根据idc获取集群主机列表,支持多机房
function get_cluster()
{
    function do_get_cluster()
    {
        local key=$1;
        local number_of_elements=${#LINE_CLUSTER_LIST[@]}
        local index=0
        local element
        local array_key
        local array_value
        while [ "$index" -lt "$number_of_elements" ]
        do
            element=${LINE_CLUSTER_LIST[$index]}
            array_key=`echo $element | awk -F"=>" '{print $1}'`
            if [ "$array_key" == "$key" ];then
                array_value=`echo $element | awk -F"=>" '{print $2}'`
                echo "$array_value"
                break
            fi
            let "index += 1"
        done    
    }   
    for param in $*
    do  
        do_get_cluster $param
    done
}

#根据机器名获取所在机房
function get_idc()
{
    local number_of_elements=${#LINE_CLUSTER_LIST[@]}
    local index=0
    local element
    local array_key
    local array_value
    while [ "$index" -lt "$number_of_elements" ]
    do
        element=${LINE_CLUSTER_LIST[$index]}
        array_key=`echo $element | awk -F"=>" '{print $1}'`
        array_value=`echo $element | awk -F"=>" '{print $2}'`
        if [[ "$array_value" =~ "$HOSTNAME" ]];then
            echo $array_key
            return 0
        fi
        for ip in "${iplist[@]}"
        do
            if [[ "$array_value" =~ "$ip" ]];then
                echo $array_key
                return 0
            fi
        done
        let "index += 1"
    done    
    echo 'unknown'
    return 1
}
