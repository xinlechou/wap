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
    goback:function(){
        var ignore = ['success','faild','user_bind_mobile','wx_jspay','']
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
