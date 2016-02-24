/**
 * Created by admin on 16/2/17.
 */
String.prototype.query=function(name){
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)","i");
    var r = window.location.search.substr(1).match(reg);
    if (r!=null) return unescape(r[2]); return null;
}
var EXSTATES = {
    Bnotavailable: '您的物品不可交换',
    Anotavailable: '对方的物品不可交换',
    BOFF: '您的物品下架啦',
    AOFF: '对方物品下架啦',
    Bnotexist:'对方物品不存在',
    Anotexist:'您的物品不存在',
    EXcannotedit:'交换信息不可修改',
    EXEMPTY:'交换信息为空',
    EXIDEMPTY:'交换ID为空'
};
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
        },
        beforeSend:function(){
            $('#loading').show();
        },
        complete:function(){
            $('#loading').hide();
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
//物品第一次上架
old.put_on_first = function(goods_id,fun){
    var url = old.interface+'?act=online_goods&goods_id='+goods_id;
    old.sendAjax(url,"",function(json){
        fun && fun(json);
    })
}
//物品上架
old.put_on = function(obj,goods_id){
    var url = old.interface+'?act=online_goods&goods_id='+goods_id;
    old.sendAjax(url,"",function(json){
        if(json.error == 0){
            //上架成功
            $(obj).off('click');
            var good = $(obj).parents('li');
            var btnStr = '<a href="#" onclick="javascript:old.pull_off(this,'+goods_id+')">下架</a>'
            good.find('.cans_right').removeClass('thin').html(btnStr);
            good.appendTo('#on>ul');
            $('#myTab li a[href="#on"]').tab('show');
        }else{
            //alert(json.data.msg);
            var d = new dialog(json.data,'light',function(){})
        }

    })
}
//物品下架
old.pull_off = function(obj,goods_id){
    var url = old.interface+'?act=offline_goods&goods_id='+goods_id;
    old.sendAjax(url,"",function(json){
        if(json.error == 0){
            //下架成功
            $(obj).off('click');
            var good = $(obj).parents('li');
            var btnStr = '<a href="#" onclick="javascript:old.put_on(this,'+goods_id+')">上架</a>';
            btnStr += '<span class="cans_right_btns"><div type="button" class="edit_btn"></div><button type="button" class="del_btn"></button></span>';
            good.find('.cans_right').addClass('thin').html(btnStr);
            good.appendTo('#off>ul');
            $('#myTab li a[href="#off"]').tab('show');
        }else{
            //alert(json.data.msg);
            var d = new dialog(json.data,'light',function(){})
        }
        //console.log(json)
    })
}
//提交交换订单
old.add_exchange = function(p){
    /*@obj p*/
    var url = old.interface+'?act=add_exchange';
    old.sendAjax(url,p,function(json){
        if(json.error == 0){
            //提交成功
            //window.location.href =  APP_ROOT+"/index.php?ctl=old&act=home_exchange";
            //return false;
            var a = new dialog('请求已经通知给主人，接下来你想去干啥？','default',function(){
                window.location.href =  APP_ROOT+"/index.php?ctl=old&act=home_exchange#my_exchange";
            },function(){
                window.location.href =  APP_ROOT+"/index.php?ctl=old";
            },[{name:"看看我的申请",action:'confirm'},{name:"看看别的",action:'cancel'}]);
        }else{
            var msg = json.data.msg;
            switch (json.data.error){
                case "-4":
                    msg = "｀咦，这个物品已经下线了｀";

            }
            var d = new dialog(msg,'light',function(){})
        }
        console.log(json)
    })
}
//添加物品
//参数：user_id,name,info,images(图片地址数组)，top_img_num(第几个图片为首图)
old.add_goods = function(p,card_state,user_id,images){
    var url = old.interface+'?act=add_goods&user_id='+user_id+"&images="+images;
    old.sendAjax(url,p,function(json){
        if(json.error == 0){
            //提交成功
            if(card_state!==1){
                //需实名认证
                window.location.href =  APP_ROOT+"/index.php?ctl=old&act=bind&state=onechance&goods_id="+json.data.id;
            }else{
                var goods_id = json.data.id;
                old.put_on_first(goods_id,function(json){
                    if(json.error==0){
                        old.success();
                    }else{
                        old.failed();
                    }
                })
                //window.location.href =  APP_ROOT+"/index.php?ctl=old&act=success";
            }
        }else{
            var d = new dialog(json.data.msg,'light',function(){})
        }
        console.log(json.data)
    })
}
//通用上传图
old.uploadFile = function(url,file_id,successFun,errorFun){

    $(document).on('change',"input[name='"+file_id+"']",function(){
        $('#loading').show();
        var files = $('input[name="'+file_id+'"]').prop('files');
        //console.log(files);

        $.ajaxFileUpload({
                url:url,
                //url:APP_ROOT_ORA+"/olddata.php?act=add_goods_img&user_id={$user.id}&img_num="+index,
                secureuri:false,
                fileElementId:file_id,
                dataType: 'json',
                success: function (data){
                    $('#loading').hide();
                    if(data.error==0){
                        successFun && successFun(data);
                    }else{
                        errorFun && errorFun(data);
                    }
                },
                error: function (data, status, e){
                    console.log(e.message);
                }
            }
        );
    });
}
old.success = function(){
    window.location.href = APP_ROOT+"/index.php?ctl=old&act=success";
    return;
}
old.failed = function(){
    window.location.href = APP_ROOT+"/index.php?ctl=old&act=failed";
    return;
}
old.ignore_exchange = function(exchange_id){
    var url = old.interface+'?act=ignore_exchange&id='+exchange_id;
    old.sendAjax(url,'',function(json){
        if(json.error == 0){
            setTimeout(function(){
                window.location.reload()
            },50);
        }else{
            var d = new dialog(json.data.msg,'light',function(){})
        }
        console.log(json)
    })
}
//同意交换
old.set_exchange = function(exchange_id){
    var url = old.interface+'?act=set_exchange&exchange_id='+exchange_id;
    old.sendAjax(url,'',function(json){
        if(json.error == 0){
            setTimeout(function(){
                window.location.reload()
            },50);
        }else{
            var msg = json.data.msg;
            switch (json.error){
                case "-6":
                    msg = EXSTATES.AOFF;
            }
            var d = new dialog(msg,'light',function(){})
        }
        console.log(json)
    })

}
//设置交换方式
old.set_exchange_type = function(p,susseccFun,errorFun){
//exchange_id,user_type,change_type,deposit
    var url = old.interface+'?act=set_exchange_type';
    old.sendAjax(url,p,function(json){
        if(json.error == 0){
            susseccFun &&susseccFun(json);
        }else{
            errorFun && errorFun(json);
        }
        console.log(json)
    })
}
//同意交换方式
old.agree_exchange_type = function(exchange_id,user_type){
    var url = old.interface+'?act=agree_exchange_type&exchange_id='+exchange_id+'&user_type='+user_type;
    old.sendAjax(url,"",function(json){
        if(json.error == 0){
            // from_change_type: "2"时调用支付接口去支付保证金
/*Object
* exchange_id: "56"
 exchange_state: "1"
 exchange_time: "0"
 from_address: ""
 from_change_type: "2"
 from_city: ""
 from_consignee: ""
 from_deposit: "1111.00"
 from_express: ""
 from_is_paid: "0"
 from_last_set_time: "1456047456"
 from_mobile: ""
 from_province: ""
 from_user_state: "1"
 from_zip: ""
 id: "7"
 to_address: ""
 to_change_type: "2"
 to_city: ""
 to_consignee: ""
 to_deposit: "1111.00"
 to_express: ""
 to_is_paid: "0"
 to_last_set_time: "1456046795"
 to_mobile: ""
 to_province: ""
 to_user_state: "1"
 to_zip: ""*/
            window.location.reload();
        }else{
            var d = new dialog(msg,'light',function(){})
        }
        console.log(json)
    })
}
//完成交换
old.finish_exchange = function(obj,exchange_id,user_type){
    var url = old.interface+'?act=finish_exchange&exchange_id='+exchange_id+'&user_type='+user_type;
    old.sendAjax(url,"",function(json) {
        if (json.error == 0) {
            //$(obj).parents('.change_Panels').remove();
            var d = new dialog('````恭喜！撒花！````', 'light', function () {})
            window.location.reload()
        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//设置交换地址
old.set_exchange_consignee = function(exchange_id,consginee_id,user_type){
    var url = old.interface+'?act=set_exchange_consignee&exchange_id='+exchange_id+'&user_type='+user_type+'&consignee_id='+consginee_id;
    old.sendAjax(url,"",function(json) {
        if (json.error == 0) {
            window.location.reload();
            var d = new dialog('````设置成功````', 'light', function () {})

        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//B放弃交换
old.giveup_exchange = function(obj,exchange_id,user_type){
    var url = old.interface+'?act=giveup_exchange&exchange_id='+exchange_id+'&user_type='+user_type;
    old.sendAjax(url,"",function(json) {
        if (json.error == 0) {
            $(obj).parents('.change_foot_body').html('<a href="javascript:void(0)" class="change_disagree faild">交换失败</a>')
        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//设置物流信息
old.set_exchange_express = function(p,fun){
    var url = old.interface+'?act=set_exchange_express';
    old.sendAjax(url,p,function(json) {
        if (json.error == 0) {
            //var d = new dialog('｀填写成功｀', 'light', function () {})
            fun&& fun(json);
        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//消息通知
old.show_notify = function(){
    var $div = $('.light-tips');
    old.get_notify(user_id,1,function(json){
        if(json.error == 0&&json.data.num.num>0){
            $div.html('<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button><a href="'+APP_ROOT+'?ctl=old&act=notify'+'" id="notify">您有'+json.data.num.num+'条新消息！</a>').addClass('show');
            $div.on(EVENT_TYPE,'[aria-label="Close"]',function(){
                $div.removeClass('show');
            });
            var notifyTimeout = setTimeout(function(){
                $div.removeClass('show');
            },2000);
        }
    },1)
}
//获取消息通知
old.get_notify = function(user_id,is_read,fun,page){
    var url = old.interface+'?act=get_notify',p={user_id:user_id,is_read:is_read,p:page,pagesize:20};
    old.sendAjax(url,p,function(json) {
        if (json.error == 0) {
            //var d = new dialog('｀填写成功｀', 'light', function () {})
            fun&& fun(json);
        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//设置消息已读
old.set_notify_isread = function(obj,id){
    var url = old.interface+'?act=set_notify_isread',p={notify_id:id};
    old.sendAjax(url,p,function(json) {
        if (json.error == 0) {
            $(obj).removeClass('spot');
        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}

//页面弹出消息
var url = window.slocation.href;
if(user_id && url.query('act')!=='home_exchange'&&url.query('act')!=='notify'){old.show_notify();}