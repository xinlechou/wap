//var EVENT_TYPE = 'touchend';

$(function(){
    //删除订单
    $(".del_order").live(EVENT_TYPE,function(e){
        e.preventDefault();
        var url = $(this).attr('data-url');

        $.showConfirm("<div class='empty_tip'>余额支付部份将自动退回帐户</div><div>确定删除该记录吗？</div>",function(){
            $.ajax({url:url,dataType:"json",type:"get",
                success:function(json){
                    if(json.status==1){
                        $.showSuccess("删除成功！",function(){
                            if(json.jump!=""){
                                location.href = json.jump;
                            }else{
                                window.location.reload;
                            }
                        });
                    }
                },error:function(json){
                    if(json.responseText!=''){alert(json.responseText);}}
            });
        });
    });
    //显示详情
    $(".showProjectDetail").bind(EVENT_TYPE,function(){
//            $(".projectDetail").toggleClass('show');
        $(this).next('.projectDetail').toggleClass('show');
        $(this).find('img').toggleClass('rotate');
    })
})