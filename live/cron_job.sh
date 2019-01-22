#!/bin/bash
cd /home/wwwroot/vod/
. ../hls/.env

writeLog(){
    echo `date '+%F %T'` "$1"
}

delDiskMp4(){
    vodMonth=`echo $1 | cut -d- -f3`
    vodStream=$(echo $1 | cut -d- -f1)
    filePath=$DISK_SAVE_PATH/$vodStream/$vodMonth/$1.mp4
    if [ -e $filePath ];then
        rm -f $filePath
        if [ $? -ne 0 ];then
            writeLog "delete file failure $filePath"
        fi
    else
        writeLog "file not exists $filePath"
    fi
}

delOOSMp4(){
    vodMonth=`echo $1 | cut -d- -f3`
    vodStream=$(echo $1 | cut -d- -f1)
    if [ ${vodStream:0:5}m == "stream" ];then
        /usr/bin/s3cmd rm -q $OOS_SAVE_BUCKET/$vodStream/$vodMonth/$1.mp4
    else
        writeLog "OOS rm $1 failure"
    fi
}

deleteByIPC(){
    token=$(/usr/bin/php curl_token.php $1-$2)
    mp4Arr=$(/usr/bin/curl -s "http://www.xxx.com/deleteByIPC?token=$token")
    if [ ${#mp4Arr} -gt 2 ];then
        if [ ${mp4Arr:0:5}m == "stream" ];then
            for mp4 in $mp4Arr; do
                delOOSMp4 $mp4
            done
        else
            writeLog "deleteByIPC $1-$2 wrong response $mp4Arr"
        fi
    fi
}

fetchSaveDays(){
    token=$(/usr/bin/php curl_token.php fetchSaveDays)
    daysEnv=$(/usr/bin/curl -s "http://www.xxx.com/fetchSaveDays?token=$token")
    if [ ${#daysEnv} -gt 18 ];then
        if [ ${daysEnv:0:18}VERSION == "VOD_SAVE_DAYS_VERSION" ];then
            new_env_days=save_days_`date +%Y%m%d`.conf
            echo $daysEnv > $new_env_days
            ln -sf $new_env_days save_days.conf
            old_env_file=save_days_`date -d '-7 days' +%Y%m%d`.conf
            if [ -e $old_env_file ];then
                rm -f $old_env_file
            fi
        else
            writeLog "fetchSaveDays wrong response $daysEnv"
        fi
    fi
}

updateRecordIPC(){
    resultStr='RECORD_IPC=( '
    for k in ${!RECORD_IPC[*]};do
        resultStr+="[$k]=${RECORD_IPC[$k]} "
    done
    resultStr+=")"
    echo $resultStr > record_ipc.conf
}
fetchSaveDays
source save_days.conf
source record_ipc.conf
if [ ! -z $VOD_SAVE_DAYS_VERSION ];then
    for ipc in ${!VOD_SAVE_DAYS[*]};do
        if [ ${VOD_SAVE_DAYS[$ipc]} -ne -1 ];then
            if [ ${VOD_SAVE_DAYS[$ipc]} -eq 0 ];then
                #stop record
                if [ ! -z ${RECORD_IPC[$ipc]} ];then
                    unset RECORD_IPC[$ipc]
                    updateRecordIPC
                    /usr/bin/curl -s "https://127.0.0.1/control/record/stop?app=live&name=stream$ipc&rec=rec1" > /dev/null 2>> cron_job.log
                    deleteByIPC $(date +%s) $ipc
                fi
            else
            #start record
                if [ -z ${RECORD_IPC[$ipc]} ];then
                    RECORD_IPC[$ipc]=$(($RANDOM%30+1))
                    /usr/bin/curl -s "https://127.0.0.1/control/record/start?app=live&name=stream$ipc&rec=rec1" > /dev/null 2>> cron_job.log
                fi
                time=`date -d "-${VOD_SAVE_DAYS[$ipc]} days" +%s`
                deleteByIPC $time $ipc
            fi
        else
            #start record
            if [ -z ${RECORD_IPC[$ipc]} ];then
                RECORD_IPC[$ipc]=$(($RANDOM%30+1))
                /usr/bin/curl -s "https://127.0.0.1/control/record/start?app=live&name=stream$ipc&rec=rec1" > /dev/null 2>> cron_job.log
            fi
        fi
    done
    updateRecordIPC
fi
