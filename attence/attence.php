<?php
require_once dirname(__FILE__).'/vendor/autoload.php';
use phpseclib\Crypt\AES;
use \Firebase\JWT\JWT;
ignore_user_abort(true);
set_time_limit(0);

class Attence{
    private $ATTENCE_KEY;
    
    public function __construct(){
        $config=parse_ini_file(dirname(dirname(__FILE__)).'/.env');
        $this->ATTENCE_KEY=$config['ATTENCE_KEY'];
    }
    
    //批量提交数据，不需要结果
    public function postMulti($postArr,$usleep=0){
        $error_code=$error_msg=[];
        foreach ($postArr as $i=>$va){
            $fp[$i] = pfsockopen('ssl://www.xxx.com','443',$error_code[$i],$error_msg[$i],30);
            if(!$fp[$i]) {
                $err[$i]=array('error_code' => $error_code[$i],'error_msg' => $error_msg[$i]);
            }else {
                $postStr=http_build_query([
                    'action'=>$va['action'],
                    'jwt'=>$va['jwt']
                ]);
                stream_set_blocking($fp[$i],true);
                stream_set_timeout($fp[$i],30);
    //            stream_set_write_buffer($fp[$i],0);
                $header = "POST / HTTP/1.1\r\n";
                $header.="Host: www.xxx.com\r\n";
                $header.="Content-Type: application/x-www-form-urlencoded\r\n";
                $header.="Content-Length: ".strlen($postStr)."\r\n";
                $header.="Connection: Keep-Alive\r\n\r\n";
                $header.=$postStr . "\r\n\r\n";
                $err[$i]['written']=[$this->kqFwriteStream($fp[$i], $header),strlen($header)];
                /* while(!feof($fp[$i])) {
                     echo fgets($fp[$i], 4096);
                 }*/
                if(!empty($usleep))
                    usleep($usleep);
    //            fclose($fp[$i]);
            }
        }
    }
    
    //确保写入完成
    private function kqFwriteStream($fp, $string) {
        for ($written = 0; $written < strlen($string); $written += $fwrite) {
            $fwrite = fwrite($fp, substr($string, $written));
            if ($fwrite === false) {
                return $written;
            }
        }
        return $written;
    }
    
    //获取数据
    public function postGetData($action,$data,$isRawData=false){
        if($isRawData){
            $jwtData=$data;
        }else{
            $jwtData=$this->encrypt($data);
        }
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://www.xxx.com/', [
            'form_params' => [
                'action' => $action,
                'jwt' => $jwtData
            ]
        ]);
        return $response->getBody();
    }
    
    //解密数据
    public function decrypt($jwt,$noAesDecode=false){
        $arr=(array)JWT::decode($jwt, $this->ATTENCE_KEY, array('HS256'));
        if($arr===false)
            return false;
        if($noAesDecode){
            return $arr;
        }else{
            return $this->aesDecode($arr['data']);
        }
    }
    
    
    //加密数据
    public function encrypt($data,$hasAesEncoded=false){
        if($hasAesEncoded){
            $postData=$data;
        }else{
            $postData=$this->aesEncode($data);
        }
        $jwtData = array(
            "iss" => "https://www.xxx.com",
            "aud" => "https://kq.xxx.com",
            "exp" => time()+72000,
            "data"=>$postData
        );
        return JWT::encode($jwtData, $this->ATTENCE_KEY);
    }
    
    private function aesDecode($dataStr){
        $cipher = new AES();
        $cipher->setKey($this->ATTENCE_KEY);
        $cipher->setIV($this->ATTENCE_KEY);
        return json_decode($cipher->decrypt(urlsafe_base64_decode($dataStr)),true);
    }
    
    private function aesEncode($dataArr){
        $cipher = new AES();
        $cipher->setKey($this->ATTENCE_KEY);
        $cipher->setIV($this->ATTENCE_KEY);
        $plainText = json_encode($dataArr,JSON_UNESCAPED_UNICODE);
        return urlsafe_base64_encode($cipher->encrypt($plainText));
    }
    
    //二进制编码
    private function encode($strArr,$binArr=[]){
        $result='';
        $str=json_encode($strArr,JSON_UNESCAPED_UNICODE);
        $strLen=strlen($str)+1;
        $result.=$this->setContentLen($strLen);
        $result.=$this->str2hex($str.chr(0));
        if(count($binArr)>0){
            foreach($binArr as $va){
                $result.=$this->setContentLen(strlen($va)/2);
                $result.=$va;
            }
        }
        return $result;
    }
    
    //二进制解码
    private function decode($rawBin){
        $rawBin=str_replace(chr(0),'',$raw);
        $totalLen=strlen($rawBin);
        $strLen=$this->getContentLen(substr($rawBin,0,8));
        $str=$this->hex2str(substr($rawBin,8,$strLen*2));
        $result['str']=$str;
        $result['json']=json_decode($str,true);
        $binHex=substr($rawBin,2*(4+$strLen));
        $result=array_merge($result,$this->getBinArr($binHex));
        return $result;
    }
    
    //获取16进制数组
    private function getBinArr($rawBinHex){
        $binHex=$rawBinHex;
        $binHexLen=strlen($binHex);
        $i=1;
        $result=[];
        while($binHexLen>0){
            $binLen=$this->getContentLen(substr($binHex,0,8));
            $binContent=substr($binHex,8,$binLen*2);
            $binHex=substr($binHex,2*(4+$binLen));
            $binHexLen=strlen($binHex);
            $result['BIN_'.$i]=$binContent;
            $i++;
        }
        return $result;
    }
    
    //16进制转换成字符串
    private function hex2str($hex){
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2){
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return trim($string);
    }
    
    //字符串换成16进制
    private function str2hex($str){
        $result='';
        for ($i=0; $i < strlen($str); $i++){
            $result .= sprintf('%02X',ord($str[$i]));
        }
        return $result;
    }
    
    //二进制编码获取字节长度
    private function getContentLen($hex){
        return hexdec(substr($hex,6,2).substr($hex,4,2).substr($hex,2,2).substr($hex,0,2));
    }
    
    //长度换成二进制编码
    private function setContentLen($len){
        $hex=sprintf('%08X',$len);
        return substr($hex,6,2).substr($hex,4,2).substr($hex,2,2).substr($hex,0,2);
    }
    
    //获取redis生成的唯一id
    public function getNewTransId(){
        $redis = new Redis();
        $redis->connect('127.0.0.1',6379);
        if(!$redis->exists('cmd_trans_id')){
            $redis->set('cmd_trans_id',10000);
        }
        return $redis->incr('cmd_trans_id');
    }
    
    //获取时间，到毫秒
    public function getMicroTimestamp(){
        $mtimestamp = sprintf("%.3f", microtime(true));
        $timestamp = floor($mtimestamp);
        $milliseconds = round(($mtimestamp - $timestamp) * 1000);
        return date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;
    }
}



