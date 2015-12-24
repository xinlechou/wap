/**
 * Created by admin on 15/12/7.
 */
var topicNum = 3;
$(function(){
    $(".indexSlick").slick({
//            lazyLoad: 'ondemand',
        dots:true,
        slidesToShow: 2,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 2000,
        arrows:false,  //是否显示翻页按钮
        //touchMove:true,
        draggable:true,
        mobileFirst:true,
        speed:2000
    });
    $(".index_slick_content").each(function(){
        var _this = $(this);
        var _id = _this.attr('data-id');
        _this.find('a').each(function(){
            $(this).attr('href',APP_ROOT+"/index.php?ctl=deal&id="+_id);
        })
    })
    $('#topicList').dropload({
        scrollArea : window,
        loadDownFn : function(me){
            $("#dragTips").hide()
            ajaxDragload();
            me.resetload();
        }
    });
    $("#dragTips").click(function(){
        ajaxDragload();
    });
})
function ajaxDragload(){
    //$("#dragTips").show();
    var url = APP_ROOT+"/index.php?act=ajaxTopic&s="+topicNum;
    $.ajax({url:url,dataType:"json",type:"POST",
        success:function(json){
            //$("#dragTips").hide();
            json = eval("("+json+")");
            if(json.status==0){
                //$("#dragTips").html(json.msg);
            }else{
                topicNum+=3;//每次加载三条数据
                var htmlStr = "",list = json.msg;
                for(var i = 0;i < list.length;i++){
                    if(list[i].wap_url){
                        htmlStr+='<li><a href="'+list[i].wap_url+'"><img src=".'+list[i].pic+'"  border=0  width="356" height="119" ></a></li>';
                    }else{
                        htmlStr+='<li><a href="'+list[i].url+'"><img src=".'+list[i].pic+'"  border=0  width="356" height="119" ></a></li>';
                    }
                }
                //setTimeout(function(){
                    $("#topicList").append(htmlStr);

                //},500);
                //$("#topicList").append(htmlStr);
                //$(htmlStr).fadeIn().appendTo("#topicList");
            }
        },error:function(json){
            alert(e.responseText);
        }
    });
}

