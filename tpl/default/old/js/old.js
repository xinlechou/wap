/**
 * Created by admin on 16/2/17.
 */
var old = {
    sendAjax: function (url, param, successFun, errorFun,type) {
    $.ajax({
        url: url,
        data: param,
        type: 'GET',
        dataType: "json",
        success: function (json) {
            successFun && successFun(json);
        },
        error: function (e) {
            errorFun && errorFun(e);
        }
    });
}};
old.interface = APP_ROOT_ORA+'/olddata.php';
//赞
old.add_focus = function(obj,user_id,goods_id){
    if(user_id==""){
        var d = new dialog('要先登录呦','alert',function(){
            window.location.href = APP_ROOT+"/index.php?ctl=user&act=login";
        })
        /*if(confirm('要先登录呦')){
            window.location.href = APP_ROOT+"/index.php?ctl=user&act=login";
        }*/
        return false;
    }
    var url = old.interface+'?act=add_focus&user_id='+user_id+'&goods_id='+goods_id;
    old.sendAjax(url,"",function(json){
        if(json.error == 0){
            //赞成功
            var after = obj.getAttribute('data-src'),num = parseInt($(obj).next().html());
            obj.dataset.src = obj.src;
            obj.src = after;
            $(obj).next().html(num+1);
        }else{
            if(json.error == -2){
                //已点赞
                old.del_focus(obj,user_id,goods_id);
            }else{
                var d = new dialog(json.data.msg,'light',function(){})
            }
        }
    })
}
//取消赞
old.del_focus = function(obj,user_id,goods_id){
    var url = old.interface+'?act=del_focus&user_id='+user_id+'&goods_id='+goods_id;
    old.sendAjax(url,"",function(json){
        if(json.error == 0){
            //赞成功
            var after = obj.getAttribute('data-src'),num = parseInt($(obj).next().html());;
            obj.dataset.src = obj.src;
            obj.src = after;
            $(obj).next().html(num-1);
        }else{
            //alert(json.data.msg);
            var d = new dialog(json.data.msg,'light',function(){})
        }
        console.log(json)
    })
}
