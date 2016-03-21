//加载新手引导页面，点击一次后将不再出现
(function(){
    var introurl = location.href.split("?")[0]+'?ctl=old&act=intro&type=';
    var act =  location.href.query('act');
    var storage = window.localStorage;
    var del =  location.href.query('del');
    //var test = {'name':'1','hehe':2};//test = JSON.stringify(test)
    act = act?act:'index';

    if(del=='hehe'){
        storage.removeItem('isHuanIntroAction'+act);
        storage.removeItem('isHuanIntroActionno'+act);
        storage.removeItem('isHuanIntroActionhave'+act);
    }
    console.log(storage)
        setTimeout(function(){
            var a =storage.getItem('isHuanIntroAction'+act);
            //alert(a);
            //storage.removeItem('isHuanIntroActionhave'+act)
            if(!a){showIntro()}
            if(a&&act=='select'){
                //console.log(storage.getItem('isHuanIntroActionhave'+act))
                if((storage.getItem('isHuanIntroActionhave'+act)=='null')||(storage.getItem('isHuanIntroActionno'+act)=='null')){
                    //console.log('ss')
                    showIntro();
                };
            }
        },10)
    function showIntro(){
        //alert(introurl+act);
        $('.intro').load(introurl+act,'',function(){
            //console.log( $('#intro_'+act).html())
            switch (act){
                case 'index':
                    break;
                case 'select':
                    var oLi = $('.select-list li');
                    var oLi_first = $('.select-list li:first>figure');
                    var oLi_first_clone = oLi_first.clone();
                    oLi_first_clone.css({'width':oLi_first.width(),'height':oLi_first.width(),'position':'absolute','top':oLi_first.offset().top,'left':oLi_first.offset().left,'background-size':'cover'});
                    if(oLi.length>2){
                        $('#intro_have_goods').prepend(oLi_first_clone).show();
                        storage.setItem('isHuanIntroActionhave'+act,act);
                        storage.setItem('isHuanIntroActionno'+act,act);
                    }else{
                        storage.setItem('isHuanIntroActionno'+act,act);
                        $('#intro_no_goods').prepend(oLi_first_clone).show();//这儿有点问题
                    }
                    break;
            }
            $('#intro_'+act).show();
            $('#intro_'+act).click(function(){
                $(this).hide();
                storage.setItem('isHuanIntroAction'+act,act);
            });
        })
    }
})(jQuery);
