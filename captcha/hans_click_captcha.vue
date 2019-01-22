<template>
    <div>
        <Form-item v-if="captchaType=='slide'">
            <div id="geetestCaptcha">
                <p id="geetestWait" class="geetest-show">正在加载验证码......</p>
            </div>
        </Form-item>
        <Form-item v-if="captchaType=='img'">
            <Row type="flex" align="middle">
                <Col span="8">
                <Input type="text" v-model="formItem.captcha" @input="updateCaptchaValue" placeholder="验证码">
                <Icon type="ios-barcode-outline" slot="prepend"></Icon>
                </Input>
                </Col>
                <Col span="12" style="text-align: center;height: 40px;">
                <img :src="captchaImg" alt="验证码" title="点击刷新！" @click="fetchNewCaptchaImg">
                </Col>
                <Col span="4"><Button type="ghost" shape="circle" icon="android-refresh" @click="fetchNewCaptchaImg"></Button></Col>
            </Row>
        </Form-item>
        <Form-item v-if="captchaType==='click'">
            <Row>
                <Col span="24">
                <div :style="{backgroundImage: 'url('+captchaImg+')',backgroundSize:'cover',paddingBottom:'56.8%'}">
                    <div ref="click-captcha-container" style="position: absolute;width: 100%;height: 100%;top:0;left: 0;" @click="handleCaptchaClick($event)">
                        <Button shape="circle" @click.stop="fetchNewCaptchaImg" icon="refresh" style="position: absolute;top:0;right: 0;"></Button>
                        <Icon v-if="clickCaptchaTextColor[0]==='#fff'" type="heart" :size="35" :color="'#ed3f14'" :style="{position: 'absolute',top:(Number(clickCaptchaInfo[0].split(',')[1])-17)+'px',left: (Number(clickCaptchaInfo[0].split(',')[0])-14)+'px'}"></Icon>
                        <Icon v-if="clickCaptchaTextColor[1]==='#fff'" type="heart" :size="35" :color="'#ed3f14'" :style="{position: 'absolute',top:(Number(clickCaptchaInfo[1].split(',')[1])-17)+'px',left: (Number(clickCaptchaInfo[1].split(',')[0])-14)+'px'}"></Icon>
                        <Icon v-if="clickCaptchaTextColor[2]==='#fff'" type="heart" :size="35" :color="'#ed3f14'" :style="{position: 'absolute',top:(Number(clickCaptchaInfo[2].split(',')[1])-17)+'px',left: (Number(clickCaptchaInfo[2].split(',')[0])-14)+'px'}"></Icon>
                    </div>
                </div>
                </Col>
            </Row>
            <Row>
                <Col :style="{background: clickCaptchaBarColor,color:'#fff',textAlign:'center',marginTop:'5px',fontSize:'20px'}" span="24">
                {{clickCaptchaTip}}：
                <span v-for="(item,index) in clickCaptchaTextArr" :key="index">{{index!==0?'、':''}}&nbsp;"<span :style="{color:clickCaptchaTextColor[index]}">{{item}}</span>"&nbsp;</span>
                </Col>
            </Row>
        </Form-item>
    </div>
