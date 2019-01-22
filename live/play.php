<?php
require_once '.env';
define('RTMP_PUBLISH_KEY','xxxxxx');
$id=intval($_GET['id']);
$uid=$_SESSION['uid'];
$hasVideoRight=false;
if(is_numeric($uid)){
	//SQL查询判断权限
}
if(!$hasVideoRight||empty($id))die('请求错误！');
$videoPlayToken=authcode($omsId.'|'.$uid,'ENCODE','',600);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
	      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<link rel="stylesheet" href="/video-js-5.20.5/video-js.min.css">
	<link rel="stylesheet" href="/css/bootstrap.min.css">
	<!--[if lt IE 9]>
	<script type="text/javascript" src="/video-js-5.20.5/ie8/videojs-ie8.min.js"></script>
	<![endif]-->
	<script src="/video-js-5.20.5/video.min.js"></script>
    <script src="/video-js-5.20.5/videojs-contrib-hls-5.14.1.min.js"></script>
    <script src="/video-js-5.20.5/lang/zh-CN.js"></script>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 text-center">
            <h3>直播</h3>
        </div>
    </div>
	<div class="row">
		<div class="col-md-8 col-md-offset-2">
			<video id="live-stream-player" class="video-js vjs-fluid vjs-big-play-centered" controls preload="auto"
			        data-setup="{}">
                <source src="rtmp://www.xxx.com/live?action=play&token=<?=$videoPlayToken?>/stream<?=$id?>" type="rtmp/flv">
                <source src="//www.xxx.com/live/stream<?=$id?>.m3u8" type="application/x-mpegURL">
			</video>
		</div>
	</div>
</div>
<script>
    var liveStreamTech=['flash','h5'];
    if(window.location.search.indexOf('&tech=flash')!==-1){
	    liveStreamTech=['flash'];
    }else if(window.location.search.indexOf('&tech=h5')!==-1){
	    liveStreamTech=['h5'];
    }
    var h5Config=videojs.browser.IS_ANDROID?{
	    hls:{
		    overrideNative:true
	    },
	    nativeAudioTracks:false,
	    nativeVideoTracks:false
    }:{};
    var vjsPlayer=videojs('live-stream-player',{
    	h5:h5Config,
        'techOrder':liveStreamTech,
        'language':'zh-CN',
	    AudioTrackButton:true,
        notSupportedMessage:$('<p>FLASH被禁用或没有安装,<a href="http://www.macromedia.com/go/getflashplayer" target="_blank">点击安装或启用FLASH</a></p>')[0]
    },function () {
        if(navigator.userAgent.indexOf('Windows')!==-1){
            var newbtn = $('<div class="live-stream-menu vjs-icon-cog vjs-menu-button vjs-menu-button-popup vjs-control vjs-button" tabindex="0" role="menuitem" aria-live="polite" title="选择播放器" aria-disabled="false" aria-expanded="false" aria-haspopup="true"><div class="vjs-menu" role="presentation"><ul class="vjs-menu-content" role="menu"><li>使用h5播放</li><li>使用flash播放</li></ul></div><span class="vjs-control-text">选择播放器</span></div>')[0];
            var controlBar = document.getElementsByClassName('vjs-control-bar')[0];
            insertBeforeNode = document.getElementsByClassName('vjs-fullscreen-control')[0];
            controlBar.insertBefore(newbtn,insertBeforeNode);
            $('.live-stream-menu ul li').click(function (event) {
            	console.info(event.currentTarget.innerHTML.indexOf('flash'));
	            if(event.currentTarget.innerHTML.indexOf('flash')!==-1){
		            if(window.location.search.indexOf('&tech=')!==-1){
		            	if(window.location.search.indexOf('&tech=flash')!==-1){
				            window.location.reload();
                        }else{
				            window.location.href=window.location.search.replace('&tech=h5','&tech=flash');
                        }
		            }else{
			            window.location.href +='&tech=flash';
		            }
                }else{
		            if(window.location.search.indexOf('&tech=')!==-1){
			            if(window.location.search.indexOf('&tech=h5')!==-1){
				            window.location.reload();
			            }else{
				            window.location.href=window.location.search.replace('&tech=flash','&tech=h5');
			            }
		            }else{
			            window.location.href +='&tech=h5';
		            }
                }
            });
        }
    });
</script>
</body>
</html>
