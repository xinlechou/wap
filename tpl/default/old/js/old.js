/**
 * Created by admin on 16/2/17.
 */
var EXSTATES = {
    Bnotavailable: '您的物品不可交换',
    Anotavailable: '对方的物品不可交换',
    BOFF: '物品已下架',
    AOFF: '物品已下架',
    Bnotexist:'对方物品不存在',
    Anotexist:'您的物品不存在',
    EXcannotedit:'交换信息不可修改',
    EXEMPTY:'交换信息为空',
    EXIDEMPTY:'交换ID为空',
    HAVEIGNORED:'两物品已申请过交换'
};
var CARDSTATE = {
    1:'您已提交实名认证，请耐心等待审核通过',
    2:'您的实名认证未通过，请重新提交'}
var old = {
    sending:null,
    sendAjax: function (url, param, successFun, errorFun,type) {
        if(old.sending!==null) return;
        old.sending = $.ajax({
            url: url,
            data: param,
            type: 'GET',
            dataType: "json",
            success: function (json) {
                old.sending =null;
                successFun && successFun(json);

                //console.log(json);
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
//----------------------------------------management

//赞
old.add_focus = function(obj,user_id,goods_id){
    if(user_id==""){
        var d = new dialog('要先登录呦','alert',function(){
            window.location.href = APP_ROOT+"/index.php?ctl=olduser&act=login";
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
    $.showConfirm('物品下架后，所有和此物品相关的申请都会消失，确认下架？',function(){
        old.sendAjax(url,"",function(json){
            if(json.error == 0){
                //下架成功
                $(obj).off('click');
                var good = $(obj).parents('li');
                var btnStr = '<a href="#" onclick="javascript:old.put_on(this,'+goods_id+')">上架</a>';
                btnStr += '<span class="cans_right_btns"><a href="'+APP_ROOT+'/?ctl=old&act=edit&is_new=1&id='+goods_id+'" class="edit_btn"></a><button type="button" class="del_btn" onclick="old.del_goods(this,'+goods_id+')"></button></span>';
                good.find('.cans_right').addClass('thin').html(btnStr);
                var delStr = '<div class="cans_checkbox"><input type="checkbox" value="'+goods_id+'" id="'+goods_id+'" name="del" hidden="hidden"><label for='+goods_id+'></label></div>';
                good.prepend(delStr);
                good.prependTo('#off>ul');
                $('#myTab li a[href="#off"]').tab('show');
            }else{
                //alert(json.data.msg);
                var d = new dialog(json.data,'light',function(){})
            }
            //console.log(json)
        })
    });
}

//添加物品
//参数：user_id,name,info,images(图片地址数组)，top_img_num(第几个图片为首图)
old.add_goods = function(p,card_state,user_id){
    var url = old.interface+'?act=add_goods&user_id='+user_id;

    old.sendAjax(url,p,function(json){
        if(json.error == 0){
            //提交成功,先进入预览页面
            if(card_state==0){
                //需实名认证
                window.location.href =  APP_ROOT+"/index.php?ctl=old&act=bind&goods_id="+json.data.id;
            }else{
                window.location.href =  APP_ROOT+"/index.php?ctl=old&act=preview&id="+json.data.id;
                /*var goods_id = json.data.id;
                //console.log(goods_id)
                old.put_on_first(goods_id,function(json){
                    if(json.error==0){
                        old.success(goods_id);//用goodsid获取发布的物品信息，用于微信朋友圈分享
                    }else{
                        old.failed();
                    }
                })*/
                //window.location.href =  APP_ROOT+"/index.php?ctl=old&act=success";
            }
        }else{
            var d = new dialog(json.data.msg,'light',function(){})
        }
        console.log(json.data)
    })
}
//编辑物品
//编辑并上架是edit_online_goods。发布预览的编辑用edit_goods
//编辑并上架以后跳转到物品管理，只是编辑的就跳转到预览页
old.edit_goods = function(p,card_state,user_id){
    var url = old.interface+'?act=edit_goods&user_id='+user_id;
    old.sendAjax(url,p,function(json){
        if(json.error == 0){
            //提交成功
            var goods_id = json.data.id;
            window.location.href =  APP_ROOT+"/index.php?ctl=old&act=preview&id="+goods_id;
            console.log(goods_id)
            /*old.put_on_first(goods_id,function(json){
                if(json.error==0){
                    old.success();
                }else{
                    old.failed();
                }
            })*/
            //window.location.href =  APP_ROOT+"/index.php?ctl=old&act=success";
        }else{
            var d = new dialog(json.data.msg,'light',function(){})
        }
        console.log(json.data)
    })
}
old.edit_online_goods = function(p,card_state,user_id){
    var url = old.interface+'?act=edit_online_goods&user_id='+user_id;
    old.sendAjax(url,p,function(json){
        console.log(json);
        if(json.error == 0){
            //提交成功
            window.location.href =  APP_ROOT+"/index.php?ctl=old&act=home_goods";
            return;
            //window.location.href =  APP_ROOT+"/index.php?ctl=old&act=success";
        }else{
            var d = new dialog(json.data.msg,'light',function(){})
        }

    })
}
//删除物品
old.del_goods = function(obj,goods_id){
    var goods_ids = [];
    goods_ids.push(goods_id);
    var url = old.interface+'?act=del_goods&goods_ids='+goods_ids;
    old.sendAjax(url,"",function(json){
        if(json.error == 0){
            var d = new dialog('删除成功！','light',function(){})
            $(obj).parents('li').remove();
        }else{
            var d = new dialog(json.data.msg,'light',function(){})
        }
    });
}
//----------------------------------------upload

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
old.success = function(goods_id){
    //alert(goods_id)
    window.location.href = APP_ROOT+"/index.php?ctl=old&act=success&goods_id="+goods_id;
    return;
}
old.failed = function(){
    window.location.href = APP_ROOT+"/index.php?ctl=old&act=failed";
    return;
}
//----------------------------------------exchange
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
            //console.log(json);
            switch (json.error){
                case "-4":
                    msg = EXSTATES.BOFF;break;
                case "-7":
                    msg = EXSTATES.HAVEIGNORED;break;
            }
            var d = new dialog(msg,'light',function(){})
        }
    })
}
old.ignore_exchange = function(obj,exchange_id){
    var url = old.interface+'?act=ignore_exchange&id='+exchange_id;
    old.sendAjax(url,'',function(json){
        if(json.error == 0){
            $(obj).parents('.change_Panels').remove();
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
old.agree_exchange_type = function(exchange_id,user_type,user_id){
    var url = old.interface+'?act=agree_exchange_type&exchange_id='+exchange_id+'&user_type='+user_type;
    old.sendAjax(url,"",function(json){
        console.log(json)

        if(json.error == 0){
            //alert(json.data.from_change_type+'json.from_change_type');
            // from_change_type: "2"时调用支付接口去支付保证金
            if(json.data.from_change_type=="2"||json.data.from_change_type==2){
                var deposit = json.data.from_deposit;
                //用户点同意的时候如果发现以前交过钱就自动退款
                /*if(originalPrice>0){
                    deposit = json.data.from_deposit-originalPrice;
                }*/
                var p = {exchange_id:exchange_id,user_id:user_id,deposit:deposit,bank_id:0};
                //alert('originalPrice:'+originalPrice+';from_deposit:'+json.data.from_deposit);
                //alert('deposit'+p.deposit);
                localStorage.setItem("nowPayInfo", JSON.stringify(p));
                var is_refund = json.data.is_refund;
                if(is_refund){
                    $('#paymentModal').on('show.bs.modal', function (event) {
                        $('#is_refund').html('不用担心，您之前支付的保证金已退还。')
                    })
                }
                $('#paymentModal').modal('show');

                return;
            }
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
            var d = new dialog('恭喜您完成交换！', 'light', function () {})
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
            var d = new dialog('设置成功!', 'light', function () {window.location.reload();})

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
//删除交易
old.del_exchange = function(obj,exchange_id,user_type){
    var url = old.interface+'?act=del_exchange&exchange_id='+exchange_id+'&user_type='+user_type;
    old.sendAjax(url,'',function(json) {
        if (json.error == 0) {
            var d = new dialog('删除成功！', 'light', function () {});
            $(obj).parents('.change_Panels').remove();
        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//----------------------------------------pay
//创建支付订单
old.add_order = function(p){
    var url = old.interface+'?act=add_order';
    old.sendAjax(url,p,function(json) {
        if (json.error == 0) {
            var notice_id= json.data;
            //判断是否是微信浏览器
            if(tools.isWeiXin()){
                //跳转到支付页面
                var url = APP_ROOT+'?ctl=old&act=wx_jspay&id='+notice_id;
                window.location.href = url;
            }else{
                //扫码支付
                var url = APP_ROOT+'?ctl=old&act=jump_wxzf&id='+notice_id;
                window.location.href = url;
            }

        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
};

//----------------------------------------notify
//消息通知
old.show_notify = function(){
    var $div = $('.light-tips');
    old.get_unsent_notify(user_id,function(json){
        if(json.error == 0&&json.data.num>0){
            var str = '<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
            var tourl = APP_ROOT+'?ctl=old&act=notify';
            str += '<a href="'+tourl+'" id="notify">您有'+json.data.num+'条新消息！</a>';
            $div.html(str).addClass('show');
            $div.on(EVENT_TYPE,'[aria-label="Close"]',function(){
                $div.removeClass('show');
            });
            var notifyTimeout = setTimeout(function(){
                $div.removeClass('show');
            },5000);
        }
    },1)
}
//获取未读消息通知
old.get_notify = function(user_id,is_read,fun,page){
    var url = old.interface+'?act=get_notify',p={user_id:user_id,is_read:is_read?is_read:0,p:page?page:1,pagesize:20};
    old.sendAjax(url,p,function(json) {
        if (json.error == 0) {
            //var d = new dialog('｀填写成功｀', 'light', function () {})
            fun&& fun(json);
        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//获取新消息推送
old.get_unsent_notify = function(user_id,fun){
    var url = old.interface+'?act=get_unsent_notify',p={user_id:user_id};
    old.sendAjax(url,p,function(json) {
        if (json.error == 0) {
            fun&& fun(json);
        }
    })
}

//设置消息已读
old.set_notify_isread = function(obj,id){
    var url = old.interface+'?act=set_notify_isread',p={notify_id:id};
    //$(obj).parents('.notify_list').removeClass('spot');
    console.log(url);
    //return;
    old.sendAjax(url,p,function(json) {
        console.log(json)
        if (json.error == 0) {
            $(obj).parents('.notify_list').removeClass('spot');
        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//清除已读
old.del_all_isread_notify = function(){
    var url = old.interface+'?act=del_all_isread_notify',p={user_id:user_id};
    old.sendAjax(url,p,function(json) {
        if (json.error == 0) {
            window.location.reload();
        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//发送私信
old.send_message = function(p,fun){
    var url = old.interface+'?act=send_message';
    old.sendAjax(url,p,function(json) {
        if (json.error == 0) {
            fun && fun();
        } else {
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//获取私信
old.get_message = function(p,fun){
    var url = old.interface+'?act=get_message';
    old.sendAjax(url,p,function(json) {
        if (json.error == 0&& json.data.num.num>0) {
            fun && fun(json.data.list);
        } else {
            //var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//设为已读
old.set_message_isread = function(user_id,dest_id){
    var url = old.interface+'?act=set_message_isread&user_id='+user_id+'&dest_user_id='+dest_id;
    old.sendAjax(url,"",function() {})
}
//----------------------------------------verified
//获取实名认证状态
old.get_card_state = function(user_id){
    var url = APP_ROOT+'?ctl=old&act=get_user_state&id='+user_id;

    old.sendAjax(url,"",function(json) {
        switch (json.data){
            case '1':
                 //正在审核
                var d = new dialog(CARDSTATE[1], 'light', function () {})
                break;
            case '2':
                //审核失败
                var d = new dialog(CARDSTATE[2], 'alert', function () {
                    window.location.href =  APP_ROOT+"/index.php?ctl=old&act=bind";
                })
                break;
            case '0':
                //没有提交过审核，有一次发布机会
            case '3':
                //已通过实名认证
                window.location.href =  APP_ROOT+"/index.php?ctl=old&act=release";
                break;
            default :
                window.location.href =  APP_ROOT+"/index.php?ctl=old&act=bind";
                break;
        }
    })
}
//提交实名认证
old.set_user_card_state = function(user_id,fun){
    var url = old.interface+'?act=set_user_card_state&user_id='+user_id;
    old.sendAjax(url,"",function(json) {
        if(json.error==0){
            fun&&fun(json);
        }else{
            var d = new dialog(json.data.msg, 'light', function () {})
        }
    })
}
//页面弹出消息
var url = window.location.href;
//if(user_id && url.query('act')==null){old.show_notify();}
if(user_id){
    //var notifyTimeout = setInterval(function(){
        old.show_notify();
    //},10000);
}
