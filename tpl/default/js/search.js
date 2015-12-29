/**
 * Created by admin on 15/12/3.
 */
//var TOUCH_EVENTS = 'touchend';
$(function() {
        //        可弹出搜索层，待完善
    function search(btn,layer,closeBtn){
        this.showLayerBtn = btn?btn:$("#showLayerBtn");//显示搜索层
        this.layer = layer?layer:$("#searchLayer");
        this.closeBtn = closeBtn?closeBtn:$("#searchCloseBtn");
        this.searchBtn = $("#searchBtn");
        this.tabs = $(".search_list_bar_right>ul>li>a");
        this.keywordsList = $("#keywordsList>li>a");
        this.searchUrl = APP_ROOT+"/index.php?ctl=deals";
        var url = window.location.href,is_ap = url.query("isap");
        this.searchUrl += is_ap==1?"&isap=1":"";
        var _this = this;

        this.search = function(){
            var url = _this.searchUrl;
            //判断是否是全部分类
            if($("#cate_all").prop("checked")){
                url+="&cate=all";//http://localhost:8888/htdocs/wap/index.php?ctl=deals&cate=all
            }else{
                url+="&cate=";var p=[];
                $('input[name="cate"]').each(function(){
                    if(this.checked){
                        p.push($(this).val());
                    }
                });
                url+=p.join(',');//http://localhost:8888/htdocs/wap/index.php?ctl=deals&cate=2,1
            }
            if($("#status_all").prop("checked")){
                url+="&status=all";
            }else{
                url+="&status=";var p=[];
                $('input[name="status"]').each(function(){
                    if(this.checked){
                        p.push($(this).val());
                    }
                });
                url+=p.join(',');
            }
            var keyword = $('input[name="keyword"]').val();
            if(keyword!==""){
                url += "&keyword="+encodeURI(keyword);
            }
            window.location.href = url;
        }

        this.init = function(){
            //绑定事件
            _this.showLayerBtn.bind(EVENT_TYPE,function(e){
                e.preventDefault && e.preventDefault();
                _this.showLayer();
            });
            _this.searchBtn.live(EVENT_TYPE,_this.search);
            _this.tabs.live(EVENT_TYPE,function(e){
                //e.preventDefault && e.preventDefault();
                $(this).toggleClass('act');
            })
            _this.keywordsList.live(EVENT_TYPE,function(){
                var keyword = $(this).html();
                var url = _this.searchUrl;
                window.location.href = url + "&keyword=" +encodeURI(keyword);
            });
            $("#del").live(EVENT_TYPE,function(){$('input[name="keyword"]').val('').focus()});

        }
        this.showLayer = function(){
            //var _this = this;
            //this.layer.slideDown();
            this.layer.removeClass('fadeOutUp').addClass('fadeInDown');
            this.closeBtn.live("click",function(){_this.closeLayer();})

        }
        this.closeLayer = function(){
            //this.layer.slideUp();
            this.layer.removeClass('fadeInDown').addClass('fadeOutUp');

        }
        this.init();
    }
        var search = new search();
    }
);
