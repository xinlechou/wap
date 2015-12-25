/**
 * Created by admin on 15/12/22.
 */
$(function() {
    $("#showAddNewAddress").live('touchend click',addNewAddress);
    $(".isdefault_ckb label").live(EVENT_TYPE,function(){$(this).toggleClass('onchecked');});
    $(".closebox").live(EVENT_TYPE,function(){
        $.weeboxs.close();
    });
    $('#saveEditCon').bind(EVENT_TYPE,function(){
        saveConsignee('editConForm',function(json){
            if(json.status==1){
                showtips('保存地址成功!',function(){
                    window.location.href = APP_ROOT+"/index.php?ctl=settings&act=consignee";
                });
            }else{
                showtips(json.info)
            }
        });
    });
    $('.a_item').each(function(){
        var _this = $(this),
            id = _this.attr('data-id'),
            delBtn = _this.find('.delConsignee'),
            setDefaultBtn = _this.find('.setDefault');
        //删除地址
        delBtn.bind(EVENT_TYPE, function () {
            $.weeboxs.open('<div class="confirm">确定删除本条地址？</div>',{showTitle: false,
                //onclose:function(){},
                onok:function(){
                    $("#loading").show();
                    var url = APP_ROOT + "/index.php?ctl=settings&act=del_consignee&id=" + id;
                    $.ajax({
                        url: url, dataType: "json", type: "get",
                        success: function (json) {
                            $("#loading").hide();
                            if(json.status == 1){
                                showtips(json.info);
                                _this.remove();

                            }
                        }
                    })
                    $.weeboxs.close();
                   }
            });
        });
        //设为默认地址
        setDefaultBtn.bind(EVENT_TYPE,function(){
            $("#loading").show();
            var url = APP_ROOT + "/index.php?ctl=settings&act=set_default_consignee&id=" + id;
            $.ajax({
                url: url, dataType: "json", type: "get",
                success: function (json) {
                    $("#loading").hide();

                    if(json.status == 1){
                        /*$.showSuccess('默认地址设置成功！',function(){
                            $('.setDefault').each(function(){$(this).removeClass('onchecked');})
                            setDefaultBtn.addClass('onchecked');
                        });*/

                        showtips('默认地址设置成功！');
                        $('.setDefault').each(function(){$(this).removeClass('onchecked').html('设为默认');})
                        setDefaultBtn.addClass('onchecked').html('默认地址');
                    }
                }
            })
        })
    });
});
function showtips(msg,fun){
    $('.light-tips').html(msg).fadeIn(function(){
        setTimeout(function(){$('.light-tips').fadeOut(); fun && fun();},1000)
    });

}
/*显示添加新地址弹框*/
function addNewAddress(){
    $.weeboxs.open("#addNewAddress",{boxid:"addressBox",type:'box',width:300,showTitle: false,showCancel:false,clickClose:true,position:"element",trigger:"#body",okBtnName:"保存",onok:function(){
        saveConsignee('addNewAddressForm',function(json){
            if(json.status==1){
                $.weeboxs.close();
                showtips('保存地址成功!',function(){
                    window.location.reload();
                });
            }else{
                showtips(json.info)
            }
        });
        /*var $form = $("#addNewAddressForm"),$tips = $form.find('.tips'),
            str = "",p=$form.serialize();
        if(!$.checkMobilePhone($.trim($form.find('input[name="mobile"]').val()))){
            str="请填写正确的手机号";$tips.html(str).show();return;
        }
        //后台校验
        var url = APP_ROOT+"/index.php?ctl=ajax&act=save_consignee";

        $.ajax({url:url,dataType:"json",type:"POST",data:p,
            success:function(json){
                if(json.status==1){
                    $.weeboxs.close();
                    showtips('保存地址成功!',function(){
                        window.location.reload();
                    });
                }else{
                    showtips(json.info)
                }
            }
        });*/

    }});
}
/* 编辑地址*/
function saveConsignee(formid,fun){
    var $form = $("#"+formid),
        str = "",p=$form.serialize();
    if(!$.checkMobilePhone($.trim($form.find('input[name="mobile"]').val()))){
        str="请填写正确的手机号";showtips(str);return;
    }
    //后台校验
    var url = APP_ROOT+"/index.php?ctl=ajax&act=save_consignee";

    $.ajax({url:url,dataType:"json",type:"POST",data:p,
        success:function(json){
            fun && fun(json);
            /*if(json.status==1){
                //$.weeboxs.close();
                showtips('保存地址成功!',function(){
                    window.location.reload();
                });
            }else{
                showtips(json.info)
            }*/
        }
    });

}