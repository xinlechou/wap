/**
 * Created by admin on 16/1/19.
 */
$(function(){
    var a = isWeiXin();//要首先判断，否则在不刷新页面的情况下判断就不好使了

    $("#wechatLoginBtn").live(EVENT_TYPE,function(){
        if(a){
            weChatUtils.getWechatUserInfo();
        }else{
            var url = APP_ROOT+'?ctl=user&act=wx_login';
            window.location.href = url;
        }
    })
    if(!a&&$("#qr_container").length > 0){
        //非微信浏览器获取微信登录二维码
        weChatUtils.getWechatLoginQr();
        if(typeof WxLogin == 'undefined'){
            $('.wxtips').html('出问题啦。是不是网络没连上？');
        };
    }
})
var weChatUtils = {
    urlPrefix:'http://'+window.location.host+'/wxpay_web/wxUtil.php?act=',
    //获取微信登录二维码
    getWechatLoginQr:function(){
        var redirect_uri = encodeURIComponent("http://www.xinlechou.com/wxpay_web/wxUtil.php?act=getWechatAuthLogin");
        if(typeof WxLogin !== 'undefined'){
            var obj = new WxLogin({
                id:"qr_container",//二维码容器id
                appid: "wx12b1f2142f7e8e7a",
                scope: "snsapi_login",
                redirect_uri: redirect_uri,
                state: (new Date()).getTime(),
                style: "black",
                href: ""
            });
        }
    },
    getWechatUserInfo:function(){
        //临时地址
        var url = weChatUtils.urlPrefix+'getWechatAuthBase';
        console.log("开始注水："+url);
        window.location.href = url;
    }
}