</template>
<script>
    import util from '../libs/util';
    import qs from 'qs';
    export default {
        data(){
            return {
                formItem: {
                    captcha:'',
                },
                captchaType:this.$store.state.server_data.captchaType,
                captchaImg:this.$store.state.captcha_data.captchaImg,
                clickCaptchaTextArr:this.$store.state.captcha_data.captchaText,
                clickCaptchaTip:'请依次点击上图中',
                clickCaptchaInfo:[],
                clickCaptchaBarColor:'#5cadff',
                clickCaptchaBarColorOri:'#5cadff',
                clickCaptchaBarColorSuccess:'#19be6b',
                clickCaptchaBarColorFailure:'#ed3f14',
                clickCaptchaTextColor:this.$store.state.captcha_data.captchaText.map(function(){
                    return '#000';
                }),
                destroyed:false
            }
        },
        mounted(){
            let thisApp=this;
            if(this.captchaType==='slide'){
                this.showSpin=true;
                util.loadScript('/index_org/assets/gt.js',function () {
                    util.ajax.get("/lib/StartCaptchaServlet.php?t="+ (new Date()).getTime())
                        .then(function(res){
                            thisApp.showSpin=false;
                            if(thisApp.destroyed) return;
                            window.initGeetest({
                                gt: res.data.gt,
                                challenge: res.data.challenge,
                                product: "popup",
                                offline: !res.data.success,
                                new_captcha: res.data.new_captcha,
                                width:'100%',
                            }, function(captchaObj){
                                if(!thisApp.destroyed)
                                    captchaObj.appendTo('#geetestCaptcha');
                                else
                                    return ;
                                captchaObj.onReady(function () {
                                    document.querySelector('#geetestWait').style.display='none';
                                });
                                captchaObj.onSuccess(function () {
                                    thisApp.updateCaptchaValue();
                                });
                                captchaObj.onError(function () {
                                    thisApp.updateCaptchaValue();
                                });
                                window.gt = captchaObj;
                            });
                        })
                        .catch(function(err){
                            console.log(err);
                        })
                });
            }
        },
        methods:{
            reset:function () {
                if(this.captchaType==='slide'){
                    window.gt.reset();
                    this.updateCaptchaValue();
                }else{
                    this.fetchNewCaptchaImg();
                }
            },
            destroy:function () {
                this.destroyed=true;
            },
            updateCaptchaValue:function () {
                let result;
                if(this.captchaType==='slide'){
                    let gtResult = window.gt.getValidate();
                    if(gtResult){
                        result={
                            'geetest_challenge':gtResult.geetest_challenge,
                            'geetest_validate':gtResult.geetest_validate,
                            'geetest_seccode':gtResult.geetest_seccode,
                        };
                    }else{
                        result='';
                    }
                }else{
                    if(this.formItem.captcha){
                        result = {
                            captcha:this.formItem.captcha
                        };
                    }else{
                        result='';
                    }
                }
                this.$emit('change', result);
            },
            fetchNewCaptchaImg:function () {
                let thisApp=this;
                let newAction=this.captchaType==='img'?'getNewCaptchaImg':'getNewClickCaptcha';
                util.ajax.get("/captchaController.php?action="+newAction+"&t="+ (new Date()).getTime())
                    .then(function(res){
                        if(res.data.status===200){
                            thisApp.captchaImg=res.data.captchaImg;
                            if(thisApp.captchaType==='click'){
                                thisApp.clickCaptchaTextArr=res.data.captchaText;
                                thisApp.clickCaptchaBarColor=thisApp.clickCaptchaBarColorOri;
                                thisApp.clickCaptchaInfo=[];
                                thisApp.clickCaptchaTextColor=thisApp.clickCaptchaTextArr.map(function (v,i) {
                                    return '#000';
                                });
                            }
                            thisApp.formItem.captcha='';
                            thisApp.updateCaptchaValue();
                            thisApp.$store.commit('updateCaptchaData',thisApp.captchaType==='img'?{
                                captchaImg:res.data.captchaImg,
                                captchaText:[],
                            }:{
                                captchaImg:res.data.captchaImg,
                                captchaText:res.data.captchaText,
                            });
                        }
                    })
                    .catch(function(err){
                        console.log(err);
                    })
            },
            handleCaptchaClick:function (e) {
                if(this.clickCaptchaTextArr.length===this.clickCaptchaInfo.length){
                    return ;
                }
                let clickContainer=this.$refs['click-captcha-container'];
                let hOffsetTop=function( elem ){
                    let top = elem.offsetTop;
                    let parent = elem.offsetParent;
                    while(parent){
                        top += parent.offsetTop;
                        parent = parent.offsetParent;
                    }
                    return top;
                };
                let hOffsetLeft=function( elem ){
                    let left = elem.offsetLeft;
                    let parent = elem.offsetParent;
                    while( parent){
                        left += parent.offsetLeft;
                        parent = parent.offsetParent;
                    }
                    return left;
                };
                let scrollLeft = document.body.scrollLeft || (document.documentElement && document.documentElement.scrollLeft);
                let scrollTop = document.body.scrollTop || (document.documentElement && document.documentElement.scrollTop);
                this.clickCaptchaInfo.push((scrollLeft + e.clientX - hOffsetLeft(clickContainer)) + ',' + (scrollTop + e.clientY - hOffsetTop(clickContainer)));
                let cLength=this.clickCaptchaInfo.length;
                this.clickCaptchaTextColor=this.clickCaptchaTextArr.map(function (v,i) {
                    return i<cLength?'#fff':'#000';
                });
                if(this.clickCaptchaTextArr.length===cLength){
                    let postParams={
                        info:[this.clickCaptchaInfo.join('-'), clickContainer.clientWidth, clickContainer.clientHeight].join(';')
                    };

                    let thisApp=this;
                    util.ajax.post("/captchaController.php?action=checkClickCaptcha&t="+ (new Date()).getTime(),qs.stringify(postParams) ,{
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                    })
                        .then(function(res){
                            if(res.data.status===200){
                                if(!res.data.checked){
                                    thisApp.clickCaptchaBarColor=thisApp.clickCaptchaBarColorFailure;
                                    thisApp.fetchNewCaptchaImg();
                                }else{
                                    thisApp.formItem.captcha=postParams.info;
                                    thisApp.updateCaptchaValue();
                                    thisApp.clickCaptchaBarColor=thisApp.clickCaptchaBarColorSuccess;
                                }
                            }
                        })
                        .catch(function(err){
                            console.log(err);
                        })
                }
            }
        }
    }
</script>