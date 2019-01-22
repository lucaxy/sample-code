#!/bin/bash
cd /home/wwwroot/vod/
. ../hls/.env
exitAndLog(){
    echo `date '+%F %T'` $flv $mp4 "$1"
    exit 1
}

flv=$1/$2.flv
mp4=""
#skip streams
vodStreamNum=`echo $2|cut -d- -f1`
#if [ ${vodStreamNum:6} -gt 6 ];then
#    rm -f $flv
#    exitAndLog "skip store video"
#fi

if [ -e $flv ];then
    if [ ! -s $flv ];then
        rm -f $flv
        exit 0
    fi
#   transcode
    /usr/bin/ffmpeg -y -i $flv -movflags faststart -c copy $1/$2.mp4 &> /dev/null
    if [ $? -eq 0 ];then
        rm -f $flv
    else
        exitAndLog  "transcode fail"
    fi

    vodMonth=`echo $2|cut -d- -f3`
    vodStream=`echo $2|cut -d- -f1`
    if [ 1 ];then
#   save to oos
        mp4=$OOS_SAVE_BUCKET/$vodStream/$vodMonth/$2.mp4
        /usr/bin/s3cmd put -q --limit-rate=5m $1/$2.mp4 $mp4
        if [ $? -ne 0 ];then
            exitAndLog "put oos file failure"
        else
            rm -f $1/$2.mp4
#   post data
            token=`/usr/bin/php curl_token.php $2`
            /usr/bin/curl -s "http://www.xxx.com/VodController.php?action=create&token=$token"
        fi
    else
#   save to disk
        diskSavePath=$DISK_SAVE_PATH/$vodStream/$vodMonth
        mp4=$diskSavePath/$2.mp4
        if [ ! -e $diskSavePath ];then
            mkdir -p $diskSavePath
            if [ $? -ne 0 ];then
                exitAndLog  "mkdir $diskSavePath failure"
            fi
        fi
        mv $1/$2.mp4 $mp4
        if [ $? -ne 0 ];then
            exitAndLog "move disk file failure"
        fi
    fi
else
    exitAndLog "flv not exist"
fi
