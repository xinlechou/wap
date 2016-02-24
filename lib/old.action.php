<?php

class oldModule{
	function index(){
        $p = $_REQUEST['p']?$_REQUEST['p']:"1";
        $is_ajax = $_REQUEST['ajax'];
        $pagesize = $_REQUEST['pagesize']?$_REQUEST['pagesize']:'';
        //获取物品列表
        $url = $this->getUrl().'act=get_goods_list&p='.$p.'&pagesize='.$pagesize;
        $goods = json_decode($this->getCurl($url),true);

        if(strval($goods['error'])!=='0'){
            $GLOBALS['tmpl']->assign("error",$goods['msg']);
        }else{
            foreach($goods['data'] as $k=>$v){
                //封面图像
                if(!empty($v['images_data']['list'])){
                    foreach($v['images_data']['list'] as $list_k=>$list_v){
                        if($list_v['is_top'] == 1){
                            $goods['data'][$k]['head_image'] = $list_v['url'];
                        }

                        if(empty($goods['data'][$k]['head_image'])){
                            $goods['data'][$k]['head_image'] = $v['images_data']['list'][0]['url'];
                        }
                        /*else{
                            $goods['data'][$k]['head_image'] = $v['images_data']['list'][0]['url'];
                        }*/
                    }
                }/*else{
                    $goods['data'][$k]['head_image'] = 'http://localhost:8888/htdocs/wap/tpl/default/old/images/holder.png';//test
                }*/
                //获取用户头像
                $user_id = $v['user_id'];
                $avatar = get_user_avatar($user_id,'middle');
                $goods['data'][$k]['avatar'] = $avatar;
                if($GLOBALS['user_info']){
                    $is_focus = $this->is_focus($GLOBALS['user_info']['id'],$v['id']);
                }else{
                    $is_focus = 0;
                }
                $goods['data'][$k]['is_focus'] = $is_focus;
            }
        }
        if($is_ajax==1){
            $data = array('goods'=>$goods,'now_page'=>$p);
            ajax_return($data);
            exit;
        }
//        print_r($goods['data']);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("goods",$goods['data']);
        $GLOBALS['tmpl']->assign("page",$p);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'Ta旧');
        $GLOBALS['tmpl']->display("old/old_index.html");
	}
    function home(){
        //个人管理
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
        $id = $GLOBALS['user_info']['id'];
        $good_num = $GLOBALS['db']->getOne("select count(*) from old_goods where user_id=".$id);
        $order_num = $GLOBALS['db']->getOne("select count(*) from old_exchange where from_user_id=".$id.' and exchange_state !=2');

        print_r($order_num);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'管理');
        $GLOBALS['tmpl']->assign("good_num",$good_num);
        $GLOBALS['tmpl']->assign("order_num",$order_num);
        $GLOBALS['tmpl']->display("old/home.html");
    }
    function home_goods(){
        //个人管理
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
        $p = $_REQUEST['p']?$_REQUEST['p']:1;
        $url = $this->getUrl().'act=get_user_goods_list&p='.$p.'&user_id='.$GLOBALS['user_info']['id'];
//        echo $url;
        $goods = json_decode($this->getCurl($url),true);
        if(strval($goods['error'])!=='0'){
            $GLOBALS['tmpl']->assign("error",$goods['data']['msg']);
        }else{
            foreach($goods['data'] as $k=>$v){
                //封面图像
                if(!empty($v['images_data']['list'])){
                    foreach($v['images_data']['list'] as $list_k=>$list_v){
                        if($list_v['is_top'] == 1){
                            $goods['data'][$k]['head_image'] = $list_v['url'];
                        }
                        if(empty($goods['data'][$k]['head_image'])){
                            $goods['data'][$k]['head_image'] = $v['images_data']['list'][0]['url'];
                        }
                    }

                }
                //获取用户头像
                $user_id = $v['user_id'];
                $avatar = get_user_avatar($user_id,'middle');
                $goods['data'][$k]['avatar'] = $avatar;
                if($GLOBALS['user_info']){
                    $is_focus = $this->is_focus($GLOBALS['user_info']['id'],$v['id']);
                }else{
                    $is_focus = 0;
                }
                $goods['data'][$k]['is_focus'] = $is_focus;
            }
        }
//        print_r($goods);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("goods",$goods['data']);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'我的物品');
        $GLOBALS['tmpl']->display("old/home_goods.html");
    }
    function home_exchange(){
        //个人管理
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
//        get_from_exchange_list
//        print_r($GLOBALS['user_info']);
        $p = $_REQUEST['p']?$_REQUEST['p']:1;
        //-----------别人向我申请的
        $from_url = $this->getUrl().'act=get_from_exchange_list&p='.$p.'&user_id='.$GLOBALS['user_info']['id'];
        $from_user_goods = json_decode($this->getCurl($from_url),true);
//        echo $from_url;
        if(strval($from_user_goods['error'])!=='0'){
            $GLOBALS['tmpl']->assign("from_error",$from_user_goods['data']['msg']);
        }else{
            foreach($from_user_goods['data']['list'] as $k=>$v){
                $from_user_goods['data']['list'][$k]['to_rest_time'] = $this->get_last_time($v['exchange_log']['to_last_set_time'],time());
                $from_user_goods['data']['list'][$k]['from_rest_time']= $this->get_last_time($v['exchange_log']['from_last_set_time'],time());
            }
            $GLOBALS['tmpl']->assign("from_user_goods",$from_user_goods['data']['list']);

        }
        //------------我申请交换的
        $to_url =  $this->getUrl().'act=get_to_exchange_list&p='.$p.'&user_id='.$GLOBALS['user_info']['id'];
        $to_user_goods = json_decode($this->getCurl($to_url),true);

        if(strval($to_user_goods['error'])!=='0'){
            $GLOBALS['tmpl']->assign("to_error",$to_user_goods['data']['msg']);
        }else{
            foreach($to_user_goods['data']['list'] as $k=>$v){
                $to_user_goods['data']['list'][$k]['to_rest_time'] = $this->get_last_time($v['exchange_log']['to_last_set_time'],time());
                $to_user_goods['data']['list'][$k]['from_rest_time']= $this->get_last_time($v['exchange_log']['from_last_set_time'],time());
            }
            $GLOBALS['tmpl']->assign("to_user_goods",$to_user_goods['data']['list']);
        }
        $user_consignee = $this->get_user_consignee($GLOBALS['user_info']['id']);
//        print_r($to_user_goods);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("user_consignee",$user_consignee['data']);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("page_title",'交换申请');
        $GLOBALS['tmpl']->display("old/home_exchange.html");
    }
    function release(){
        //发布物品
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
//        print_r($GLOBALS['user_info']);
        $old_user = $this->getUrl().'act=get_user_info&user_id='.$GLOBALS['user_info']['id'];
        $old_user_info = json_decode($this->getCurl($old_user),true);
//        print_r($old_user_info);exit;
        //实名认证：1:已认证 2:未认证&未发布，提示可发布一次 0:未认证通过，已发过物品直接进认证页面
        $card_state = 0;
        if($old_user_info['data']['card_state']=="1"){
            $card_state = 1;//已认证
        }else{
            //未认证
            $good_num = $GLOBALS['db']->getOne("select count(*) from old_goods where user_id=".$GLOBALS['user_info']['id']);
            $card_state = $good_num?0:2;
        }

        if($card_state==0){
            app_redirect(url_wap("old#bind",array(state=>'nochance')));
        }

        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
//        $GLOBALS['tmpl']->assign("pre",$pre);
        $GLOBALS['tmpl']->assign("card_state",$card_state);
        $GLOBALS['tmpl']->assign("page_title",'发布物品');
        $GLOBALS['tmpl']->display("old/release.html");
    }
    function notify(){
        $p = $_REQUEST['p'];
        $pagesize = $_REQUEST['pagesize'];
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
        $notifyUrl = $this->getUrl().'act=get_notify&user_id='.$GLOBALS['user_info']['id'].'&is_read=0&p='.$p.'&pagesize='.$pagesize;
        $notify_info = json_decode($this->getCurl($notifyUrl),true);
        foreach($notify_info['data']['list'] as $k => $v){
            $notify_info['data']['list'][$k]['log_time'] = date("Y-m-d ", $v['log_time']);
//            print_r($v);
        }
//        print_r($notify_info);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("page_title",'消息');
        $GLOBALS['tmpl']->assign("notify",$notify_info['data']['list']);
        $GLOBALS['tmpl']->display("old/notify.html");
    }
    function bind(){
        //实名认证
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
        $state = $_GET['state'];
        $goods_id= $_GET['goods_id'];
        $GLOBALS['tmpl']->assign("page_title","实名认证");
        $GLOBALS['tmpl']->assign("state",$state);
        $GLOBALS['tmpl']->assign("goods_id",$goods_id);
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->display("old/verified.html");
    }
    function select(){
        //提出交换&选择用来交换的物品
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));
        //获取物品列表
        $p = $_REQUEST['p']?$_REQUEST['p']:1;
        $from_user_id = $_REQUEST['from_user_id'];
        $from_goods_id = $_REQUEST['from_goods_id'];
        $url = $this->getUrl().'act=get_user_goods_list&p='.$p.'&user_id='.$GLOBALS['user_info']['id'];
        $goods = json_decode($this->getCurl($url),true);
