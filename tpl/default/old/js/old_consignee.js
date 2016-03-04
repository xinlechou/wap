/**
 * Created by admin on 15/12/22.
 */
$(function() {
    //$("#showAddNewAddress").on('touchend click',addNewAddress);
    $(".isdefault_ckb label").on(EVENT_TYPE,function(){$(this).toggleClass('onchecked');});
    $(".closebox").on(EVENT_TYPE,function(){
        $.weeboxs.close();
    });
    $('#saveEditCon').bind(EVENT_TYPE,function(){
        saveConsignee('editConForm',function(json){
            if(json.status==1){
                $.showSuccess('保存地址成功!',function(){
                    window.location.href = APP_ROOT+"/index.php?ctl=old&act=consignee";
                });
            }else{
                $.showSuccess(json.info,null,1)
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
            $.showSuccess('确定删除本条地址？',function(){
                $("#loading").show();
                var url = APP_ROOT + "/index.php?ctl=oldsettings&act=del_consignee&id=" + id;
                $.ajax({
                    url: url, dataType: "json", type: "get",
                    success: function (json) {
                        $("#loading").hide();
                        if(json.status == 1){
                            $.showSuccess(json.info);
                            _this.remove();

                        }
                    }
                })
            });
        });
        //设为默认地址
        setDefaultBtn.bind(EVENT_TYPE,function(){
            $("#loading").show();
            var url = APP_ROOT + "/index.php?ctl=oldsettings&act=set_default_consignee&id=" + id;
            $.ajax({
                url: url, dataType: "json", type: "get",
                success: function (json) {
                    $("#loading").hide();
                    if(json.status == 1){
                        $.showSuccess('默认地址设置成功！');
                        $('.setDefault').each(function(){$(this).removeClass('onchecked').html('设为默认');})
                        setDefaultBtn.addClass('onchecked').html('默认地址');
                    }
                }
            })
        })
    });
});
/* 编辑地址*/
function saveConsignee(formid,fun){
    var $form = $("#"+formid),
        str = "",p=$form.serialize();
    if(!$.checkMobilePhone($.trim($form.find('input[name="mobile"]').val()))){
        str="请填写正确的手机号"; $.showSuccess(str);return;
    }
    //后台校验
    var url = APP_ROOT+"/index.php?ctl=ajax&act=save_consignee";

    $.ajax({url:url,dataType:"json",type:"POST",data:p,
        success:function(json){
            fun && fun(json);
        }
    });

}