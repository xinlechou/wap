String.prototype.query=function(name){
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)","i");
    var r = window.location.search.substr(1).match(reg);
    if (r!=null) return unescape(r[2]); return null;
};//擦，少个分号会报错

(function($) {
    $.fn.lazyload = function() {
        this.each(function() {
            var self = $(this), srcImg = self.attr("src");
            self.get(0).onerror = function() {
                self.attr("src", srcImg);
            }
            self.attr("src", self.attr("original"));
        }) ;
    }
    $(document).find('img.lazy').lazyload();
})(jQuery);
var tools = {
    /*判断是否微信浏览器*/
    isWeiXin:function(){
        var ua = window.navigator.userAgent.toLowerCase();
        return (ua.match(/MicroMessenger/i) == 'micromessenger');
    },
    share:function(){
        if(tools.isWeiXin()){
            $('#shareLayer').fadeIn().on(EVENT_TYPE,function(){
                $(this).fadeOut()
            })
        }else{
            $('#shareModal').modal('show')
        }
    },
    getFileType:function(id){
    //获取欲上传的文件路径
        var filepath = $('input[name="'+id+'"]')[0].value;
        var re = /(\+)/g;//为了避免转义反斜杠出问题，这里将对其进行转换
        var filename=filepath.replace(re,"#");//对路径字符串进行剪切截取
        var one=filename.split("#");//获取数组中最后一个，即文件名
        var two=one[one.length-1];
        var three=two.split(".");//再对文件名进行截取，以取得后缀名
        var last=three[three.length-1];//获取截取的最后一个字符串，即为后缀名
        var tp ="jpg,gif,bmp,JPG,GIF,BMP,jpeg,JPEG,png,PNG"; //添加需要判断的后缀名类型
        var rs=tp.indexOf(last);//返回符合条件的后缀名在字符串中的位置
        return (rs>=0);//如果返回的结果大于或等于0，说明包含允许上传的文件类型
    },
    addHandler: function (element, type, handler) {
        if (element.addEventListener) {
            element.addEventListener(type, handler, false);
        } else if (element.attachEvent) {
            element.attachEvent("on" + type, handler);
        } else {
            element["on" + type] = handler;
        }
    }
}

$.checkMobilePhone = function(value){
    if($.trim(value)!='')
        return /^\d{6,}$/i.test($.trim(value));
    else
        return true;
};

$.checkEmail = function(val){
    var reg = /^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/;
    return reg.test(val);
};
/*$.showTips = function(msg,fun){
    var $div = $('.light-tips');
    $div.html(msg).addClass('show');
    setTimeout(function(){
        $div.removeClass('show');
        fun && fun();},2000)
}*/
$.showErr = $.showSuccess = function(msg,fun){
    //console.log(fun)
    //if(fun!==undefined&&fun!==null){
    //    var a = new dialog(msg,'alert',fun);
    //}else{
        var a = new dialog(msg,'light',fun);
    //}
}
$.showConfirm = function(msg,fun){
    var a = new dialog(msg,'alert',fun);
}
$.extend({
    //  获取对象的长度，需要指定上下文 this
    Object:     {
        count: function( p ) {
            p = p || false;

            return $.map( this, function(o) {
                if( !p ) return o;

                return true;
            } ).length;
        }
    }
});
//数组去重
/*Array.prototype.unique = function() {
    this.sort();
    var re=[this[0]];
    for(var i = 1; i < this.length; i++)
    {
        if( this[i] !== re[re.length-1])
        {
            re.push(this[i]);
        }
    }
    return re;
}*/
var cookie = {
    //写cookies
    setCookie:function(name, value){
        var Days = 30;
        var exp = new Date();
        exp.setTime(exp.getTime() + Days*24*60*60*1000);
        document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
    },
    //读取cookies
    getCookie:function(name) {
        var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");
        if(arr=document.cookie.match(reg))
            return unescape(arr[2]);
        else
            return null;
    },

    //删除cookies
    delCookie:function(name)
    {
        var exp = new Date();
        exp.setTime(exp.getTime() - 1);
        var cval= cookie.getCookie(name);
        if(cval!=null) document.cookie= name + "="+cval+";expires="+exp.toGMTString();
    },


    //如果需要设定自定义过期时间
    //那么把上面的setCookie　函数换成下面两个函数就ok;
    //程序代码
    setCookieAndTime:function(name,value,time){
        var strsec = cookie.getsec(time);
        var exp = new Date();
        exp.setTime(exp.getTime() + strsec*1);
        document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
    },
    getsec:function(str){
        var str1=str.substring(1,str.length)*1;
        var str2=str.substring(0,1);
        if (str2=="s"){
            return str1*1000;
        }else if (str2=="h"){
            return str1*60*60*1000;
        }else if (str2=="d"){
            return str1*24*60*60*1000;
        }
    }
//这是有设定过期时间的使用示例：
//s20是代表20秒
//h是指小时，如12小时则是：h12
//d是天数，30天则：d30
//暂时只写了这三种，不知道谁有更好的方法，呵呵
//setCookie("name","hayden","s20");
}
