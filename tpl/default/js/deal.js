$(document).ready(function(){
    deal.bind_attention_focus();
    $(window).scroll(function(){
        var scrollTop = $(this).scrollTop(),scrollHeight = $(document).height(),windowHeight = $(this).height();
        //数据条在滚动到最上方的时候&滚动到底部时显示，滚动过程中消失
        if(scrollTop>windowHeight){$("#footer_count_bar").fadeOut("slow")}
        if((scrollTop + windowHeight == scrollHeight) || scrollTop == 0){$("#footer_count_bar").fadeIn();}
    });
    /*绑定分享事件*/
    shareFun();//直接显示分享层：  new shareFun().show();
});
var deal = {
    bind_attention_focus:function(){
        //给关注增加事件
        $(".num-focus").live("touchend",function(){
            deal.attention_focus_deal($(this).attr("id"));
        });
    },
    attention_focus_deal:function(id){
        var url = APP_ROOT+"/index.php?ctl=deal&act=focus&id="+id;
        deal.sendAjax(url,"",function(ajaxobj){
                switch (ajaxobj.status)
                {
                    case 1:
                        /*1:关注成功*/
                        var $obj = $(".num-focus"),$value = parseInt($.trim($obj.find('em').html()));
                        $obj.addClass("attention");
                        $value++;
                        $obj.find('em').html($value);
                        break;
                    case 2:
                        /*2:取消关注成功*/
                        var $obj = $(".num-focus"),$value = parseInt($.trim($obj.find('em').html()));
                        $obj.removeClass("attention");
                        $value--;
                        $obj.find('em').html($value);
                        break;
                    case 3:
                        $.showErr(ajaxobj.info);
                        break;
                    default :
                        $.showErr("请先登录",function(){
                        location.href=APP_ROOT+"/index.php?ctl=user&act=login";
                    });
                        break;
                }})
    },
    send_message:function(usermessage_url){
        var url = usermessage_url;
        deal.sendAjax(url,"",function(ajaxobj){
            switch (ajaxobj.status)
            {
                case 1:
                    /*1:成功，弹出输入框*/
                    $.weeboxs.open("#msg_box", {boxid:'send_message',contentType:'text',showButton:false, title:'发送私信',width:300,height:185,type:'wee',focus:"textarea"});
                    deal.bind_usermessage_form();
                    break;
                case 2:
                    /*2:未登录，跳转到登录页面*/
                    window.location.href=APP_ROOT+"/?ctl=user&act=login";
                    break;
                default :
                    $.showErr(ajaxobj.info);
                    break;
            }})
    },
    bind_usermessage_form:function(){
        $("#close_msg_box").live('click',function(){$.weeboxs.close()})
        $("#send_msg_box").unbind('click');//先解绑事件，否则会重复绑定，数据会被重复发送
        $("#send_msg_box").bind("click",function(){
            var $form = $("#user_message_form"),url = $form.attr("action"),p = $form.serialize();
            if($.trim($form.find("textarea").val())=="") {
                $form.find("textarea").focus();
                return false;
            }
            $.ajax({url:url,dataType:"json",data:p,type:"POST",
                success:function(json){
                    $.weeboxs.close()
                    if(json.info!=""){
                        $.showSuccess(json.info,function(){
                            if(json.jump!=""){
                                location.href = json.jump;
                            }
                        });
                    }
                },error:function(json){
                    if(json.responseText!=''){alert(json.responseText);}}
            });
            return false;
        });
    },
    //显示登入框
   /* show_pop_login:function(){
        window.location.href= APP_ROOT+"/?ctl=user&act=login";//'{url_wap r="user#login"}';
    },*/
    sendAjax:function(url,param,successFun,errFun){
        $.ajax({
            url: url, dataType: "json", data: param, type: "POST",
            success: function (json) {
                successFun && successFun(json);
            }, error: function (json) {
                errFun && errFun(json);
            }
        });
    }
}

function bind_attention_focus(){
    //给关注增加事件
    $(".num-focus").live("click",function(){
        attention_focus_deal($(this).attr("id"));
    });
}
function attention_focus_deal(id)
{
    var ajaxurl = APP_ROOT+"/index.php?ctl=deal&act=focus&id="+id;
    $.ajax({
        url: ajaxurl,
        dataType: "json",
        type: "POST",
        success: function(ajaxobj){
            if(ajaxobj.status==1)
            {/*关注成功*/
                var $obj = $(".num-focus"),$value = parseInt($.trim($obj.find('em').html()));
                $obj.addClass("attention");
                $value++;
                $obj.find('em').html($value);

            }
            else if(ajaxobj.status==2)
            {/*取消关注成功*/
                var $obj = $(".num-focus"),$value = parseInt($.trim($obj.find('em').html()));
                $obj.removeClass("attention");
                $value--;
                $obj.find('em').html($value);
            }
            else if(ajaxobj.status==3)
            {
                $.showErr(ajaxobj.info);
            }
            else
            {
                $.showErr("请先登录",function(){
                    location.href=APP_ROOT+"/index.php?ctl=user&act=login";
                });
            }
        },
        error:function(ajaxobj)
        {
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
        }
    });
}
//发消息
function send_message(usermessage_url){
    var ajaxurl = usermessage_url;
    $.ajax({
        url: ajaxurl,
        dataType: "json",
        type: "POST",
        success: function(ajaxobj){
            if(ajaxobj.status==1)
            {
                $.weeboxs.open("#msg_box", {boxid:'send_message',contentType:'text',showButton:false, title:'发送私信',width:300,height:185,type:'wee',focus:"textarea"});
                //$("#user_message_form").find("textarea[name='message']").focus();
                bind_usermessage_form();
            }
            else if(ajaxobj.status==2)
            {
                window.location.href=APP_ROOT+"/?ctl=user&act=login";
            }
            else
            {
                $.showErr(ajaxobj.info);
            }
        },
        error:function(ajaxobj)
        {
            //			if(ajaxobj.responseText!='')
            //			alert(ajaxobj.responseText);
        }
    });
}
function bind_usermessage_form(){
    $("#close_msg_box").live('click',function(){$.weeboxs.close()})

    $("#send_msg_box").live("click",function(){
        var $form = $("#user_message_form"),url = $form.attr("action"),p = $form.serialize();
        if($.trim($form.find("textarea").val())=="") {
            $form.find("textarea").focus();
            return false;
        }
        $.ajax({url:url,dataType:"json",data:p,type:"POST",
            success:function(json){
                $.weeboxs.close()
                    if(json.info!=""){
                        $.showSuccess(json.info,function(){
                            if(json.jump!=""){
                                location.href = json.jump;
                            }
                        });
                }
            },error:function(json){
                if(json.responseText!=''){alert(json.responseText);}}
        });
        return false;
    });
}
//显示登入框
function show_pop_login(){
    window.location.href='{url_wap r="user#login"}';
}