
$(function(){
    var $grid = $('.content');
    $grid.imagesLoaded( function() {
        $grid.masonry({
            itemSelector: '.content_list',
            //percentPosition: true,
            isAnimated: true
        });
    });

    $(window).scroll(function(){
        if ($(document).height() - $(this).scrollTop() - $(this).height()<80) {
            var nowpage = page;
            nowpage++;
            var url = APP_ROOT+'?ctl=old',p = {p:nowpage,ajax:1};
            if(ajax_flag) return;
            ajax_flag = $.ajax({url:url,dataType:"json",type:"get",data: p,
                success:function(json){
                    if (json.goods.error == 0) {
                        page = json.now_page;
                        writeIndex(json.goods.data);

                        //console.log($(a));
//                             $grid.masonry('reloadItems')
                        ajax_flag=null;
                    }
                },error:function(e){
                    alert(e.responseText);
                }
            });
        }
    });
    function writeIndex(goods){
        var len = $(goods).size();
        var htmlStr = '';
        if(len){
            for(var i = 0;i < len;i ++){

                htmlStr += '<div class="content_list">';
                htmlStr += '<a href="{url_wap r="old#detail" p="id='+goods[i].id+'"}" class="content_list_link"><img src="'+goods[i].head_image+'" class="img-responsive"></a>';
                htmlStr += '<div class="content_list_bottom">';
                htmlStr += '<img src="'+goods[i].avatar+'" class="img-circle circle">';
                htmlStr +=  '<a href="{url_wap r="old#detail" p="id='+goods[i].id+'"}" class="font_title">'+goods[i].name+'</a></div><div class="content_list_bottoma">';
                if(goods[i].is_focus){
                    htmlStr += '<img src="'+imgurl+'/old/images/zan_hover.png" data-src="'+imgurl+'/old/images/zan.png" border="0" onclick="old.add_focus(this,'+user_id+','+goods[i].id+')">';
                }else{
                    htmlStr += '<img src="'+imgurl+'/old/images/zan.png" data-src="'+imgurl+'/old/images/zan_hover.png" border="0" onclick="old.add_focus(this,'+user_id+','+goods[i].id+')">';
                }
                htmlStr += '<span>'+goods[i].focus_num+'</span></div></div>';
                $grid.append($(htmlStr)).masonry('appended', $(htmlStr));
                //$grid.masonry('reloadItems')

            }
            //return htmlStr;
//            console.log(htmlStr);
//            $('.content').append(htmlStr);
        }

    }
})
