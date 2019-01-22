<?php
$config=parse_ini_file('./.env');
$filename=authcode($_GET['token'],'DECODE',$config['VOD_MP4_KEY']);
if(!empty($filename)){
    $paramArr=explode('-',$filename);
//    $file=$config['DISK_SAVE_PATH'].'/'.$paramArr[0].'/'.$paramArr[2].'/'.$filename.'.mp4';
    if(true){
        $file=$config['OOS_MOUNT_PATH'].'/'.$paramArr[0].'/'.$paramArr[2].'/'.$filename.'.mp4';
    }
    if(!file_exists($file)){
        http_response_code(404);die;
    }
}else{
    http_response_code(403);die;
}
$fp=fopen($file,'r');
$size = filesize($file);
$begin = 0;
$end = $size - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    header('HTTP/1.1 206 Partial Content');
    if (preg_match('/bytes=\b(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
        $begin = intval($matches[1]);
        if (!empty($matches[2])) {
            $end = intval($matches[2]);
        }
    }
    header('Content-Range: bytes ' . $begin . '-' . $end . '/' . $size);
} else {
    header('HTTP/1.1 200 OK');
}

header('Cache-control: public');
header('Pragma: no-cache');
header('Accept-Ranges: bytes');
header('Content-Type: video/mp4');
header('Content-Transfer-Encoding: binary');
header('Content-Length:' . (($end - $begin) + 1));

fseek($fp, $begin);
$length=0;
while (!feof($fp)&&$length<=$end - $begin+1){
    echo fread($fp, 8192);
    ob_flush();
    flush();
    $length+=8192;
}
fclose($fp);
