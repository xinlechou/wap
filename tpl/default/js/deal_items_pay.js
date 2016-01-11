/**
 * Created by admin on 15/12/4.
 */

var DEAL_ITEM_ID = 0,//选择的支持项ID
    CONSIGNEE_ID = 0,//收件人ID
    IS_DELIVERY = 0,//是否需要配送
    PAYMENT_ID = 0;//支付方式ID
var url = window.location.href,is_ap = url.query('isap');
$(function(){
    /*绑定弹框事件*/
    $("#showAddressBox").live(EVENT_TYPE,editAddress);//一定要绑一个touchend事件才能显示出来，为啥？？执行顺序问题？
    $("#showAddNewAddress").live(EVENT_TYPE,addNewAddress);
    $("#showPaymentBox").live(EVENT_TYPE,choosePayment);
    $(".isdefault_ckb label").live(EVENT_TYPE,function(){$(this).toggleClass('onchecked');})

    $(".closebox").live(EVENT_TYPE,function(){
        $.weeboxs.close();
    });
    /*$(".pay_item").live(EVENT_TYPE,function(){alert($(this).attr('class'))
     var _this = $(this);var checkBg = _this.find('.con_btn');
     if(_this.hasClass('xm_list_soldout')){return;}
     checkBg.toggleClass('onchecked');
     var id = _this.find('.pay_sun').attr('data-id');
     DEAL_ITEM_ID = id;
     getDealItems(id);
     $(".pay_item").not(this).each(function(){
     $(this).find('.con_btn').removeClass('onchecked');
     })

     });*/
    $(".pay_item").each(function(){
        $(this).live(EVENT_TYPE,function(){
            var _this = $(this);var checkBg = _this.find('.con_btn');
            if(_this.hasClass('xm_list_soldout')){return;}
            checkBg.toggleClass('onchecked');
            var id = _this.find('.pay_sun').attr('data-id');
            DEAL_ITEM_ID = id;
            getDealItems(id);
            $(".pay_item").not(this).each(function(){
                $(this).find('.con_btn').removeClass('onchecked');
            });
            $("#loading").show();
        });
    });
    $("#pay_form").find('button').live(EVENT_TYPE,function(e){
        var $form = $("#pay_form"),inputs = "",param={};
        //判断必填项是否为空
        if(IS_DELIVERY){
            param = {id:DEAL_ITEM_ID,consignee_id:CONSIGNEE_ID,payment_id:PAYMENT_ID};

        }else{
            param = {id:DEAL_ITEM_ID,payment_id:PAYMENT_ID};
            if(!DEAL_ITEM_ID ||!PAYMENT_ID){
                return false;
            }
        }
        for(var x in param){
            if(!param[x]){
                $.showErr("请填写完整信息！");
                return false;
            }else{
                $form.find('input[name="'+x+'"]').val(param[x]);
            }
        }
        param.is_address = IS_DELIVERY;
        $form.find('input[name="is_address"]').val(IS_DELIVERY);
        $form.find('input[name="isap"]').val(is_ap);
        param.remark = $form.find('textarea').val();
        param.t = (new Date()).getTime();
        $form.find('input[name="t"]').val(param.t);
        //alert(checkPayment(PAYMENT_ID));
        return checkPayment(PAYMENT_ID);
        $form.submit();
    })
})
/*获取订单信息、收件人地址、地区*/
function getDealItems(id){
    var url = APP_ROOT+"/index.php?ctl=ajax&act=deal_items&id="+(id?id:DEAL_ITEM_ID);
    if(is_ap){url+="&isap="+is_ap;};
    $.ajax({url:url,dataType:"json",type:"POST",
        success:function(json){
            switch (json.status){
                case "0"://未登录
                    window.location.href = APP_ROOT+"/index.php?ctl=user&act=login";
                    break;
                case '1'://订单正常
                    show_pay_form(json);
                    break;
                case '2'://没有订单信息
                    $.showErr(json.error);
                    break;
            }
            $("#loading").hide();

        },error:function(json){}
    });
}
/*显示选择收件人地址弹框*/
function editAddress(){
    $.weeboxs.open("#addressBox",{boxid:"addressBox",type:'box',width:300,showTitle: false,showCancel:false,clickClose:true,okBtnName:"添加新地址",onok:addNewAddress});
    $("#addressList>li").live(EVENT_TYPE,function(){
        var $this =  $(this),$payForm = $("#payFormItems"),
            $consignee_info = $this.find("input[name='consignee_info']"),
            $checked = $.trim($consignee_info.val()),
            $address = $consignee_info.attr("data-address"),
            $mobile = $consignee_info.attr("data-mobile"),
            $name = $consignee_info.attr("data-name");
        //选择后将id填入收件人data-id,address,mobile填入html,仅用来展示，保存地址时只需要CONSIGNEE_ID
        CONSIGNEE_ID = $checked;

        $("#showAddressBox").attr("data-id",$checked).html($address);
        $payForm.find("input[name='mobile']").val($mobile);
        $payForm.find("input[name='username']").val($name);
        //交替选中样式
        if(!$this.hasClass("onchecked")){
            $this.addClass("onchecked").siblings().removeClass("onchecked");
            //$.weeboxs.close();
        }
    });
}
/*显示添加新地址弹框*/
function addNewAddress(){
    $.weeboxs.open("#addNewAddress",{boxid:"addressBox",type:'box',width:300,showTitle: false,showCancel:false,clickClose:true,okBtnName:"保存",position:"element",trigger:"#payForm",onok:function(){
        var $form = $("#addNewAddressForm"),$tips = $form.find('.tips'),
        /*p = {
         mobile:$.trim($form.find('input[name="mobile"]').val()),
         consignee:$form.find('input[name="consignee"]').val(),
         province:$("#area").attr('data-province'),
         city:$("#area").attr('data-city'),
         zip:$form.find('input[name="zip"]').val(),
         address:$form.find('input[name="address"]').val(),
         id:$form.find('input[name="id"]').val()
         }*/
            str = "",p=$form.serialize();
        if(!$.checkMobilePhone($.trim($form.find('input[name="mobile"]').val()))){
            str="请填写正确的手机号";$tips.html(str).show();return;
        }
        //后台校验
        var url = APP_ROOT+"/index.php?ctl=ajax&act=save_consignee";

        $.ajax({url:url,dataType:"json",type:"POST",data:p,
            success:function(json){
                switch (json.status){
                    case 0://保存地址失败
                        $('.tips').html(json.info).show();
                        break;
                    case 1://保存地址成功
                        getDealItems();//重新加载订单信息
                        $.weeboxs.close();
//                                $.showSuccess(json.info+"。重新加载订单信息。",function(){window.location.reload();})
                        break;
                }

            },error:function(json){}
        });

    }});
}
/*选择支付方式*/
function choosePayment(){
    $.weeboxs.open("#paymentBox",{type:'box',width:300,showTitle: false,showCancel:false,clickClose:true,okBtnName:"确定"});
    $("#paymentList>li:first").addClass("onchecked");
    $("#paymentList>li").live(EVENT_TYPE,function(){
        var $this = $(this),$oBtn = $("#showPaymentBox"),p_info = {id:$this.find('input[name="payment"]').val(),name:$this.find('span').html()};
        $oBtn.html(p_info.name).attr('data-id',p_info.id);
        PAYMENT_ID = p_info.id;// 修改支付方式
        //交替选中样式
        if(!$this.hasClass("onchecked")){
            $this.addClass("onchecked").siblings().removeClass("onchecked");
            //$.weeboxs.close();
        }
    });
}
/*write #payForm HTML*/
function show_pay_form(json){
    //为积分商城增加判断
    var ratio = json.deal_info!=='undefined' && json.deal_info.ap_ratio;
    //alert(is_ap)
    is_ap = (json.deal_info.is_ap==1||json.deal_info.is_ap==2)?1:0;
    $("#pay_form").find('input[name="isap"]').val(is_ap);
    var htmlStr = "",amount=0;
    if(typeof json.deal_item!=="undefined"){
        var price = json.deal_item.price,delivery_fee = json.deal_item.delivery_fee,boxHtmlStr="";

        amount = parseFloat(price)+parseFloat(delivery_fee);
        if(!is_ap){
            //------ 普通项目
            htmlStr+= '<li>应付金额：<color class="color">￥'+amount+'</color></li>';
            htmlStr+= '<li>支持金额：￥'+price+'</li>';
        }else{
            //------ 积分商城
            htmlStr+= '<li>应付积分：<color class="color">'+number_format(amount*ratio)+'</color></li>';
            htmlStr+= '<li>支持积分：'+number_format(price*ratio)+'</li>';
            //alert(userAp)
            if(parseInt(userAp)<amount*ratio){
                $('.tips').show();
                $("#pay_form").find('button').attr('disabled',true).addClass('disabled');
            }
        }

        /*添加收件人信息*/
        if(json.is_delivery=="1"){
            //需要配送
            IS_DELIVERY = 1;
            if(delivery_fee==0){
                htmlStr+= '<li>配送费用：免运费</li>';
            }else{
                if(!is_ap){
                    htmlStr+= '<li>配送费用：￥'+delivery_fee+'</li>';
                }else{
                    htmlStr+= '<li>配送费用：'+number_format(delivery_fee*ratio)+'</li>';
                }

            }
            //-----------有联系人列表时
            if(typeof json.consignee_list !== "undefined" && json.consignee_list.length >0){
                var c_list = json.consignee_list,c_list_default=c_list[0];
                //有默认地址时，将默认地址输出到页面上，所有地址输出到弹框中

                for(var i=0;i<c_list.length;i++){
                    var v= c_list[i];
                    var atmp = [v.province,v.city,v.address,v.zip,v.consignee,v.mobile];

                    if(c_list[i].is_default==1){
                        //保存默认地址
                        c_list_default = c_list[i];
                    }
                    var is_onchecked = c_list[i].is_default==1?"onchecked":"";
                    boxHtmlStr += '<li class="'+is_onchecked+'"><input class="radioclass" type="radio" value="'+ v.id+'" name="consignee_info" hidden="hidden" data-address="'+v.address+'" data-mobile="'+ v.mobile+'" data-name="'+ v.consignee+'""><span>'+atmp.join(" ")+'</span></li>';
                }
                CONSIGNEE_ID = c_list_default.id;
                htmlStr+= '<li>收件地址:<div class="input_pay" id="showAddressBox" data-id="'+c_list_default.id+'">'+c_list_default.address+'</div></li>';
                htmlStr+= '<li>联系人：<input type="text" name="username" Readonly class="input_ad" value="'+c_list_default.consignee+'"></li>';
                htmlStr+= '<li>联系电话：<input type="text" name="mobile" Readonly class="input_ad" value="'+c_list_default.mobile+'"></li>';
            }else{
                //-------------没有联系人列表时
                htmlStr+= '<li>收件地址:<div class="input_pay" id="showAddNewAddress" data-id="">添加新的收件人信息</div></li>';
                htmlStr+= '<li>联系人：<input type="text" name="username" Readonly class="input_ad" placeholder="无"></li>';
                htmlStr+= '<li>联系电话：<input type="text" name="mobile" Readonly class="input_ad" placeholder="无"></li>';
                boxHtmlStr+="请添加新地址";
            }
            /*写入收件人列表弹框*/
            $("#addressList").html(boxHtmlStr);
        }
        /*添加支付方式*/
        if(typeof json.payment_list!=="undefined"){
            var payBoxHtmlStr = "",p_list = json.payment_list;
            if(p_list.length>0){
                for(var i=0;i<p_list.length;i++){
                    if(p_list[i].is_effect){
                        payBoxHtmlStr += '<li><input class="radioclass" type="radio" value='+ p_list[i].id+' name="payment" hidden="hidden"><span>'+p_list[i].name+'</span></li>';
                    }
                }
                PAYMENT_ID = p_list[0].id;//默认支付方式
                if(p_list.length==1){
                    htmlStr+= '<li>支付方式:<div class="input_pay disabled" data-id="'+p_list[0].id+'">'+p_list[0].name+'</div></li>';
                }else{
                    htmlStr+= '<li>支付方式:<div class="input_pay" id="showPaymentBox" data-id="'+p_list[0].id+'">'+p_list[0].name+'</div></li>';
                }

            }

            /*写入支付方式列表弹框*/
            $("#paymentList").html(payBoxHtmlStr);
        }
        $("#payFormItems").html(htmlStr);
        if(!is_ap){$("#amount").html("￥"+amount);}else{ $("#amount").html(number_format(amount*ratio));}
        $("#payment_id").val(PAYMENT_ID);
        $("#consignee_id").val(c_list_default);
        $("#payForm").show("easy",function(){
            $('html,body').animate({scrollTop:$(this).offset().top},600);
        });
    }
}
function checkPayment(paymentId){
    switch (paymentId){
        case "35":
            //微信支付
            if(isWeiXin()){
                return true;
            }else{
                //$("#loading").show();
                $("#pay_form").find('input[name="isqr"]').val(1)
                //非微信浏览器时获取支付二维码
                //后台校验
                /*var url = APP_ROOT+"/index.php?ctl=cart&act=go_pay";
                 $.ajax({url:url,dataType:"json",type:"get",
                 success:function(json){
                 switch (json.status){
                 case "0"://获取失败
                 window.clipboardData.setData("Text",window.location.href);
                 $.showSuccess('复制地址成功！请粘贴到微信浏览器完成支付。',function(){
                 //alert(location.href);
                 window.location.href='weixin://'+location.href;
                 });
                 break;
                 case '1'://获取二维码正常
                 $("#pay_form").find('input[name="isqr"]').val(1)
                 *//*$.showSuccess('请使用微信浏览器打开此页才能正常使用。<img src="'+json.src+'">',function(){
                 //alert(location.href);
                 //window.location.href='weixin://'+location.href;
                 });*//*
                 break;

                 }
                 $("#loading").hide();

                 },error:function(json){}
                 });*/

                //return false;
            }
            return true;
            break;
        case "41":
            //积分支付
            //$.showSuccess('进入积分支付。');
            return true;
            break;
        default :
            //其它支付方式
            $.showSuccess('由于微信浏览器不能支持其他支付方式，请使用其他浏览器打开此页才能正常使用。');
            return false;
            break;
    }
    /*if(isWeiXin()){
     if(paymentId==35){
     return true;
     }else{
     $.showSuccess('由于微信浏览器不能支持其他支付方式，请使用其他浏览器打开此页才能正常使用。');
     return false;
     }
     }else{
     if(paymentId==35){
     $.showSuccess('请使用微信浏览器打开此页才能正常使用。');
     return false;
     }

     }*/
}