//        print_r($goods);
        $goods = $this->get_cover($goods['data']);
//        print_r($_SERVER);
        es_session::set("from_user_id",$from_user_id);
        es_session::set("from_goods_id",$from_goods_id);
        $GLOBALS['tmpl']->assign("page_title","提出交换");
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("goods",$goods);
        $GLOBALS['tmpl']->assign("from_user_id",$from_user_id);
        $GLOBALS['tmpl']->assign("from_goods_id",$from_goods_id);
        $GLOBALS['tmpl']->display("old/select.html");
    }
    function detail(){
        //物品详情
        $id=$_REQUEST['id'];
        //获取物品列表
        $url = $this->getUrl().'act=get_goods_info&id='.$id;

        $details = json_decode($this->getCurl($url),true);
        if(strval($details['error'])!=='0') {
            $GLOBALS['tmpl']->assign("error", $details['msg']);
        }else{
            $detail = $details['data'];
            //获取用户头像
            $user_id = $detail['user_id'];
            $owner = $this->getUrl().'act=get_user_info&user_id='.$user_id;
            $owner_info = json_decode($this->getCurl($owner),true);
            $avatar = get_user_avatar($user_id,'middle');
            $username = $GLOBALS['db']->getOne("select `user_name` from  ".DB_PREFIX."user where id=".$user_id);
            $detail['avatar'] = $avatar;
            $detail['username'] = $username;
            $detail['card_state'] = $owner_info['data']['card_state'];
            //是否赞
            $detail['is_focus'] = $this->is_focus($GLOBALS['user_info']['id'],$id);
        }
        $ex_list = $this->get_goods_exchange_list($id);
        print_r($ex_list);
//            print_r($detail);
        $GLOBALS['tmpl']->assign("detail",$detail);
        $GLOBALS['tmpl']->assign("pre",get_gopreview_wap());
        $GLOBALS['tmpl']->assign("user",$GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("page_title",'物品详情');
        $GLOBALS['tmpl']->display("old/detail.html");
    }
    function success(){
        $from = es_session::get('from_goods_id');
        echo $from;
        if($from){
            $gopreview = url_wap('old#select',array('from_goods_id'=>$from,'from_user_id'=>es_session::get('from_user_id')));
        }else{
            $gopreview = url_wap('old');
        }
        $GLOBALS['tmpl']->assign("page_title",'发布成功');
        $GLOBALS['tmpl']->assign('pre',$gopreview);
        $GLOBALS['tmpl']->display("old/success.html");
    }
    function failed(){
        $GLOBALS['tmpl']->assign("page_title",'发布失败');
        $GLOBALS['tmpl']->display("old/failed.html");
    }
    /**
     * GET-CURL
     * @return array
     */
    function getCurl($url=''){
        try {
            // 1. 初始化
            $ch = curl_init();
            // 2. 设置选项，包括URL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            // 3. 执行并获取HTML文档内容
            $output = curl_exec($ch);
            $response = curl_getinfo($ch,CURLINFO_HTTP_CODE);
//            $curl_error_no = curl_errno($ch);

            // 4. 释放curl句柄
            curl_close($ch);

            if($output && !empty($output) && $response == '200'){
                return $output;
            }
            else{
                return false;
            }
        }
        catch(Exception $e){
            return false;
        }
    }
    function getUrl(){
        return get_domain().APP_ROOT."/olddata.php?";
    }
    function is_focus($user_id,$good_id){
        $tmp = $GLOBALS['db']->getOne("select `user_id` from old_goods_focus_log where goods_id=".$good_id." and user_id=".$user_id);
        return $tmp;
    }
    function get_cover($arr){
        foreach($arr as $k=>$v){
            //封面图像
            if(!empty($v['images_data']['list'])){
                foreach($v['images_data']['list'] as $list_k=>$list_v){
                    if($list_v['is_top'] == 1){
                        $arr[$k]['head_image'] = $list_v['url'];
                    }


                }
                if(empty($arr[$k]['head_image'])){
                    $arr[$k]['head_image'] = $v['images_data']['list'][0]['url'];
                }
//                print_r($arr[$k]['head_image']);

            }
        }
        return $arr;
    }
    function get_goods_exchange_list($goods_id){
        $goods_id = $_GET['id'];
        $url = $this->getUrl().'act=get_goods_exchange_list&id='.$goods_id;
//        echo $url;
        $goods = json_decode($this->getCurl($url),true);
//        print_r($goods);
    }
    function get_last_time($time_s,$time_n){
        $strtime = '';
        $a = $time =$time_s+3600*24 -  $time_n;//先设置24小时过期
        if($time >= 86400){
            return $strtime = date('Y-m-d H:i:s',$time_s);
        }
        if($time >= 3600){
            $strtime .= intval($time/3600).'小时';
            $time = $time % 3600;
        }else{
            $strtime .= '';
        }
        if($time >= 60){
            $strtime .= intval($time/60).'分钟';
            $time = $time % 60;
        }else{
            $strtime .= '';
        }
        if($time > 0){
            $strtime .= intval($time).'秒';
        }else{
            $strtime = "时间错误";
        }
//        print_r(intval($a)/(36*24).'--------');
        return array('time'=>$strtime,'percent'=>100-intval(intval($a)/(36*24)));
    }
    function get_user_consignee($user_id){
        $url = $this->getUrl().'act=get_user_consignee&user_id='.$user_id;
        $list = json_decode($this->getCurl($url),true);
        return $list;
    }
    /*copy from settings.action.php*/
    /*增加地址管理*/
    function consignee(){
        $GLOBALS['tmpl']->assign("page_title","最新动态");
        if(!$GLOBALS['user_info'])
            app_redirect(url_wap("user#login"));

        $consignee_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_consignee where user_id = ".intval($GLOBALS['user_info']['id']));
        $region_pid = 0;
        $region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
        $GLOBALS['tmpl']->assign("region_lv2",$region_lv2);


        if($region_pid>0)
        {
            $region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".$region_pid." order by py asc");  //三级地址
            $GLOBALS['tmpl']->assign("region_lv3",$region_lv3);
        }
        $GLOBALS['tmpl']->assign("consignee_list",$consignee_list);
        $GLOBALS['tmpl']->display("old/old_consignee.html");
    }
    function edit_consignee(){

        if(!$GLOBALS['user_info']) {app_redirect(url_wap("user#login"));} else {
            $id = intval($_REQUEST['id']);
            $title = empty($id)?"新建地址":"编辑";
            $consignee_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_consignee where id = ".$id." and user_id = ".intval($GLOBALS['user_info']['id']));
            $region_pid = 0;
            $region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
            foreach($region_lv2 as $k=>$v) {
                if($v['name'] == $consignee_info['province']) {
                    $region_lv2[$k]['selected'] = 1;
                    $region_pid = $region_lv2[$k]['id'];
                    break;
                }
            }
            $GLOBALS['tmpl']->assign("region_lv2",$region_lv2);

            if($region_pid>0) {
                $region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".$region_pid." order by py asc");  //三级地址
                foreach($region_lv3 as $k=>$v) {
                    if($v['name'] == $consignee_info['city']) {
                        $region_lv3[$k]['selected'] = 1;
                        break;
                    }
                }
                $GLOBALS['tmpl']->assign("region_lv3",$region_lv3);
            }
            $GLOBALS['tmpl']->assign("item",$consignee_info);
        }
        $GLOBALS['tmpl']->assign("page_title",$title);
        $GLOBALS['tmpl']->display("old/old_edit_consignee.html");
    }
    function chat(){
        $GLOBALS['tmpl']->assign("page_title",'对方名字');
        $GLOBALS['tmpl']->display("old/chat.html");
    }
}
?>