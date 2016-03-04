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

$.showErr = $.showSuccess = function(msg,fun){
    //console.log(fun)
    if(fun!==undefined){
        var a = new dialog(msg,'alert',fun);
    }else{
        var a = new dialog(msg,'light',function(){});
    }
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
